<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use ValientFactions\Main;

/**
 * Event-driven armor perk logic.
 *
 * Passive perks (damage boost/reduction, KB resist, lifesteal, thorns,
 * slowness-on-hit, fall/explosion reduction) are applied inline.
 *
 * Active triggers ({@see SetTrigger}) are dispatched through
 * {@see ArmorManager::fireTrigger()} – no hardcoded set-name checks here.
 *
 * Combat event order (EntityDamageByEntity):
 *  1. Cancel if wearer invincible.
 *  2. Dispatch ON_HIT_TAKEN for victim (preventDamage cancels event).
 *  3. Damage boost (attacker passive perk).
 *  4. Damage reduction (victim passive perk).
 *  5. Knockback resistance (victim passive perk).
 *  6. Lifesteal heal (attacker, 1-tick delay).
 *  7. Thorns reflection (victim to attacker, 1-tick delay, recursion-guarded).
 *  8. Slowness-on-hit (attacker perk to victim).
 *  9. Dispatch ON_HIT_DEALT for attacker (Blaze Ignition, Inferno Nova, etc.).
 * 10. Stats tracking.
 */
final class ArmorListener implements Listener {

    /** Minimum HP left after thorns reflection (prevents instant-kill). */
    private const MIN_HEALTH_AFTER_THORNS = 0.5;

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    // =========================================================================
    // Environmental damage  (fall, fire, explosion, etc.)
    // =========================================================================

    public function onEntityDamage(EntityDamageEvent $event): void {
        if ($event instanceof EntityDamageByEntityEvent || $event->isCancelled()) {
            return;
        }

        $victim = $event->getEntity();
        if (!$victim instanceof Player) {
            return;
        }

        if ($this->armorManager->isInvincible($victim)) {
            $event->cancel();
            return;
        }

        $cause = $event->getCause();

        switch ($cause) {
            case EntityDamageEvent::CAUSE_FALL:
                $fallRed = $this->armorManager->getFallDamageReduction($victim);
                if ($fallRed > 0.0) {
                    $event->setBaseDamage($event->getBaseDamage() * (1.0 - $fallRed));
                }
                break;

            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                $explRed = $this->armorManager->getExplosionResistance($victim);
                if ($explRed > 0.0) {
                    $event->setBaseDamage($event->getBaseDamage() * (1.0 - $explRed));
                }
                break;
        }

        $reduction = $this->armorManager->getDamageReduction($victim);
        if ($reduction > 0.0) {
            $event->setBaseDamage($event->getBaseDamage() * (1.0 - $reduction));
        }
    }

    // =========================================================================
    // PvP / entity combat
    // =========================================================================

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $attacker = $event->getDamager();
        $victim   = $event->getEntity();

        // Step 1: Invincibility (Void Shroud, etc.)
        if ($victim instanceof Player && $this->armorManager->isInvincible($victim)) {
            $event->cancel();
            return;
        }

        // Step 2: ON_HIT_TAKEN triggers for victim (may cancel event via preventDamage)
        if ($victim instanceof Player) {
            $ctx = new TriggerContext(
                TriggerType::ON_HIT_TAKEN,
                $victim,
                $attacker instanceof Player ? $attacker : null,
                $event->getFinalDamage(),
                $victim->getHealth(),
                $victim->getMaxHealth()
            );
            $this->armorManager->fireTrigger($victim, $ctx);
            if ($ctx->preventDamage) {
                $event->cancel();
                return;
            }
        }

        // Step 3: Damage boost (attacker passive perk)
        if ($attacker instanceof Player) {
            $boost = $this->armorManager->getDamageBoost($attacker);
            if ($boost > 0.0) {
                $event->setBaseDamage($event->getBaseDamage() * (1.0 + $boost));
                $this->armorManager->getStats()->increment(
                    $attacker,
                    ArmorStatsTracker::DAMAGE_DEALT_BOOSTED,
                    $event->getBaseDamage() * $boost
                );
            }
        }

        // Step 4: Damage reduction (victim passive perk, skipped for thorns bounces)
        if ($victim instanceof Player) {
            if (!($attacker instanceof Player && $this->armorManager->isThornsReflecting($attacker))) {
                $reduction = $this->armorManager->getDamageReduction($victim);
                if ($reduction > 0.0) {
                    $reduced = $event->getBaseDamage() * $reduction;
                    $event->setBaseDamage($event->getBaseDamage() * (1.0 - $reduction));
                    $this->armorManager->getStats()->increment(
                        $victim,
                        ArmorStatsTracker::DAMAGE_ABSORBED,
                        $reduced
                    );
                }
            }
        }

        // Step 5: Knockback resistance (victim passive perk)
        if ($victim instanceof Player) {
            $kbResist = $this->armorManager->getKnockbackResistance($victim);
            if ($kbResist > 0.0) {
                $event->setKnockBack($event->getKnockBack() * (1.0 - $kbResist));
            }
        }

        $finalDamage = $event->getFinalDamage();

        // Step 6: Lifesteal (attacker, heals 1 tick after damage is applied)
        if ($attacker instanceof Player && $victim instanceof Player) {
            $lifesteal = $this->armorManager->getLifesteal($attacker);
            if ($lifesteal > 0.0) {
                $healAmount = $finalDamage * $lifesteal;
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(function () use ($attacker, $healAmount): void {
                        if ($attacker->isOnline() && $attacker->isAlive()) {
                            $newHp = min($attacker->getMaxHealth(), $attacker->getHealth() + $healAmount);
                            $attacker->setHealth($newHp);
                        }
                    }),
                    1
                );
                $this->armorManager->getStats()->increment(
                    $attacker,
                    ArmorStatsTracker::LIFESTEAL_HEALED,
                    $finalDamage * $lifesteal
                );
            }
        }

        // Step 7: Thorns reflection (victim to attacker, recursion-guarded)
        if ($victim instanceof Player && $attacker instanceof Player) {
            if (!$this->armorManager->isThornsReflecting($victim)) {
                $thorns = $this->armorManager->getThorns($victim);
                if ($thorns > 0.0) {
                    $reflected = $finalDamage * $thorns;
                    $this->armorManager->startThornsReflection($victim);
                    $this->plugin->getScheduler()->scheduleDelayedTask(
                        new ClosureTask(function () use ($victim, $attacker, $reflected): void {
                            $this->armorManager->endThornsReflection($victim);
                            if ($attacker->isOnline() && $attacker->isAlive()) {
                                $newHp = max(self::MIN_HEALTH_AFTER_THORNS, $attacker->getHealth() - $reflected);
                                $attacker->setHealth($newHp);
                            }
                        }),
                        1
                    );
                    $this->armorManager->getStats()->increment(
                        $victim,
                        ArmorStatsTracker::THORNS_REFLECTED,
                        $reflected
                    );
                }
            }
        }

        // Step 8: Slowness-on-hit (attacker's perk applied to victim)
        if ($attacker instanceof Player && $victim instanceof Player) {
            $slowTicks = $this->armorManager->getSlownessOnHitTicks($attacker);
            if ($slowTicks > 0) {
                $victim->getEffects()->add(
                    new EffectInstance(VanillaEffects::SLOWNESS(), $slowTicks, 0, false)
                );
            }
        }

        // Step 9: ON_HIT_DEALT triggers for attacker (Blaze Ignition, Inferno Nova, etc.)
        if ($attacker instanceof Player && $victim instanceof Player) {
            $ctx = new TriggerContext(
                TriggerType::ON_HIT_DEALT,
                $attacker,
                $victim,
                $finalDamage,
                $attacker->getHealth(),
                $attacker->getMaxHealth()
            );
            $this->armorManager->fireTrigger($attacker, $ctx);
        }

        // Step 10: Stats
        if ($attacker instanceof Player) {
            $this->armorManager->getStats()->increment($attacker, ArmorStatsTracker::HITS_DEALT);
        }
        if ($victim instanceof Player) {
            $this->armorManager->getStats()->increment($victim, ArmorStatsTracker::HITS_TAKEN);
        }
    }

    // =========================================================================
    // Kill detection  (dispatches ON_KILL trigger – e.g. Storm Thunder Dash)
    // =========================================================================

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        $cause  = $victim->getLastDamageCause();

        if (!$cause instanceof EntityDamageByEntityEvent) {
            return;
        }

        $killer = $cause->getDamager();
        if (!$killer instanceof Player || !$killer->isOnline() || !$killer->isAlive()) {
            return;
        }

        $ctx = new TriggerContext(
            TriggerType::ON_KILL,
            $killer,
            $victim,
            0.0,
            $killer->getHealth(),
            $killer->getMaxHealth()
        );
        $this->armorManager->fireTrigger($killer, $ctx);
        $this->armorManager->getStats()->increment($killer, ArmorStatsTracker::KILLS_WITH_SET);
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $this->armorManager->removeFromCache($event->getPlayer());
    }
}
