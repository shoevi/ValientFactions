<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\item\Item;
use pocketmine\item\LeatherArmor;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Color;

/**
 * Defines a complete custom leather armor set.
 *
 * Perks are ONLY granted when the player wears ALL FOUR pieces of the same set.
 * No partial-set bonuses exist.
 *
 * Each set may optionally define:
 *  - A unique active {@see SetAbility} triggered via /vfability
 *  - A passive combat trigger description shown in item lore
 */
abstract class ArmorSet {

    public const SLOT_HELMET     = "helmet";
    public const SLOT_CHESTPLATE = "chestplate";
    public const SLOT_LEGGINGS   = "leggings";
    public const SLOT_BOOTS      = "boots";

    public const ALL_SLOTS = [
        self::SLOT_HELMET,
        self::SLOT_CHESTPLATE,
        self::SLOT_LEGGINGS,
        self::SLOT_BOOTS,
    ];

    // -------------------------------------------------------------------------
    // Abstract identity
    // -------------------------------------------------------------------------

    /** Unique internal set name (used for NBT tags and registry lookups). */
    abstract public function getName(): string;

    /** Short flavour description displayed in lore. */
    abstract public function getDescription(): string;

    /** Leather dye colour applied to all pieces. */
    abstract public function getColor(): Color;

    /** TextFormat colour prefix for names and headers. */
    abstract public function getLoreColor(): string;

    /**
     * The full-set perks granted when ALL 4 pieces are worn.
     * Keys are {@see ArmorPerk} value strings; values are numeric amounts.
     *
     * @return array<string, float>
     */
    abstract public function getPerks(): array;

    /**
     * Optional active ability triggered by /vfability.
     * Return null if the set has no active ability.
     */
    abstract public function getAbility(): ?SetAbility;

    /**
     * Optional single-line description of the set's passive combat trigger.
     * Shown in lore. Return null if there is no combat trigger.
     */
    public function getCombatTriggerDescription(): ?string {
        return null;
    }

    // -------------------------------------------------------------------------
    // Item construction
    // -------------------------------------------------------------------------

    /**
     * Build and return a fully configured, NBT-tagged leather armor item.
     */
    public function getItemForSlot(string $slot): Item {
        /** @var LeatherArmor $item */
        $item = match ($slot) {
            self::SLOT_HELMET     => VanillaItems::LEATHER_HELMET(),
            self::SLOT_CHESTPLATE => VanillaItems::LEATHER_CHESTPLATE(),
            self::SLOT_LEGGINGS   => VanillaItems::LEATHER_LEGGINGS(),
            self::SLOT_BOOTS      => VanillaItems::LEATHER_BOOTS(),
            default               => throw new \InvalidArgumentException("Invalid armor slot: $slot"),
        };

        $item->setCustomColor($this->getColor());
        $item->setCustomName($this->getLoreColor() . $this->getName() . " " . ucfirst($slot));
        $item->setLore($this->buildLore($slot));

        // Stamp set identity + slot into NBT for identification
        $nbt = $item->getNamedTag();
        $nbt->setString("vfArmorSet",  $this->getName());
        $nbt->setString("vfArmorSlot", $slot);
        $item->setNamedTag($nbt);

        return $item;
    }

    /**
     * Returns all 4 pieces keyed by slot constant.
     * @return array<string, Item>
     */
    public function getFullSet(): array {
        $pieces = [];
        foreach (self::ALL_SLOTS as $slot) {
            $pieces[$slot] = $this->getItemForSlot($slot);
        }
        return $pieces;
    }

    /** Returns true if the given item is NBT-tagged as belonging to this set. */
    public function isPartOfSet(Item $item): bool {
        return $item->getNamedTag()->getString("vfArmorSet", "") === $this->getName();
    }

    // -------------------------------------------------------------------------
    // Lore builder
    // -------------------------------------------------------------------------

    /**
     * Builds rich multi-line lore for a single armor piece.
     * @return string[]
     */
    protected function buildLore(string $slot): array {
        $lc    = $this->getLoreColor();
        $lines = [];

        // Header
        $lines[] = $lc . "§l" . $this->getName() . " Set§r";
        $lines[] = "§7" . $this->getDescription();
        $lines[] = "";

        // Requirement warning
        $lines[] = "§e⚠ §6Requires all §e4/4§6 pieces to activate";
        $lines[] = "";

        // Perks
        $lines[] = "§6§lFull Set Perks:";
        foreach ($this->getPerks() as $perkValue => $amount) {
            $lines[] = "  " . $this->formatPerkLine(ArmorPerk::from($perkValue), $amount);
        }
        $lines[] = "";

        // Combat trigger
        $trigger = $this->getCombatTriggerDescription();
        if ($trigger !== null) {
            $lines[] = "§d§lPassive Trigger:";
            $lines[] = "  §5" . $trigger;
            $lines[] = "";
        }

        // Active ability
        $ability = $this->getAbility();
        if ($ability !== null) {
            $cd      = (int) round($ability->getCooldownTicks() / 20);
            $lines[] = "§b§lActive Ability: §f" . $ability->getName();
            $lines[] = "  §7" . $ability->getDescription();
            $lines[] = "  §3Cooldown: §f" . $cd . "s";
            $lines[] = "  §3Use: §f/vfability";
            $lines[] = "";
        }

        // Slot indicator
        $lines[] = "§8Slot: " . ucfirst($slot);

        return $lines;
    }

    /** Format a single perk value into a human-readable coloured line. */
    protected function formatPerkLine(ArmorPerk $perk, float $amount): string {
        return match ($perk) {
            ArmorPerk::DAMAGE_REDUCTION     => sprintf("§a+%.0f%% Damage Reduction",      $amount * 100),
            ArmorPerk::DAMAGE_BOOST         => sprintf("§c+%.0f%% Damage Boost",           $amount * 100),
            ArmorPerk::THORNS               => sprintf("§4%.0f%% Thorns (dmg reflected)",  $amount * 100),
            ArmorPerk::KNOCKBACK_RESISTANCE => sprintf("§a%.0f%% Knockback Resistance",    $amount * 100),
            ArmorPerk::FALL_DAMAGE_REDUCTION => sprintf("§a%.0f%% Fall Dmg Reduction",     $amount * 100),
            ArmorPerk::EXPLOSION_RESISTANCE  => sprintf("§a%.0f%% Explosion Resistance",   $amount * 100),
            ArmorPerk::SLOWNESS_ON_HIT      => sprintf("§9Slowness on Hit (%.1fs)",        $amount / 20),
            ArmorPerk::LIFESTEAL            => sprintf("§4%.0f%% Lifesteal",               $amount * 100),
            ArmorPerk::SPEED               => "§a Speed " . $this->romanLevel((int) $amount),
            ArmorPerk::REGENERATION        => "§a Regeneration " . $this->romanLevel((int) $amount),
            ArmorPerk::STRENGTH            => "§c Strength " . $this->romanLevel((int) $amount),
            ArmorPerk::RESISTANCE          => "§a Resistance " . $this->romanLevel((int) $amount),
            ArmorPerk::JUMP_BOOST          => "§a Jump Boost " . $this->romanLevel((int) $amount),
            ArmorPerk::ABSORPTION          => "§6 Absorption " . $this->romanLevel((int) $amount),
            ArmorPerk::FIRE_RESISTANCE     => "§6 Fire Resistance",
        };
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Convert an integer level to a Roman numeral string (up to X). */
    protected function romanLevel(int $level): string {
        return match ($level) {
            1 => "I", 2 => "II", 3 => "III", 4 => "IV", 5 => "V",
            6 => "VI", 7 => "VII", 8 => "VIII", 9 => "IX", 10 => "X",
            default => (string) $level,
        };
    }
}
