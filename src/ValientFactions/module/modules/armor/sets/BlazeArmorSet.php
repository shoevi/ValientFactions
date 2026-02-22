<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\InfernoNovaAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Blaze Armor Set – Forged in the Nether.
 *
 * An all-out offense set built for players who fight fire with fire.
 * The full 4-piece bonus grants massive damage amplification, Strength II,
 * and complete fire immunity.
 *
 * Passive combat trigger: On every successful hit there is a 35% chance to
 * ignite the target for 4 seconds.
 *
 * Active ability: Inferno Nova – erupts a ring of fire around the wearer.
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

    public function getPerks(): array {
        return [
            ArmorPerk::DAMAGE_BOOST->value       => 0.30,
            ArmorPerk::STRENGTH->value            => 2.0,
            ArmorPerk::FIRE_RESISTANCE->value     => 1.0,
            ArmorPerk::FALL_DAMAGE_REDUCTION->value => 0.15,
        ];
    }

    public function getAbility(): ?SetAbility {
        return new InfernoNovaAbility();
    }

    public function getCombatTriggerDescription(): ?string {
        return "35% chance on hit to ignite target for 4 seconds";
    }
}
