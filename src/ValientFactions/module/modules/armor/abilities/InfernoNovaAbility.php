<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetTrigger;
use ValientFactions\module\modules\armor\TriggerContext;
use ValientFactions\module\modules\armor\TriggerType;

/**
 * Blaze Active Trigger – Inferno Nova
 *
 * When the Blaze wearer deals a hit and the 45-second cooldown has expired,
 * erupts a ring of fire that ignites every enemy within 8 blocks for 4 seconds
 * and deals 5 direct damage each.
 *
 * TriggerType: ON_HIT_DEALT  (auto-fires on hit when cooldown is ready)
 */
final class InfernoNovaAbility implements SetTrigger {

    /** Minimum HP left on a target after taking direct Inferno Nova damage. */
    private const MIN_ENTITY_HEALTH = 0.5;
    /** Damage dealt per target hit by Inferno Nova. */
    private const HIT_DAMAGE = 5.0;
    /** Fire duration in seconds applied to each target. */
    private const FIRE_SECONDS = 4;
    /** Blast radius in blocks. */
    private const RADIUS = 8.0;

    public function getName(): string {
        return "Inferno Nova";
    }

    public function getDescription(): string {
        return "Ignite all enemies within " . self::RADIUS . " blocks (" . self::FIRE_SECONDS . "s fire + " . self::HIT_DAMAGE . " dmg each)";
    }

    public function getTriggerType(): TriggerType {
        return TriggerType::ON_HIT_DEALT;
    }

    public function getCooldownTicks(): int {
        return 45 * 20; // 45 s
    }

    public function shouldFire(TriggerContext $ctx): bool {
        return true; // fires whenever the cooldown allows
    }

    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string {
        $pos = $wearer->getPosition();
        $r   = self::RADIUS;
        $bb  = new AxisAlignedBB(
            $pos->x - $r, $pos->y - $r, $pos->z - $r,
            $pos->x + $r, $pos->y + $r, $pos->z + $r
        );

        $hits = 0;
        foreach ($wearer->getWorld()->getNearbyEntities($bb, $wearer) as $entity) {
            if (!$entity instanceof Player || !$entity->isAlive()) {
                continue;
            }
            $entity->setOnFire(self::FIRE_SECONDS);
            $newHp = max(self::MIN_ENTITY_HEALTH, $entity->getHealth() - self::HIT_DAMAGE);
            $entity->setHealth($newHp);
            $entity->sendMessage(TF::GOLD . "☄ " . TF::RED . "You were struck by " . $wearer->getName() . "'s Inferno Nova!");
            $hits++;
        }

        if ($hits === 0) {
            return TF::RED . "Inferno Nova – no enemies in range!";
        }
        return TF::GOLD . "☄ Inferno Nova struck " . TF::RED . $hits . TF::GOLD . " enemies!";
    }
}
