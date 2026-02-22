<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Void Set Active Ability – Void Shroud
 *
 * Wraps the wearer in an impenetrable void shell for 3 seconds, making them
 * completely invulnerable to all damage and restoring 4 HP.  Cooldown: 60 s.
 */
final class VoidShroudAbility implements SetAbility {

    public function getName(): string {
        return "Void Shroud";
    }

    public function getDescription(): string {
        return "3s complete invulnerability + restore 4 HP";
    }

    public function getCooldownTicks(): int {
        return 60 * 20; // 60 s
    }

    public function execute(Player $player, ArmorManager $manager): string {
        // Grant 3 seconds of server-side invincibility
        $manager->grantInvincibility($player, 3 * 20);

        // Immediately heal 4 HP (capped at max)
        $newHp = min($player->getMaxHealth(), $player->getHealth() + 4.0);
        $player->setHealth($newHp);

        return TF::DARK_PURPLE . "✦ Void Shroud activated! " . TF::GRAY . "(3s invulnerability)";
    }
}
