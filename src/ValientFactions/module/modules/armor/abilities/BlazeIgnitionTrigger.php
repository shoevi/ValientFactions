<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\player\Player;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetTrigger;
use ValientFactions\module\modules\armor\TriggerContext;
use ValientFactions\module\modules\armor\TriggerType;

/**
 * Blaze Passive Trigger – Blaze Ignition
 *
 * Every time the Blaze wearer deals a hit, there is a 35% chance to ignite
 * the target for 4 seconds.  No cooldown (fires on every hit it procs).
 *
 * TriggerType: ON_HIT_DEALT
 */
final class BlazeIgnitionTrigger implements SetTrigger {

    private const CHANCE   = 35;   // percent
    private const DURATION = 4;    // seconds

    public function getName(): string {
        return "Blaze Ignition";
    }

    public function getDescription(): string {
        return self::CHANCE . "% chance on hit to ignite target for " . self::DURATION . "s";
    }

    public function getTriggerType(): TriggerType {
        return TriggerType::ON_HIT_DEALT;
    }

    public function getCooldownTicks(): int {
        return 0; // no cooldown – fires whenever the RNG procs
    }

    public function shouldFire(TriggerContext $ctx): bool {
        return $ctx->otherPlayer !== null && mt_rand(1, 100) <= self::CHANCE;
    }

    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string {
        $ctx->otherPlayer?->setOnFire(self::DURATION);
        return ""; // silent proc – no message to avoid spam
    }
}
