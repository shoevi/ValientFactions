<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

/**
 * Enumerates every event type that can auto-fire a {@see SetTrigger}.
 *
 * ON_HIT_DEALT  – fired when the wearer successfully deals melee damage
 * ON_HIT_TAKEN  – fired when the wearer receives any damage
 * ON_KILL       – fired when the wearer kills another player
 * ON_LOW_HP     – fired periodically (every 5 s) when wearer HP ≤ set threshold
 * ON_EQUIP      – fired once when the full 4-piece set is first equipped
 * ON_COMMAND    – manually activated via /armor ability
 * ON_CUSTOM     – extensibility hook for server-specific trigger logic
 */
enum TriggerType: string {
    case ON_HIT_DEALT = "on_hit_dealt";
    case ON_HIT_TAKEN = "on_hit_taken";
    case ON_KILL      = "on_kill";
    case ON_LOW_HP    = "on_low_hp";
    case ON_EQUIP     = "on_equip";
    case ON_COMMAND   = "on_command";
    case ON_CUSTOM    = "on_custom";

    /** Human-readable label for display. */
    public function label(): string {
        return match ($this) {
            self::ON_HIT_DEALT => "On Hit Dealt",
            self::ON_HIT_TAKEN => "On Hit Taken",
            self::ON_KILL      => "On Kill",
            self::ON_LOW_HP    => "On Low HP",
            self::ON_EQUIP     => "On Equip",
            self::ON_COMMAND   => "Manual (/armor ability)",
            self::ON_CUSTOM    => "Custom",
        };
    }

    /** Compact icon used in actionbar and lore. */
    public function icon(): string {
        return match ($this) {
            self::ON_HIT_DEALT => "⚔",
            self::ON_HIT_TAKEN => "🛡",
            self::ON_KILL      => "☠",
            self::ON_LOW_HP    => "❤",
            self::ON_EQUIP     => "✦",
            self::ON_COMMAND   => "★",
            self::ON_CUSTOM    => "✿",
        };
    }
}
