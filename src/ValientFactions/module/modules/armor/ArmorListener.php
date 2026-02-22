<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use ValientFactions\Main;

/**
 * Handles all combat-related perk logic for custom armor sets:
 *
 *  • onEntityDamage          – damage reduction for environmental damage (fire, fall, etc.)
 *  • onEntityDamageByEntity  – damage reduction + damage boost + knockback resist + lifesteal
 *
 * Separation prevents double-application of damage reduction when a player is
 * hurt by another entity (EntityDamageByEntityEvent extends EntityDamageEvent,
 * so both would fire for the same event otherwise).
 */
final class ArmorListener implements Listener {

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    /**
     * Applies victim damage reduction for non-entity damage sources
     * (e.g. fall, fire, suffocation).
     * Entity-vs-entity damage is handled in onEntityDamageByEntity.
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        if ($event instanceof EntityDamageByEntityEvent || $event->isCancelled()) {
            return;
        }

        $victim = $event->getEntity();
        if (!$victim instanceof Player) {
            return;
        }

        $reduction = $this->armorManager->getDamageReduction($victim);
        if ($reduction > 0.0) {
            $event->setBaseDamage($event->getBaseDamage() * (1.0 - $reduction));
        }
    }

    /**
     * Handles all PvP / mob combat perks:
     *  - Victim damage reduction
     *  - Attacker damage boost
     *  - Victim knockback resistance
     *  - Attacker lifesteal
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }

        $attacker = $event->getDamager();
        $victim   = $event->getEntity();

        // --- Damage boost (attacker) ----------------------------------------
        if ($attacker instanceof Player) {
            $boost = $this->armorManager->getDamageBoost($attacker);
            if ($boost > 0.0) {
                $event->setBaseDamage($event->getBaseDamage() * (1.0 + $boost));
            }
        }

        // --- Damage reduction (victim) --------------------------------------
        if ($victim instanceof Player) {
            $reduction = $this->armorManager->getDamageReduction($victim);
            if ($reduction > 0.0) {
                $event->setBaseDamage($event->getBaseDamage() * (1.0 - $reduction));
            }
        }

        // --- Knockback resistance (victim) ----------------------------------
        if ($victim instanceof Player) {
            $kbResist = $this->armorManager->getKnockbackResistance($victim);
            if ($kbResist > 0.0) {
                $event->setKnockBack($event->getKnockBack() * (1.0 - $kbResist));
            }
        }

        // --- Lifesteal (attacker) -------------------------------------------
        if ($attacker instanceof Player && $victim instanceof Player) {
            $lifesteal = $this->armorManager->getLifesteal($attacker);
            if ($lifesteal > 0.0) {
                // Capture final damage at this point in the event chain
                $healAmount = $event->getFinalDamage() * $lifesteal;
                // Schedule 1-tick delay so the heal fires after damage is applied
                $this->plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(function () use ($attacker, $healAmount): void {
                        if ($attacker->isOnline()) {
                            $newHp = min($attacker->getMaxHealth(), $attacker->getHealth() + $healAmount);
                            $attacker->setHealth($newHp);
                        }
                    }),
                    1
                );
            }
        }
    }
}
