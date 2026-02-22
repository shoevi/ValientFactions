<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Warlord Armor Set – hardened by a thousand battles.
 *
 * The ultimate tank set. The wearer shrugs off tremendous damage and cannot
 * be easily moved by knockback.
 *
 * 2-piece: 20% damage reduction, 20% knockback resistance
 * 4-piece: 40% damage reduction, 40% knockback resistance
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

    public function getPerksForPieces(int $pieces): array {
        if ($pieces >= 4) {
            return [
                ArmorPerk::DAMAGE_REDUCTION->value    => 0.40,
                ArmorPerk::KNOCKBACK_RESISTANCE->value => 0.40,
            ];
        }
        if ($pieces >= 2) {
            return [
                ArmorPerk::DAMAGE_REDUCTION->value    => 0.20,
                ArmorPerk::KNOCKBACK_RESISTANCE->value => 0.20,
            ];
        }
        return [];
    }
}
