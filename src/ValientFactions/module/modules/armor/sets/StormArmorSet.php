<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\ThunderDashAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Storm Armor Set – Blessed by the sky.
 *
 * The premier mobility and pressure set. Blazing Speed III lets the wearer
 * dictate every engagement while the on-hit Slowness I drags enemies to a crawl.
 *
 * Passive combat trigger: On player kill, gain Speed III + Jump Boost II for 5 s
 * ("Killing Spree").
 *
 * Active ability: Thunder Dash – launch forward at extreme speed.
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
            ArmorPerk::SLOWNESS_ON_HIT->value      => 60.0,  // 3 s Slowness I on target
            ArmorPerk::JUMP_BOOST->value           => 2.0,
        ];
    }

    public function getAbility(): ?SetAbility {
        return new ThunderDashAbility();
    }

    public function getCombatTriggerDescription(): ?string {
        return "On kill: Speed III + Jump Boost II for 5s (Killing Spree)";
    }
}
