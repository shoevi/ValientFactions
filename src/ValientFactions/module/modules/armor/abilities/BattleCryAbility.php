<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Warlord Set Active Ability – Battle Cry
 *
 * Lets out a terrifying battle cry, granting the wearer Strength II + Resistance II
 * for 8 seconds while applying Weakness I + Slowness I to all enemies within 6 blocks.
 * Cooldown: 50 seconds.
 */
final class BattleCryAbility implements SetAbility {

    public function getName(): string {
        return "Battle Cry";
    }

    public function getDescription(): string {
        return "Strength II + Resistance II (8s) · Weakness + Slowness to nearby foes";
    }

    public function getCooldownTicks(): int {
        return 50 * 20; // 50 s
    }

    public function execute(Player $player, ArmorManager $manager): string {
        $duration = 8 * 20; // 8 s in ticks

        // Self-buffs
        $player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(),   $duration, 1, false)); // Strength II
        $player->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), $duration, 1, false)); // Resistance II

        // Debuff nearby enemies
        $pos    = $player->getPosition();
        $radius = 6.0;
        $bb     = new AxisAlignedBB(
            $pos->x - $radius, $pos->y - $radius, $pos->z - $radius,
            $pos->x + $radius, $pos->y + $radius, $pos->z + $radius
        );

        $debuffed = 0;
        foreach ($player->getWorld()->getNearbyEntities($bb, $player) as $entity) {
            if (!$entity instanceof Player || !$entity->isAlive()) {
                continue;
            }
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::WEAKNESS(),  $duration, 0, false)); // Weakness I
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(),  $duration, 0, false)); // Slowness I
            $entity->sendMessage(TF::YELLOW . "⚔ You have been weakened by " . $player->getName() . "'s Battle Cry!");
            $debuffed++;
        }

        $msg = TF::YELLOW . "⚔ Battle Cry! §6Strength II §e+ §6Resistance II §efor §f8s";
        if ($debuffed > 0) {
            $msg .= TF::GRAY . " · Debuffed " . $debuffed . " enemies";
        }
        return $msg;
    }
}
