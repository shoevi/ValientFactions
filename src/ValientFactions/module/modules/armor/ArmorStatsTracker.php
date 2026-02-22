<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\player\Player;

/**
 * Tracks per-player combat statistics accumulated while wearing a custom armor set.
 * Data is session-only (cleared on disconnect / plugin disable).
 */
final class ArmorStatsTracker {

    // Stat key constants
    public const DAMAGE_DEALT_BOOSTED = "damage_dealt_boosted";
    public const DAMAGE_ABSORBED      = "damage_absorbed";
    public const LIFESTEAL_HEALED     = "lifesteal_healed";
    public const THORNS_REFLECTED     = "thorns_reflected";
    public const ABILITY_USES         = "ability_uses";
    public const COMBAT_TRIGGERS      = "combat_triggers";
    public const KILLS_WITH_SET       = "kills_with_set";
    public const HITS_TAKEN           = "hits_taken";
    public const HITS_DEALT           = "hits_dealt";

    /** @var array<string, array<string, float>> UUID → [stat → value] */
    private array $data = [];

    /** Add $amount to a stat for a player. */
    public function increment(Player $player, string $stat, float $amount = 1.0): void {
        $uuid = $player->getUniqueId()->toString();
        $this->data[$uuid][$stat] = ($this->data[$uuid][$stat] ?? 0.0) + $amount;
    }

    /** Get the current value of a stat for a player. */
    public function get(Player $player, string $stat): float {
        return $this->data[$player->getUniqueId()->toString()][$stat] ?? 0.0;
    }

    /**
     * Get all stats for a player as an associative array.
     * @return array<string, float>
     */
    public function getAllStats(Player $player): array {
        return $this->data[$player->getUniqueId()->toString()] ?? [];
    }

    /** Reset all stats for a player. */
    public function reset(Player $player): void {
        unset($this->data[$player->getUniqueId()->toString()]);
    }
}
