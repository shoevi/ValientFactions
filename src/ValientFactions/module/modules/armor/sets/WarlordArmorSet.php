<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\BattleCryAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Warlord Armor Set – Hardened by a thousand battles.
 *
 * Passive perks: 45% damage reduction, 45% KB resist, 50% explosion resist,
 *                25% thorns, Resistance I, 60% fall reduction.
 *
 * Triggers (auto-fire):
 *  1. BattleCryAbility (ON_HIT_TAKEN, 50s CD) – upon taking a hit: Strength II
 *     + Resistance II for 8 s, Weakness + Slowness to nearby enemies.
 */
final class WarlordArmorSet extends ArmorSet {

    public function getName(): string {
        return "Warlord";
    }

    public function getDescription(): string {
        return "Hardened by a thousand battles – unbreakable and immovable";
    }

    public function getColor(): Color {
        return new Color(140, 110, 0); // dark gold
    }

    public function getLoreColor(): string {
        return TF::YELLOW;
    }

    public function getPerks(): array {
        return [
            ArmorPerk::DAMAGE_REDUCTION->value      => 0.45,
            ArmorPerk::KNOCKBACK_RESISTANCE->value   => 0.45,
            ArmorPerk::EXPLOSION_RESISTANCE->value   => 0.50,
            ArmorPerk::THORNS->value                 => 0.25,
            ArmorPerk::RESISTANCE->value             => 1.0,
            ArmorPerk::FALL_DAMAGE_REDUCTION->value  => 0.60,
        ];
    }

    public function getTriggers(): array {
        return [
            new BattleCryAbility(),
        ];
    }
}
