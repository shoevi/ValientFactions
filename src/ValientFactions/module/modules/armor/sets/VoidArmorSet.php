<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\VoidShroudAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Void Armor Set – Infused with void energy.
 *
 * Passive perks: 35% damage reduction, 15% lifesteal, Regen II, Absorption I.
 *
 * Triggers (auto-fire):
 *  1. VoidShroudAbility (ON_HIT_TAKEN, 60s CD) – auto-absorbs a hit when HP < 35%:
 *     cancels damage, grants 3s invulnerability, heals 4 HP.
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

    public function getPerks(): array {
        return [
            ArmorPerk::DAMAGE_REDUCTION->value => 0.35,
            ArmorPerk::LIFESTEAL->value         => 0.15,
            ArmorPerk::REGENERATION->value      => 2.0,
            ArmorPerk::ABSORPTION->value        => 1.0,
        ];
    }

    public function getTriggers(): array {
        return [
            new VoidShroudAbility(),
        ];
    }
}
