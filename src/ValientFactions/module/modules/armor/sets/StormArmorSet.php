<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Storm Armor Set – blessed by the sky.
 *
 * Focused on mobility and burst damage. The wearer moves like lightning and
 * strikes harder with every hit.
 *
 * 2-piece: Speed I, +10% damage
 * 4-piece: Speed II, +20% damage, 10% knockback resistance
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

    public function getPerksForPieces(int $pieces): array {
        if ($pieces >= 4) {
            return [
                ArmorPerk::SPEED->value               => 2.0,
                ArmorPerk::DAMAGE_BOOST->value        => 0.20,
                ArmorPerk::KNOCKBACK_RESISTANCE->value => 0.10,
            ];
        }
        if ($pieces >= 2) {
            return [
                ArmorPerk::SPEED->value        => 1.0,
                ArmorPerk::DAMAGE_BOOST->value => 0.10,
            ];
        }
        return [];
    }
}
