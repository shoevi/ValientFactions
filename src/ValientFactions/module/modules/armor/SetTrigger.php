<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\player\Player;

/**
 * Contract for an armor set trigger.
 *
 * A trigger is a piece of logic attached to a specific {@see TriggerType}.
 * When the armor system fires an event of that type for a player wearing the
 * matching set, it:
 *  1. Checks the per-player cooldown.
 *  2. Calls {@see shouldFire()} for any extra condition logic.
 *  3. Calls {@see execute()} and sends the returned message to the player.
 *
 * Triggers replace the old SetAbility interface.  The trigger type
 * determines HOW the trigger fires:
 *   – Passive types (ON_HIT_DEALT, ON_HIT_TAKEN, ON_KILL, ON_LOW_HP, ON_EQUIP)
 *     fire automatically when the event occurs.
 *   – ON_COMMAND fires manually via /armor ability.
 *   – ON_CUSTOM requires explicit dispatch by server-specific code.
 */
interface SetTrigger {

    /** Unique display name shown in lore and UI. */
    public function getName(): string;

    /** One-line description of what the trigger does. */
    public function getDescription(): string;

    /** The event type that activates this trigger. */
    public function getTriggerType(): TriggerType;

    /**
     * Cooldown in ticks between consecutive activations.
     * Return 0 for triggers that should fire every time conditions are met.
     */
    public function getCooldownTicks(): int;

    /**
     * Extra condition check called AFTER the cooldown passes.
     * Return false to skip this activation even if the cooldown is ready.
     *
     * @param TriggerContext $ctx Event context including HP, damage, and other party.
     */
    public function shouldFire(TriggerContext $ctx): bool;

    /**
     * Execute the trigger logic.
     *
     * May write to {@see TriggerContext::$preventDamage} to cancel the
     * current damage event.
     *
     * @param Player         $wearer  The player wearing the armor set.
     * @param ArmorManager   $manager The active armor manager.
     * @param TriggerContext $ctx     The event context.
     * @return string Feedback message sent to the player (empty = no message).
     */
    public function execute(Player $wearer, ArmorManager $manager, TriggerContext $ctx): string;
}
