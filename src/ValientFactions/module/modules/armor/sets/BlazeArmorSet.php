<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Blaze Armor Set – forged in the Nether.
 *
 * Focused on raw offensive power. Wearing pieces ignites your attacks and
 * makes you immune to fire while dealing crushing bonus damage.
 *
 * 2-piece: +15% damage
 * 4-piece: +25% damage, Strength I, Fire Resistance
 */
final class BlazeArmorSet extends ArmorSet {

    public function getName(): string {
        return "Blaze";
    }

    public function getDescription(): string {
        return "Forged in the Nether – burns with unstoppable fury";
    }

    public function getColor(): Color {
        return new Color(220, 70, 0); // deep orange
    }

    public function getLoreColor(): string {
        return TF::GOLD;
    }

    public function getPerksForPieces(int $pieces): array {
        if ($pieces >= 4) {
            return [
                ArmorPerk::DAMAGE_BOOST->value    => 0.25,
                ArmorPerk::STRENGTH->value         => 1.0,
                ArmorPerk::FIRE_RESISTANCE->value  => 1.0,
            ];
        }
        if ($pieces >= 2) {
            return [
                ArmorPerk::DAMAGE_BOOST->value => 0.15,
            ];
        }
        return [];
    }
}
