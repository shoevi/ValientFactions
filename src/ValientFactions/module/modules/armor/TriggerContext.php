<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\player\Player;

/**
 * Carries all contextual data for a trigger dispatch.
 *
 * Triggers may read these fields in {@see SetTrigger::shouldFire()} to decide
 * whether to activate, and may write {@see $preventDamage} to cancel incoming
 * damage entirely.
 */
final class TriggerContext {

    /**
     * Set to true by a trigger to signal that the current damage event should
     * be cancelled (e.g. Void Shield absorbing a killing blow).
     */
    public bool $preventDamage = false;

    /**
     * @param TriggerType $type          The event type that fired this trigger.
     * @param Player      $wearer        The player wearing the armor set.
     * @param Player|null $otherPlayer   The other party involved (attacker or victim), if any.
     * @param float       $damage        The damage value relevant to the event (final damage before perk mods).
     * @param float       $wearerHpBefore Current HP of the wearer BEFORE this damage is applied.
     * @param float       $wearerMaxHp   Maximum HP of the wearer.
     */
    public function __construct(
        public readonly TriggerType $type,
        public readonly Player      $wearer,
        public readonly ?Player     $otherPlayer  = null,
        public readonly float       $damage       = 0.0,
        public readonly float       $wearerHpBefore = 0.0,
        public readonly float       $wearerMaxHp  = 0.0,
    ) {}

    /**
     * Predicted HP after the damage is applied (clamped to 0.0).
     */
    public function getWearerHpAfter(): float {
        return max(0.0, $this->wearerHpBefore - $this->damage);
    }

    /**
     * Predicted HP fraction (0.0–1.0) after the damage.
     * Returns 1.0 when maxHp is unknown (avoids division by zero).
     */
    public function getWearerHpFractionAfter(): float {
        if ($this->wearerMaxHp <= 0.0) {
            return 1.0;
        }
        return $this->getWearerHpAfter() / $this->wearerMaxHp;
    }

    /**
     * Current HP fraction (0.0–1.0) BEFORE damage.
     */
    public function getWearerHpFractionBefore(): float {
        if ($this->wearerMaxHp <= 0.0) {
            return 1.0;
        }
        return $this->wearerHpBefore / $this->wearerMaxHp;
    }
}
