<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetTrigger;
use ValientFactions\module\modules\armor\TriggerContext;
use ValientFactions\module\modules\armor\TriggerType;

/**
 * Storm Active Trigger – Thunder Dash
 *
 * Fires automatically when the Storm wearer kills another player (ON_KILL).
 * Launches the wearer in their looking direction at extreme speed and applies
 * a short Speed III + Jump Boost II burst – the perfect post-kill surge.
 *
 * Cooldown: 20 seconds.
 */
final class ThunderDashAbility implements SetTrigger {

    /** Forward momentum multiplier. */
    private const DASH_MULTIPLIER = 2.8;
    /** Upward kick to clear blocks. */
    private const UPWARD_BOOST = 0.4;
    /** Speed/jump burst duration in seconds. */
    private const BURST_SECONDS = 5;

    public function getName(): string {
        return "Thunder Dash";
    }

    public function getDescription(): string {
        return "On kill: launch forward + Speed III + Jump Boost II for " . self::BURST_SECONDS . "s";
    }

    public function getTriggerType(): TriggerType {
        return TriggerType::ON_KILL;
    }

    public function getCooldownTicks(): int {
        return 20 * 20; // 20 s
    }

    public function shouldFire(TriggerContext $ctx): bool {
        return true; // fires on every kill when cooldown allows
    }

    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string {
        // Forward dash
        $direction = $wearer->getDirectionVector()->multiply(self::DASH_MULTIPLIER);
        $wearer->setMotion($direction->withComponents(null, $direction->y + self::UPWARD_BOOST, null));

        // Speed + jump burst
        $duration = self::BURST_SECONDS * 20;
        $wearer->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(),      $duration, 2, false)); // Speed III
        $wearer->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), $duration, 1, false)); // Jump Boost II

        return TF::AQUA . "⚡ " . TF::GREEN . "Thunder Dash! Speed III for " . self::BURST_SECONDS . "s!";
    }
}
