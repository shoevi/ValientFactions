<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use ValientFactions\Main;

/**
 * Core logic for the armor system.
 *
 * Responsibilities:
 *  - Detect which set (and how many pieces) each player is currently wearing.
 *  - Calculate stat modifiers (damage reduction, damage boost, lifesteal, KB resist).
 *  - Apply or refresh persistent potion effects granted by a set.
 *  - Give full / individual armor pieces to players.
 */
final class ArmorManager {

    private Main $plugin;
    private ArmorSetRegistry $registry;

    public function __construct(Main $plugin, ArmorSetRegistry $registry) {
        $this->plugin   = $plugin;
        $this->registry = $registry;
    }

    public function getRegistry(): ArmorSetRegistry {
        return $this->registry;
    }

    // -------------------------------------------------------------------------
    // Set detection
    // -------------------------------------------------------------------------

    /**
     * Returns [ArmorSet, int $pieces] for the dominant set the player is wearing,
     * or null if they are not wearing any custom armor.
     *
     * "Dominant" means the set with the highest piece count.
     *
     * @return array{ArmorSet, int}|null
     */
    public function getPlayerSetInfo(Player $player): ?array {
        $armorInv = $player->getArmorInventory();
        $counts   = [];

        foreach ([
            $armorInv->getHelmet(),
            $armorInv->getChestplate(),
            $armorInv->getLeggings(),
            $armorInv->getBoots(),
        ] as $item) {
            $set = $this->registry->getSetFromItem($item);
            if ($set !== null) {
                $counts[$set->getName()] = ($counts[$set->getName()] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return null;
        }

        // Pick the set with the most pieces equipped
        arsort($counts);
        $topName = (string) array_key_first($counts);
        $topSet  = $this->registry->get($topName);

        return $topSet !== null ? [$topSet, $counts[$topName]] : null;
    }

    // -------------------------------------------------------------------------
    // Stat accessors (used by ArmorListener)
    // -------------------------------------------------------------------------

    /** Incoming damage multiplier reduction, e.g. 0.30 = 30 % less damage taken. */
    public function getDamageReduction(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::DAMAGE_REDUCTION);
    }

    /** Outgoing damage multiplier boost, e.g. 0.20 = 20 % more damage dealt. */
    public function getDamageBoost(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::DAMAGE_BOOST);
    }

    /** Knockback reduction multiplier, e.g. 0.40 = 40 % less KB received. */
    public function getKnockbackResistance(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::KNOCKBACK_RESISTANCE);
    }

    /** Lifesteal fraction, e.g. 0.10 = heal 10 % of damage dealt. */
    public function getLifesteal(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::LIFESTEAL);
    }

    // -------------------------------------------------------------------------
    // Effect management (called by ArmorEffectTask)
    // -------------------------------------------------------------------------

    /**
     * Re-apply every persistent potion effect granted by the player's active set.
     * Duration is set to 200 ticks (10 s) so it refreshes before expiry when the
     * task runs every 100 ticks (5 s).
     */
    public function refreshEffects(Player $player): void {
        $info = $this->getPlayerSetInfo($player);
        if ($info === null) {
            return;
        }

        [$set, $pieces] = $info;
        $perks = $set->getPerksForPieces($pieces);

        foreach ($perks as $perkValue => $amount) {
            $perk = ArmorPerk::from($perkValue);
            $this->applyPerkEffect($player, $perk, $amount);
        }
    }

    private function applyPerkEffect(Player $player, ArmorPerk $perk, float $amount): void {
        $duration = 200; // ticks; refreshed before expiry by the task

        match ($perk) {
            ArmorPerk::SPEED => $player->getEffects()->add(
                new EffectInstance(VanillaEffects::SPEED(), $duration, max(0, (int) $amount - 1), false)
            ),
            ArmorPerk::REGENERATION => $player->getEffects()->add(
                new EffectInstance(VanillaEffects::REGENERATION(), $duration, max(0, (int) $amount - 1), false)
            ),
            ArmorPerk::FIRE_RESISTANCE => $player->getEffects()->add(
                new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), $duration, 0, false)
            ),
            ArmorPerk::STRENGTH => $player->getEffects()->add(
                new EffectInstance(VanillaEffects::STRENGTH(), $duration, max(0, (int) $amount - 1), false)
            ),
            // Event-handled perks – no potion effect needed
            default => null,
        };
    }

    // -------------------------------------------------------------------------
    // Item / inventory helpers
    // -------------------------------------------------------------------------

    /** Equip every piece of the given set directly into the player's armor slots. */
    public function giveFullSet(Player $player, ArmorSet $set): void {
        $inv = $player->getArmorInventory();
        $inv->setHelmet($set->getItemForSlot(ArmorSet::SLOT_HELMET));
        $inv->setChestplate($set->getItemForSlot(ArmorSet::SLOT_CHESTPLATE));
        $inv->setLeggings($set->getItemForSlot(ArmorSet::SLOT_LEGGINGS));
        $inv->setBoots($set->getItemForSlot(ArmorSet::SLOT_BOOTS));
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function getPerkValue(Player $player, ArmorPerk $perk): float {
        $info = $this->getPlayerSetInfo($player);
        if ($info === null) {
            return 0.0;
        }
        [$set, $pieces] = $info;
        return (float) ($set->getPerksForPieces($pieces)[$perk->value] ?? 0.0);
    }
}
