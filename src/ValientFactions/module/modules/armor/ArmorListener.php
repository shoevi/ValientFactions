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
 * Handles all event-driven armor perk logic.
 *
 * Events handled:
 *
 *  onEntityDamage            – env damage (fall, fire, explosion) reduction
 *  onEntityDamageByEntity    – PvP/mob perks:
 *                                • invincibility check (Void Shroud ability)
 *                                • damage reduction (victim)
 *                                • damage boost (attacker)
 *                                • knockback resistance (victim)
 *                                • lifesteal (attacker, post-damage heal)
 *                                • thorns reflection (victim → attacker)
 *                                • Slowness-on-hit (attacker applies to victim)
 *                                • Void low-HP emergency shield (victim trigger)
 *                                • Blaze ignition trigger (attacker)
 *                                • stats tracking
 *  onPlayerDeath             – Storm Killing Spree trigger
 *  onPlayerQuit              – clean up per-player caches
 */
final class ArmorListener implements Listener {

    /** Minimum HP left on an entity after thorns reflection (prevents instant-kill). */
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

    /**
     * Handles non-entity damage sources.
     * EntityDamageByEntityEvent is explicitly excluded to prevent double-handling.
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if ($event instanceof EntityDamageByEntityEvent || $event->isCancelled()) {
            return;
        }

        $victim = $event->getEntity();
        if (!$victim instanceof Player) {
            return;
        }

        // Invincibility (Void Shroud)
        if ($this->armorManager->isInvincible($victim)) {
            $event->cancel();
            return;
        }

        $cause = $event->getCause();

        switch ($cause) {
            case EntityDamageEvent::CAUSE_FALL:
                $fallReduction = $this->armorManager->getFallDamageReduction($victim);
                if ($fallReduction > 0.0) {
                    $event->setBaseDamage($event->getBaseDamage() * (1.0 - $fallReduction));
                }
                break;

            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                $explodeReduction = $this->armorManager->getExplosionResistance($victim);
                if ($explodeReduction > 0.0) {
                    $event->setBaseDamage($event->getBaseDamage() * (1.0 - $explodeReduction));
                }
                break;
        }

        // General damage reduction applies to all non-entity sources
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

        // --- Invincibility check (Void Shroud) --------------------------------
        if ($victim instanceof Player && $this->armorManager->isInvincible($victim)) {
            $event->cancel();
            return;
        }

        // --- Void low-HP emergency shield (passive trigger) -------------------
        if ($victim instanceof Player) {
            $hpAfterHit = $victim->getHealth() - $event->getFinalDamage();
            if ($hpAfterHit <= $victim->getMaxHealth() * 0.25) {
                if ($this->armorManager->consumeVoidShield($victim)) {
                    $event->cancel();
                    $victim->sendTip("§5✦ §dVoid Shield §5absorbed the killing blow!");
                    return;
                }
            }
        }

        // --- Damage boost (attacker wearing full set) -------------------------
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

        // --- Damage reduction (victim wearing full set) -----------------------
        if ($victim instanceof Player) {
            // Skip if this is a thorns reflection to prevent double-reduction
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

        // --- Knockback resistance (victim) ------------------------------------
        if ($victim instanceof Player) {
            $kbResist = $this->armorManager->getKnockbackResistance($victim);
            if ($kbResist > 0.0) {
                $event->setKnockBack($event->getKnockBack() * (1.0 - $kbResist));
            }
        }

        // Store the final damage before post-event hooks
        $finalDamage = $event->getFinalDamage();

        // --- Lifesteal (attacker) ---------------------------------------------
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

        // --- Thorns (victim reflects damage to attacker) ----------------------
        if ($victim instanceof Player && $attacker instanceof Player) {
            // Guard against recursive thorns reflection
            if (!$this->armorManager->isThornsReflecting($victim)) {
                $thorns = $this->armorManager->getThorns($victim);
                if ($thorns > 0.0) {
                    $reflectedDamage = $finalDamage * $thorns;
                    $this->armorManager->startThornsReflection($victim);
                    $this->plugin->getScheduler()->scheduleDelayedTask(
                        new ClosureTask(function () use ($victim, $attacker, $reflectedDamage): void {
                            $this->armorManager->endThornsReflection($victim);
                            if ($attacker->isOnline() && $attacker->isAlive()) {
                                $newHp = max(self::MIN_HEALTH_AFTER_THORNS, $attacker->getHealth() - $reflectedDamage);
                                $attacker->setHealth($newHp);
                            }
                        }),
                        1
                    );
                    $this->armorManager->getStats()->increment(
                        $victim,
                        ArmorStatsTracker::THORNS_REFLECTED,
                        $reflectedDamage
                    );
                }
            }
        }

        // --- Slowness on hit (attacker applies to victim) ---------------------
        if ($attacker instanceof Player && $victim instanceof Player) {
            $slownessTicks = $this->armorManager->getSlownessOnHitTicks($attacker);
            if ($slownessTicks > 0) {
                $victim->getEffects()->add(
                    new EffectInstance(VanillaEffects::SLOWNESS(), $slownessTicks, 0, false) // Slowness I
                );
            }
        }

        // --- Blaze ignition trigger (attacker, 35% chance) --------------------
        if ($attacker instanceof Player && $victim instanceof Player) {
            $blazeSet = $this->armorManager->getCachedActiveSet($attacker)
                ?? $this->armorManager->getActiveSet($attacker);
            if ($blazeSet !== null && $blazeSet->getName() === "Blaze") {
                if (mt_rand(1, 100) <= 35) {
                    $victim->setOnFire(4);
                    $this->armorManager->getStats()->increment(
                        $attacker,
                        ArmorStatsTracker::COMBAT_TRIGGERS
                    );
                }
            }
        }

        // --- Stats: hits dealt / taken ----------------------------------------
        if ($attacker instanceof Player) {
            $this->armorManager->getStats()->increment($attacker, ArmorStatsTracker::HITS_DEALT);
        }
        if ($victim instanceof Player) {
            $this->armorManager->getStats()->increment($victim, ArmorStatsTracker::HITS_TAKEN);
        }
    }

    // =========================================================================
    // Kill detection  (Storm "Killing Spree")
    // =========================================================================

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        $cause  = $victim->getLastDamageCause();

        if (!$cause instanceof EntityDamageByEntityEvent) {
            return;
        }

        $killer = $cause->getDamager();
        if ($killer instanceof Player && $killer->isOnline() && $killer->isAlive()) {
            $this->armorManager->handleKill($killer);
        }
    }

    // =========================================================================
    // Cleanup
    // =========================================================================

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $this->armorManager->removeFromCache($event->getPlayer());
    }
}
