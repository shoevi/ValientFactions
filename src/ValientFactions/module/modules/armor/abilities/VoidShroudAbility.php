<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\abilities;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\SetTrigger;
use ValientFactions\module\modules\armor\TriggerContext;
use ValientFactions\module\modules\armor\TriggerType;

/**
 * Void Active Trigger – Void Shroud
 *
 * When the Void wearer's HP would drop to or below 35% of maximum after a hit,
 * the Void Shroud activates automatically (ON_HIT_TAKEN):
 *  – Cancels the incoming damage entirely (sets TriggerContext::$preventDamage)
 *  – Grants 3 seconds of server-side invulnerability
 *  – Heals 4 HP instantly
 *
 * Cooldown: 60 seconds.
 */
final class VoidShroudAbility implements SetTrigger {

    /** HP fraction threshold below which the shroud activates. */
    private const HP_THRESHOLD = 0.35;
    /** Invincibility duration in ticks. */
    private const INVINCIBILITY_TICKS = 3 * 20;
    /** Instant heal amount. */
    private const HEAL_AMOUNT = 4.0;

    public function getName(): string {
        return "Void Shroud";
    }

    public function getDescription(): string {
        return "Auto-activates when HP would drop below " . (int) (self::HP_THRESHOLD * 100) . "%: cancel hit + 3s invincibility + " . self::HEAL_AMOUNT . " HP";
    }

    public function getTriggerType(): TriggerType {
        return TriggerType::ON_HIT_TAKEN;
    }

    public function getCooldownTicks(): int {
        return 60 * 20; // 60 s
    }

    public function shouldFire(TriggerContext $ctx): bool {
        // Only fire when this hit would bring HP to or below the threshold
        return $ctx->getWearerHpFractionAfter() <= self::HP_THRESHOLD;
    }

    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string {
        // Cancel the incoming hit
        $ctx->preventDamage = true;

        // 3 s invincibility window
        $manager->grantInvincibility($wearer, self::INVINCIBILITY_TICKS);

        // Instant HP restore
        $newHp = min($wearer->getMaxHealth(), $wearer->getHealth() + self::HEAL_AMOUNT);
        $wearer->setHealth($newHp);

        return TF::DARK_PURPLE . "✦ " . TF::LIGHT_PURPLE . "Void Shroud absorbed the blow! " . TF::GRAY . "(3s invulnerable)";
    }
}
