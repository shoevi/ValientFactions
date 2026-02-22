<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\player\Player;

/**
 * Contract for an armor set's active ability.
 *
 * Each {@see ArmorSet} can return one SetAbility via {@see ArmorSet::getAbility()}.
 * Abilities are activated by the player via the /vfability command and are gated
 * by a per-player cooldown tracked in {@see ArmorManager}.
 */
interface SetAbility {

    /** Display name shown to the player. */
    public function getName(): string;

    /** One-line description of what the ability does. */
    public function getDescription(): string;

    /** Cooldown in ticks before the ability can be used again. */
    public function getCooldownTicks(): int;

    /**
     * Execute the ability for $player.
     *
     * @param Player      $player  The player activating the ability.
     * @param ArmorManager $manager The active armor manager instance.
     * @return string A feedback message sent to the player (may be empty).
     */
    public function execute(Player $player, ArmorManager $manager): string;
}
