<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use ValientFactions\Main;

/**
 * Core logic for the custom armor system.
 *
 * Key responsibilities:
 *  - Detect which (4/4) set a player is wearing and cache the result.
 *  - Expose passive perk values (damage reduction, boost, KB, lifesteal, etc.).
 *  - Manage per-player, per-trigger cooldowns.
 *  - Dispatch triggers ({@see SetTrigger}) when events occur.
 *  - Manage invincibility windows granted by the Void Shroud trigger.
 *  - Manage the thorns recursion guard.
 */
final class ArmorManager {

    private Main $plugin;
    private ArmorSetRegistry $registry;
    private ArmorStatsTracker $stats;

    // -------------------------------------------------------------------------
    // Per-player set cache  UUID → ?ArmorSet  (updated every 5 s by task)
    // -------------------------------------------------------------------------
    /** @var array<string, ArmorSet|null> */
    private array $setCache = [];

    /** @var array<string, string|null>  UUID → set name, for change detection */
    private array $prevSetName = [];

    // -------------------------------------------------------------------------
    // Per-trigger cooldowns:  "{UUID}|{triggerName}" → expiry server tick
    // -------------------------------------------------------------------------
    /** @var array<string, int> */
    private array $triggerCooldowns = [];

    // -------------------------------------------------------------------------
    // Invincibility pool (Void Shroud):  UUID → expiry server tick
    // -------------------------------------------------------------------------
    /** @var array<string, int> */
    private array $invincibleUntil = [];

    // -------------------------------------------------------------------------
    // Thorns recursion guard:  UUID → true while reflection is in-flight
    // -------------------------------------------------------------------------
    /** @var array<string, true> */
    private array $thornsGuard = [];

    // -------------------------------------------------------------------------

    public function __construct(Main $plugin, ArmorSetRegistry $registry, ArmorStatsTracker $stats) {
        $this->plugin   = $plugin;
        $this->registry = $registry;
        $this->stats    = $stats;
    }

    public function getRegistry(): ArmorSetRegistry {
        return $this->registry;
    }

    public function getStats(): ArmorStatsTracker {
        return $this->stats;
    }

    // =========================================================================
    // Set detection  (requires all 4 pieces)
    // =========================================================================

    /**
     * Returns [ArmorSet, int $pieces] for the dominant set the player is wearing.
     *
     * @return array{ArmorSet, int}|null  null = no custom armor at all
     */
    public function getPlayerSetInfo(Player $player): ?array {
        $armorInv = $player->getArmorInventory();
        $counts   = [];

        foreach ([
            $armorInv->getHelmet(),
            $armorInv->getChestplate(),
            $armorInv->getLeggings(),
            $armorInv->getBoots(),
        ] as $item) {
            $set = $this->registry->getSetFromItem($item);
            if ($set !== null) {
                $counts[$set->getName()] = ($counts[$set->getName()] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return null;
        }

        arsort($counts);
        $topName = (string) array_key_first($counts);
        $topSet  = $this->registry->get($topName);

        return $topSet !== null ? [$topSet, $counts[$topName]] : null;
    }

    /** Returns the active set only when all 4 pieces are worn; null otherwise. */
    public function getActiveSet(Player $player): ?ArmorSet {
        $info = $this->getPlayerSetInfo($player);
        if ($info === null || $info[1] !== 4) {
            return null;
        }
        return $info[0];
    }

    /** Returns the cached active set (updated every 5 s by ArmorEffectTask). */
    public function getCachedActiveSet(Player $player): ?ArmorSet {
        return $this->setCache[$player->getUniqueId()->toString()] ?? null;
    }

    /**
     * Refresh the set cache for one player.
     * @return bool true if the active set changed since the last call.
     */
    public function updateCache(Player $player): bool {
        $uuid     = $player->getUniqueId()->toString();
        $newSet   = $this->getActiveSet($player);
        $newName  = $newSet?->getName();
        $prevName = $this->prevSetName[$uuid] ?? "__unset__";

        $this->setCache[$uuid]    = $newSet;
        $this->prevSetName[$uuid] = $newName;

        return $newName !== $prevName;
    }

    /** Remove all cached state for a player (call on disconnect). */
    public function removeFromCache(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        unset($this->setCache[$uuid], $this->prevSetName[$uuid]);
        // Clean up trigger cooldown entries for this player
        $prefix = $uuid . "|";
        foreach (array_keys($this->triggerCooldowns) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->triggerCooldowns[$key]);
            }
        }
        unset($this->invincibleUntil[$uuid]);
    }

    // =========================================================================
    // Passive perk accessors  (zero unless 4/4 set is active)
    // =========================================================================

    public function getDamageReduction(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::DAMAGE_REDUCTION);
    }

    public function getDamageBoost(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::DAMAGE_BOOST);
    }

    public function getKnockbackResistance(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::KNOCKBACK_RESISTANCE);
    }

    public function getLifesteal(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::LIFESTEAL);
    }

    public function getThorns(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::THORNS);
    }

    public function getFallDamageReduction(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::FALL_DAMAGE_REDUCTION);
    }

    public function getExplosionResistance(Player $player): float {
        return $this->getPerkValue($player, ArmorPerk::EXPLOSION_RESISTANCE);
    }

    public function getSlownessOnHitTicks(Player $player): int {
        return (int) $this->getPerkValue($player, ArmorPerk::SLOWNESS_ON_HIT);
    }

    // =========================================================================
    // Potion effect refresh  (called by ArmorEffectTask every 5 s)
    // =========================================================================

    /**
     * Re-apply every persistent effect perk for a player wearing a full set.
     * Duration is 200 ticks (10 s) so effects stay active between 5-second refreshes.
     */
    public function refreshEffects(Player $player): void {
        $set = $this->getCachedActiveSet($player);
        if ($set === null) {
            return;
        }

        $perks    = $set->getPerks();
        $duration = 200;

        foreach ($perks as $perkValue => $amount) {
            $perk = ArmorPerk::from($perkValue);
            match ($perk) {
                ArmorPerk::SPEED => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::SPEED(), $duration, max(0, (int) $amount - 1), false)
                ),
                ArmorPerk::REGENERATION => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::REGENERATION(), $duration, max(0, (int) $amount - 1), false)
                ),
                ArmorPerk::FIRE_RESISTANCE => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), $duration, 0, false)
                ),
                ArmorPerk::STRENGTH => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::STRENGTH(), $duration, max(0, (int) $amount - 1), false)
                ),
                ArmorPerk::RESISTANCE => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::RESISTANCE(), $duration, max(0, (int) $amount - 1), false)
                ),
                ArmorPerk::JUMP_BOOST => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::JUMP_BOOST(), $duration, max(0, (int) $amount - 1), false)
                ),
                ArmorPerk::ABSORPTION => $player->getEffects()->add(
                    new EffectInstance(VanillaEffects::ABSORPTION(), $duration, max(0, (int) $amount - 1), false)
                ),
                default => null,
            };
        }
    }

    // =========================================================================
    // Trigger dispatch
    // =========================================================================

    /**
     * Dispatch all triggers on the player's active set that match the context's
     * TriggerType.  ON_COMMAND triggers are skipped here; use
     * {@see activateCommandTrigger()} for those.
     *
     * Each matching trigger is checked for:
     *  1. Cooldown (skipped if still cooling down)
     *  2. shouldFire() condition
     * If both pass, execute() is called, the cooldown is set, and the feedback
     * message is sent to the player as a tip.
     */
    public function fireTrigger(Player $player, TriggerContext $ctx): void {
        $set = $this->getCachedActiveSet($player) ?? $this->getActiveSet($player);
        if ($set === null) {
            return;
        }

        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();

        foreach ($set->getTriggers() as $trigger) {
            if ($trigger->getTriggerType() !== $ctx->type) {
                continue;
            }
            // ON_COMMAND triggers are activated manually only
            if ($ctx->type === TriggerType::ON_COMMAND) {
                continue;
            }

            $cdKey = $uuid . "|" . $trigger->getName();

            // Cooldown check
            if (($this->triggerCooldowns[$cdKey] ?? 0) > $now) {
                continue;
            }

            // Extra condition
            if (!$trigger->shouldFire($ctx)) {
                continue;
            }

            // Set cooldown
            if ($trigger->getCooldownTicks() > 0) {
                $this->triggerCooldowns[$cdKey] = $now + $trigger->getCooldownTicks();
            }

            // Execute
            $msg = $trigger->execute($player, $this, $ctx);
            if ($msg !== "") {
                $player->sendTip($msg);
            }

            $this->stats->increment($player, ArmorStatsTracker::COMBAT_TRIGGERS);
        }
    }

    /**
     * Fire the ON_EQUIP triggers for a player (called when the full set is
     * first detected by ArmorEffectTask).
     */
    public function fireEquipTriggers(Player $player): void {
        $set = $this->getCachedActiveSet($player);
        if ($set === null) {
            return;
        }

        $ctx  = new TriggerContext(TriggerType::ON_EQUIP, $player);
        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();

        foreach ($set->getTriggers() as $trigger) {
            if ($trigger->getTriggerType() !== TriggerType::ON_EQUIP) {
                continue;
            }
            $cdKey = $uuid . "|" . $trigger->getName();
            if (($this->triggerCooldowns[$cdKey] ?? 0) > $now) {
                continue;
            }
            if (!$trigger->shouldFire($ctx)) {
                continue;
            }
            if ($trigger->getCooldownTicks() > 0) {
                $this->triggerCooldowns[$cdKey] = $now + $trigger->getCooldownTicks();
            }
            $msg = $trigger->execute($player, $this, $ctx);
            if ($msg !== "") {
                $player->sendTip($msg);
            }
            $this->stats->increment($player, ArmorStatsTracker::COMBAT_TRIGGERS);
        }
    }

    /**
     * Fire the ON_LOW_HP triggers for a player.
     * Called by ArmorEffectTask every 5 s when the player's HP is low.
     */
    public function fireLowHpTriggers(Player $player): void {
        $set = $this->getCachedActiveSet($player);
        if ($set === null) {
            return;
        }

        $ctx  = new TriggerContext(
            TriggerType::ON_LOW_HP,
            $player,
            null,
            0.0,
            $player->getHealth(),
            $player->getMaxHealth()
        );
        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();

        foreach ($set->getTriggers() as $trigger) {
            if ($trigger->getTriggerType() !== TriggerType::ON_LOW_HP) {
                continue;
            }
            $cdKey = $uuid . "|" . $trigger->getName();
            if (($this->triggerCooldowns[$cdKey] ?? 0) > $now) {
                continue;
            }
            if (!$trigger->shouldFire($ctx)) {
                continue;
            }
            if ($trigger->getCooldownTicks() > 0) {
                $this->triggerCooldowns[$cdKey] = $now + $trigger->getCooldownTicks();
            }
            $msg = $trigger->execute($player, $this, $ctx);
            if ($msg !== "") {
                $player->sendTip($msg);
            }
            $this->stats->increment($player, ArmorStatsTracker::COMBAT_TRIGGERS);
        }
    }

    /**
     * Attempt to manually activate the first ON_COMMAND trigger on the player's set.
     *
     * @return array{bool, string}  [success, message]
     */
    public function activateCommandTrigger(Player $player): array {
        $set = $this->getCachedActiveSet($player) ?? $this->getActiveSet($player);

        if ($set === null) {
            return [false, "§cYou must wear a complete set (4/4 pieces) to use an ability!"];
        }

        $commandTriggers = array_filter(
            $set->getTriggers(),
            static fn(SetTrigger $t) => $t->getTriggerType() === TriggerType::ON_COMMAND
        );

        if (empty($commandTriggers)) {
            return [false, "§eAll abilities for this set activate automatically! No manual ability available."];
        }

        $trigger = reset($commandTriggers);
        $uuid    = $player->getUniqueId()->toString();
        $now     = $this->plugin->getServer()->getTick();
        $cdKey   = $uuid . "|" . $trigger->getName();

        if (($this->triggerCooldowns[$cdKey] ?? 0) > $now) {
            $remaining = (int) ceil(($this->triggerCooldowns[$cdKey] - $now) / 20);
            return [false, "§cAbility on cooldown! §7(" . $remaining . "s remaining)"];
        }

        $ctx = new TriggerContext(TriggerType::ON_COMMAND, $player);

        if (!$trigger->shouldFire($ctx)) {
            return [false, "§cConditions not met to activate this ability."];
        }

        if ($trigger->getCooldownTicks() > 0) {
            $this->triggerCooldowns[$cdKey] = $now + $trigger->getCooldownTicks();
        }

        $msg = $trigger->execute($player, $this, $ctx);
        $this->stats->increment($player, ArmorStatsTracker::ABILITY_USES);
        return [true, $msg];
    }

    // =========================================================================
    // Trigger cooldown info  (used by actionbar display)
    // =========================================================================

    /**
     * Returns the longest remaining cooldown and its trigger name for actionbar display.
     * Returns null when no cooldown-bearing trigger is equipped.
     *
     * @return array{string, int}|null  [trigger name, remaining ticks]
     */
    public function getPrimaryTriggerCooldown(Player $player): ?array {
        $set = $this->getCachedActiveSet($player);
        if ($set === null) {
            return null;
        }

        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();
        $best = null;

        foreach ($set->getTriggers() as $trigger) {
            if ($trigger->getCooldownTicks() <= 0) {
                continue;
            }
            $cdKey     = $uuid . "|" . $trigger->getName();
            $remaining = max(0, ($this->triggerCooldowns[$cdKey] ?? 0) - $now);
            if ($best === null || $remaining > $best[1]) {
                $best = [$trigger->getName(), $remaining];
            }
        }

        return $best;
    }

    // =========================================================================
    // Invincibility  (granted by Void Shroud and similar triggers)
    // =========================================================================

    /** Grant server-side invincibility for $durationTicks ticks. */
    public function grantInvincibility(Player $player, int $durationTicks): void {
        $uuid = $player->getUniqueId()->toString();
        $this->invincibleUntil[$uuid] = $this->plugin->getServer()->getTick() + $durationTicks;
    }

    /** Returns true if the player is currently invincible. */
    public function isInvincible(Player $player): bool {
        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();
        if (isset($this->invincibleUntil[$uuid]) && $this->invincibleUntil[$uuid] > $now) {
            return true;
        }
        unset($this->invincibleUntil[$uuid]);
        return false;
    }

    // =========================================================================
    // Thorns recursion guard
    // =========================================================================

    public function startThornsReflection(Player $player): void {
        $this->thornsGuard[$player->getUniqueId()->toString()] = true;
    }

    public function endThornsReflection(Player $player): void {
        unset($this->thornsGuard[$player->getUniqueId()->toString()]);
    }

    public function isThornsReflecting(Player $player): bool {
        return isset($this->thornsGuard[$player->getUniqueId()->toString()]);
    }

    // =========================================================================
    // Item helpers
    // =========================================================================

    /** Equip every piece of the given set directly into the player's armor slots. */
    public function giveFullSet(Player $player, ArmorSet $set): void {
        $inv = $player->getArmorInventory();
        $inv->setHelmet($set->getItemForSlot(ArmorSet::SLOT_HELMET));
        $inv->setChestplate($set->getItemForSlot(ArmorSet::SLOT_CHESTPLATE));
        $inv->setLeggings($set->getItemForSlot(ArmorSet::SLOT_LEGGINGS));
        $inv->setBoots($set->getItemForSlot(ArmorSet::SLOT_BOOTS));

        $this->updateCache($player);
    }

    /** Returns the slot names that are missing from $player's dominant set. */
    public function getMissingSlots(Player $player): array {
        $info = $this->getPlayerSetInfo($player);
        if ($info === null) {
            return [];
        }
        [$set] = $info;
        $inv   = $player->getArmorInventory();

        $slots = [
            ArmorSet::SLOT_HELMET     => $inv->getHelmet(),
            ArmorSet::SLOT_CHESTPLATE => $inv->getChestplate(),
            ArmorSet::SLOT_LEGGINGS   => $inv->getLeggings(),
            ArmorSet::SLOT_BOOTS      => $inv->getBoots(),
        ];

        $missing = [];
        foreach ($slots as $slotName => $item) {
            if (!$set->isPartOfSet($item)) {
                $missing[] = $slotName;
            }
        }
        return $missing;
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function getPerkValue(Player $player, ArmorPerk $perk): float {
        $set = $this->getCachedActiveSet($player) ?? $this->getActiveSet($player);
        if ($set === null) {
            return 0.0;
        }
        return (float) ($set->getPerks()[$perk->value] ?? 0.0);
    }
}
