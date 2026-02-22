<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Void Armor Set – infused with void energy.
 *
 * Focused on sustain and survivability. The set absorbs incoming damage and
 * returns a portion of it to the wearer as health (lifesteal).
 *
 * 2-piece: 15% damage reduction
 * 4-piece: 30% damage reduction, 10% lifesteal, Regeneration I
 */
final class VoidArmorSet extends ArmorSet {

    public function getName(): string {
        return "Void";
    }

    public function getDescription(): string {
        return "Infused with void energy – absorbs attacks and heals";
    }

    public function getColor(): Color {
        return new Color(40, 0, 80); // deep purple
    }

    public function getLoreColor(): string {
        return TF::DARK_PURPLE;
    }

    public function getPerksForPieces(int $pieces): array {
        if ($pieces >= 4) {
            return [
                ArmorPerk::DAMAGE_REDUCTION->value => 0.30,
                ArmorPerk::LIFESTEAL->value         => 0.10,
                ArmorPerk::REGENERATION->value      => 1.0,
            ];
        }
        if ($pieces >= 2) {
            return [
                ArmorPerk::DAMAGE_REDUCTION->value => 0.15,
            ];
        }
        return [];
    }
}
