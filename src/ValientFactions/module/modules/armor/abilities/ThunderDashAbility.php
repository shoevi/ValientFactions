<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Storm Set Active Ability – Thunder Dash
 *
 * Propels the wearer in their current looking direction at extreme speed.
 * Useful for closing gaps or escaping.  Cooldown: 20 seconds.
 */
final class ThunderDashAbility implements SetAbility {

    public function getName(): string {
        return "Thunder Dash";
    }

    public function getDescription(): string {
        return "Launch yourself forward at lightning speed";
    }

    public function getCooldownTicks(): int {
        return 20 * 20; // 20 s
    }

    public function execute(Player $player, ArmorManager $manager): string {
        // Apply a strong forward motion vector in the player's looking direction
        $direction = $player->getDirectionVector()->multiply(2.8);
        // Add a slight upward kick so the player clears blocks
        $player->setMotion($direction->withComponents(null, $direction->y + 0.4, null));

        return TF::AQUA . "⚡ Thunder Dash activated!";
    }
}
