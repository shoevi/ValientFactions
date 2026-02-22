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
 * Key design decisions:
 *  - ALL PERKS require exactly 4 pieces of the same set. Partial sets grant NOTHING.
 *  - A per-player set cache (updated every 5 s by ArmorEffectTask) avoids expensive
 *    inventory scans on every combat event.
 *  - Ability cooldowns, invincibility windows, and recursion guards are all tracked here.
 *  - Combat triggers (ignition, low-HP shield, kill burst, thorns) are dispatched here.
 */
final class ArmorManager {

    private Main $plugin;
    private ArmorSetRegistry $registry;
    private ArmorStatsTracker $stats;

    // -------------------------------------------------------------------------
    // Per-player set cache  (UUID string → ?ArmorSet)
    // Updated every 5 s by ArmorEffectTask; live-checked on first access per tick
    // -------------------------------------------------------------------------
    /** @var array<string, ArmorSet|null> */
    private array $setCache = [];
    /** Previous cache snapshot for equip/unequip change detection */
    /** @var array<string, string|null> UUID → set name or null */
    private array $prevSetName = [];

    // -------------------------------------------------------------------------
    // Ability cooldowns: UUID → server tick when ability expires
    // -------------------------------------------------------------------------
    /** @var array<string, int> */
    private array $abilityCooldowns = [];

    // -------------------------------------------------------------------------
    // Void low-HP emergency shield cooldown: UUID → expiry tick
    // -------------------------------------------------------------------------
    /** @var array<string, int> */
    private array $voidShieldCooldowns = [];

    // -------------------------------------------------------------------------
    // Invincibility pool (Void Shroud ability): UUID → expiry tick
    // -------------------------------------------------------------------------
    /** @var array<string, int> */
    private array $invincibleUntil = [];

    // -------------------------------------------------------------------------
    // Thorns recursion guard: set of UUIDs currently dealing reflected damage
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
    // Set detection  (REQUIRES ALL 4 PIECES)
    // =========================================================================

    /**
     * Returns [ArmorSet, int $pieces] for the dominant set the player is wearing.
     * Perks are only granted when $pieces === 4.
     *
     * This is the informational method – use getCachedActiveSet() for performance
     * in high-frequency event handlers.
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

    /**
     * Returns the ArmorSet only when ALL 4 pieces of the same set are worn.
     * Returns null for any partial set.
     */
    public function getActiveSet(Player $player): ?ArmorSet {
        $info = $this->getPlayerSetInfo($player);
        if ($info === null || $info[1] !== 4) {
            return null;
        }
        return $info[0];
    }

    /**
     * Returns the cached active set (requires 4 pieces).
     * Updated by ArmorEffectTask every 5 s.
     */
    public function getCachedActiveSet(Player $player): ?ArmorSet {
        return $this->setCache[$player->getUniqueId()->toString()] ?? null;
    }

    /**
     * Update the cache for a player and return whether the active set changed.
     * Called by ArmorEffectTask.
     */
    public function updateCache(Player $player): bool {
        $uuid      = $player->getUniqueId()->toString();
        $newSet    = $this->getActiveSet($player);
        $newName   = $newSet?->getName();
        $prevName  = $this->prevSetName[$uuid] ?? "__unset__";

        $this->setCache[$uuid]   = $newSet;
        $this->prevSetName[$uuid] = $newName;

        return $newName !== $prevName;
    }

    /** Remove cache entries when a player leaves. */
    public function removeFromCache(Player $player): void {
        $uuid = $player->getUniqueId()->toString();
        unset($this->setCache[$uuid], $this->prevSetName[$uuid]);
    }

    // =========================================================================
    // Stat accessors  (zero unless 4/4 set active)
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
    // Potion effect management  (called by ArmorEffectTask)
    // =========================================================================

    /**
     * Refresh persistent potion-effect perks for a player.
     * Only runs when the player has a complete (4/4) set active.
     * Effect duration is 200 ticks (10 s) so it stays active between
     * 100-tick task refreshes.
     */
    public function refreshEffects(Player $player): void {
        $set = $this->getCachedActiveSet($player);
        if ($set === null) {
            return;
        }

        $perks    = $set->getPerks();
        $duration = 200; // ticks

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
    // Active ability
    // =========================================================================

    /**
     * Attempt to activate the ability for the set a player is currently wearing.
     *
     * @return array{bool, string}  [success, message] – message sent to the player.
     */
    public function activateAbility(Player $player): array {
        $set = $this->getCachedActiveSet($player) ?? $this->getActiveSet($player);

        if ($set === null) {
            return [false, "§cYou must wear a complete custom armor set (4/4 pieces) to use an ability!"];
        }

        $ability = $set->getAbility();
        if ($ability === null) {
            return [false, "§cThis set has no active ability."];
        }

        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();

        if (isset($this->abilityCooldowns[$uuid]) && $this->abilityCooldowns[$uuid] > $now) {
            $remaining = (int) ceil(($this->abilityCooldowns[$uuid] - $now) / 20);
            return [false, "§cAbility on cooldown! §7(" . $remaining . "s remaining)"];
        }

        // Set cooldown before executing (prevents double-firing in edge cases)
        $this->abilityCooldowns[$uuid] = $now + $ability->getCooldownTicks();

        return [true, $ability->execute($player, $this)];
    }

    /**
     * Get remaining cooldown ticks for a player's ability, or 0 if ready.
     */
    public function getAbilityCooldownTicks(Player $player): int {
        $uuid    = $player->getUniqueId()->toString();
        $now     = $this->plugin->getServer()->getTick();
        $expiry  = $this->abilityCooldowns[$uuid] ?? 0;
        return max(0, $expiry - $now);
    }

    // =========================================================================
    // Invincibility  (Void Shroud ability)
    // =========================================================================

    /** Grant server-side invincibility for $durationTicks ticks. */
    public function grantInvincibility(Player $player, int $durationTicks): void {
        $uuid = $player->getUniqueId()->toString();
        $this->invincibleUntil[$uuid] = $this->plugin->getServer()->getTick() + $durationTicks;
    }

    /** Returns true if the player is currently invincible (Void Shroud active). */
    public function isInvincible(Player $player): bool {
        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();
        if (isset($this->invincibleUntil[$uuid]) && $this->invincibleUntil[$uuid] > $now) {
            return true;
        }
        // Clean up expired entry
        unset($this->invincibleUntil[$uuid]);
        return false;
    }

    // =========================================================================
    // Void emergency shield  (passive trigger)
    // =========================================================================

    /**
     * Check and consume the Void set's low-HP emergency shield.
     * Returns true if the shield absorbed the hit (caller should cancel damage).
     */
    public function consumeVoidShield(Player $player): bool {
        $set = $this->getCachedActiveSet($player);
        if ($set === null || $set->getName() !== "Void") {
            return false;
        }

        $uuid = $player->getUniqueId()->toString();
        $now  = $this->plugin->getServer()->getTick();

        if (isset($this->voidShieldCooldowns[$uuid]) && $this->voidShieldCooldowns[$uuid] > $now) {
            return false; // Shield on cooldown
        }

        // Shield triggers once, then enters a 45 s cooldown
        $this->voidShieldCooldowns[$uuid] = $now + 45 * 20;
        $this->stats->increment($player, ArmorStatsTracker::COMBAT_TRIGGERS);
        return true;
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
    // Kill trigger  (Storm "Killing Spree")
    // =========================================================================

    /**
     * Called when $killer kills another player.
     * Handles the Storm set's "Killing Spree" speed burst.
     */
    public function handleKill(Player $killer): void {
        $set = $this->getCachedActiveSet($killer) ?? $this->getActiveSet($killer);
        if ($set === null || $set->getName() !== "Storm") {
            return;
        }

        $duration = 5 * 20; // 5 s
        $killer->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(),      $duration, 2, false)); // Speed III
        $killer->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), $duration, 1, false)); // Jump Boost II
        $killer->sendTip("§b⚡ §aKilling Spree! §b+Speed III + Jump Boost II");

        $this->stats->increment($killer, ArmorStatsTracker::COMBAT_TRIGGERS);
        $this->stats->increment($killer, ArmorStatsTracker::KILLS_WITH_SET);
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

        // Immediately update cache after equipping
        $this->updateCache($player);
    }

    /** Returns the slot names that are missing a piece of $player's dominant set. */
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

    /**
     * Get a perk value for a player's ACTIVE (4/4) set.
     * Returns 0.0 for partial sets.
     */
    private function getPerkValue(Player $player, ArmorPerk $perk): float {
        // Try cache first for hot paths, fall back to live check
        $set = $this->getCachedActiveSet($player) ?? $this->getActiveSet($player);
        if ($set === null) {
            return 0.0;
        }
        return (float) ($set->getPerks()[$perk->value] ?? 0.0);
    }
}
