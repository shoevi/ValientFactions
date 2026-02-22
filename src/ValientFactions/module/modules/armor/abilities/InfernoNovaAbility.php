<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Blaze Set Active Ability – Inferno Nova
 *
 * Erupts a ring of fire, igniting every enemy within 8 blocks for 4 seconds
 * and dealing 5 damage to each.  Cooldown: 45 seconds.
 */
final class InfernoNovaAbility implements SetAbility {

    /** Minimum HP left on a living entity after taking direct damage (prevents insta-kill). */
    private const MIN_ENTITY_HEALTH = 0.5;
    /** Damage dealt per target hit by Inferno Nova. */
    private const HIT_DAMAGE = 5.0;
    /** Fire duration in seconds per target. */
    private const FIRE_SECONDS = 4;

    public function getName(): string {
        return "Inferno Nova";
    }

    public function getDescription(): string {
        return "Ignite all enemies within 8 blocks (4s fire + 5 dmg each)";
    }

    public function getCooldownTicks(): int {
        return 45 * 20; // 45 s
    }

    public function execute(Player $player, ArmorManager $manager): string {
        $pos    = $player->getPosition();
        $radius = 8.0;
        $bb     = new AxisAlignedBB(
            $pos->x - $radius, $pos->y - $radius, $pos->z - $radius,
            $pos->x + $radius, $pos->y + $radius, $pos->z + $radius
        );

        $hits = 0;
        foreach ($player->getWorld()->getNearbyEntities($bb, $player) as $entity) {
            if (!$entity instanceof Player || !$entity->isAlive()) {
                continue;
            }

            $entity->setOnFire(self::FIRE_SECONDS);
            // Apply direct damage (not through event to avoid recursion with other perks)
            $newHp = max(self::MIN_ENTITY_HEALTH, $entity->getHealth() - self::HIT_DAMAGE);
            $entity->setHealth($newHp);
            $entity->sendMessage(TF::GOLD . "☄ " . TF::RED . "You were hit by " . $player->getName() . "'s Inferno Nova!");
            $hits++;
        }

        if ($hits === 0) {
            return TF::RED . "No enemies in range!";
        }
        return TF::GOLD . "☄ Inferno Nova struck " . TF::RED . $hits . TF::GOLD . " enemies!";
    }
}
