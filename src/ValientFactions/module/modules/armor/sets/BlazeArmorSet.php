<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\BlazeIgnitionTrigger;
use ValientFactions\module\modules\armor\abilities\InfernoNovaAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * Blaze Armor Set – Forged in the Nether.
 *
 * Passive perks: +30% damage, Strength II, Fire Resistance, 15% fall reduction.
 *
 * Triggers (auto-fire):
 *  1. BlazeIgnitionTrigger (ON_HIT_DEALT, no CD) – 35% chance to ignite target
 *  2. InfernoNovaAbility   (ON_HIT_DEALT, 45s CD) – ring of fire when cooldown ready
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
            ArmorPerk::DAMAGE_BOOST->value          => 0.30,
            ArmorPerk::STRENGTH->value               => 2.0,
            ArmorPerk::FIRE_RESISTANCE->value        => 1.0,
            ArmorPerk::FALL_DAMAGE_REDUCTION->value  => 0.15,
        ];
    }

    public function getTriggers(): array {
        return [
            new BlazeIgnitionTrigger(),
            new InfernoNovaAbility(),
        ];
    }
}
