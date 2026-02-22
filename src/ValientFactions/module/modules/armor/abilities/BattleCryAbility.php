<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetTrigger;
use ValientFactions\module\modules\armor\TriggerContext;
use ValientFactions\module\modules\armor\TriggerType;

/**
 * Warlord Active Trigger – Battle Cry
 *
 * Fires automatically when the Warlord wearer takes a hit (ON_HIT_TAKEN) and
 * the 50-second cooldown has expired.
 *  – Grants Strength II + Resistance II to the wearer for 8 seconds
 *  – Applies Weakness I + Slowness I to every enemy within 6 blocks for 8 seconds
 *
 * Cooldown: 50 seconds.
 */
final class BattleCryAbility implements SetTrigger {

    /** Self-buff and debuff duration in seconds. */
    private const EFFECT_SECONDS = 8;
    /** Debuff radius in blocks. */
    private const RADIUS = 6.0;

    public function getName(): string {
        return "Battle Cry";
    }

    public function getDescription(): string {
        return "On taking a hit: Strength II + Resistance II (" . self::EFFECT_SECONDS . "s) · Weaken nearby foes";
    }

    public function getTriggerType(): TriggerType {
        return TriggerType::ON_HIT_TAKEN;
    }

    public function getCooldownTicks(): int {
        return 50 * 20; // 50 s
    }

    public function shouldFire(TriggerContext $ctx): bool {
        return true; // fires whenever cooldown allows
    }

    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string {
        $duration = self::EFFECT_SECONDS * 20;

        // Self-buffs
        $wearer->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(),   $duration, 1, false)); // Strength II
        $wearer->getEffects()->add(new EffectInstance(VanillaEffects::RESISTANCE(), $duration, 1, false)); // Resistance II

        // Debuff nearby enemies
        $pos    = $wearer->getPosition();
        $r      = self::RADIUS;
        $bb     = new AxisAlignedBB(
            $pos->x - $r, $pos->y - $r, $pos->z - $r,
            $pos->x + $r, $pos->y + $r, $pos->z + $r
        );

        $debuffed = 0;
        foreach ($wearer->getWorld()->getNearbyEntities($bb, $wearer) as $entity) {
            if (!$entity instanceof Player || !$entity->isAlive()) {
                continue;
            }
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::WEAKNESS(), $duration, 0, false));
            $entity->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), $duration, 0, false));
            $entity->sendMessage(TF::YELLOW . "⚔ " . TF::GRAY . "Weakened by " . $wearer->getName() . "'s Battle Cry!");
            $debuffed++;
        }

        $msg = TF::YELLOW . "⚔ Battle Cry! " . TF::GOLD . "Strength II + Resistance II for " . self::EFFECT_SECONDS . "s";
        if ($debuffed > 0) {
            $msg .= TF::GRAY . " · Debuffed " . $debuffed . " enemies";
        }
        return $msg;
    }
}
