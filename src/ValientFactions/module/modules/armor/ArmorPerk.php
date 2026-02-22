<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

/**
 * Enumeration of all possible perks an armor set can grant.
 *
 * Float values are either:
 *  - Percentage  (0.0 – 1.0)  for reduction / boost / chance perks
 *  - Integer level (1, 2, 3…) for potion-effect perks
 *
 * All perks are ONLY active when the player wears all 4 pieces of the same set.
 */
enum ArmorPerk: string {
    // -------------------------------------------------------------------------
    // Damage modifiers
    // -------------------------------------------------------------------------

    /** Fraction (0.0–1.0) of incoming damage absorbed before vanilla armor math. */
    case DAMAGE_REDUCTION = "damage_reduction";

    /** Fraction (0.0+) added to outgoing melee damage (e.g. 0.30 = +30%). */
    case DAMAGE_BOOST = "damage_boost";

    /** Fraction (0.0–1.0) of damage dealt reflected back to the attacker. */
    case THORNS = "thorns";

    // -------------------------------------------------------------------------
    // Knockback / movement
    // -------------------------------------------------------------------------

    /** Fraction (0.0–1.0) reduction to knockback received. */
    case KNOCKBACK_RESISTANCE = "knockback_resistance";

    // -------------------------------------------------------------------------
    // Environmental resistances
    // -------------------------------------------------------------------------

    /** Fraction (0.0–1.0) reduction to fall damage. */
    case FALL_DAMAGE_REDUCTION = "fall_damage_reduction";

    /** Fraction (0.0–1.0) reduction to explosion damage. */
    case EXPLOSION_RESISTANCE = "explosion_resistance";

    // -------------------------------------------------------------------------
    // On-hit debuffs applied to the enemy
    // -------------------------------------------------------------------------

    /**
     * Duration in ticks to apply Slowness I to a hit target.
     * Value is the tick duration (e.g. 40 = 2 s).
     */
    case SLOWNESS_ON_HIT = "slowness_on_hit";

    // -------------------------------------------------------------------------
    // Sustain
    // -------------------------------------------------------------------------

    /** Fraction (0.0–1.0) of damage dealt returned as health to the attacker. */
    case LIFESTEAL = "lifesteal";

    // -------------------------------------------------------------------------
    // Persistent potion effects (level = amplifier, where 1 = EffectI)
    // -------------------------------------------------------------------------

    /** Speed effect level (1 = Speed I, 2 = Speed II …). */
    case SPEED = "speed";

    /** Regeneration effect level. */
    case REGENERATION = "regeneration";

    /** Strength effect level. */
    case STRENGTH = "strength";

    /** Resistance effect level. */
    case RESISTANCE = "resistance";

    /** Jump Boost effect level. */
    case JUMP_BOOST = "jump_boost";

    /** Absorption effect level (yellow hearts). */
    case ABSORPTION = "absorption";

    // -------------------------------------------------------------------------
    // Boolean effects (value > 0 enables them)
    // -------------------------------------------------------------------------

    /** Fire Resistance (any non-zero value enables it). */
    case FIRE_RESISTANCE = "fire_resistance";
}
