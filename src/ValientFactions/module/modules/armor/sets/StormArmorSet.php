<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\ThunderDashAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Storm Armor Set – Blessed by the sky.
 *
 * Passive perks: Speed III, +25% damage, 15% KB resist, Slowness-on-hit, Jump Boost II.
 *
 * Triggers (auto-fire):
 *  1. ThunderDashAbility (ON_KILL, 20s CD) – on player kill, launch forward
 *     and gain Speed III + Jump Boost II for 5 seconds.
 */
final class StormArmorSet extends ArmorSet {

    public function getName(): string {
        return "Storm";
    }

    public function getDescription(): string {
        return "Blessed by the sky – swift and deadly as lightning";
    }

    public function getColor(): Color {
        return new Color(0, 140, 220); // electric blue
    }

    public function getLoreColor(): string {
        return TF::AQUA;
    }

    public function getPerks(): array {
        return [
            ArmorPerk::SPEED->value               => 3.0,
            ArmorPerk::DAMAGE_BOOST->value         => 0.25,
            ArmorPerk::KNOCKBACK_RESISTANCE->value => 0.15,
            ArmorPerk::SLOWNESS_ON_HIT->value      => 60.0, // 3 s Slowness I on target
            ArmorPerk::JUMP_BOOST->value           => 2.0,
        ];
    }

    public function getTriggers(): array {
        return [
            new ThunderDashAbility(),
        ];
    }
}
