<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

/**
 * Enumeration of all possible perks an armor set can grant.
 * Float values represent percentages (0.0–1.0) or integer levels
 * depending on the perk type.
 */
enum ArmorPerk: string {
    /** Percentage (0.0–1.0) of incoming damage absorbed before vanilla armor math */
    case DAMAGE_REDUCTION = "damage_reduction";

    /** Percentage (0.0+) by which outgoing melee damage is multiplied upward */
    case DAMAGE_BOOST = "damage_boost";

    /** Percentage (0.0–1.0) reduction to knockback received */
    case KNOCKBACK_RESISTANCE = "knockback_resistance";

    /** Speed effect amplifier level (1 = Speed I, 2 = Speed II …) */
    case SPEED = "speed";

    /** Regeneration effect amplifier level */
    case REGENERATION = "regeneration";

    /** Fire Resistance (any non-zero value enables it) */
    case FIRE_RESISTANCE = "fire_resistance";

    /** Percentage (0.0–1.0) of damage dealt returned as health to the attacker */
    case LIFESTEAL = "lifesteal";

    /** Strength effect amplifier level */
    case STRENGTH = "strength";
}
