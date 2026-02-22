<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\item\Item;
use pocketmine\item\LeatherArmor;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Color;

/**
 * Represents a complete custom leather armor set.
 * Each concrete set defines its color, lore, and perk scaling per pieces worn.
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

    /** Internal set identifier (unique, used for NBT tagging) */
    abstract public function getName(): string;

    /** One-line flavour description shown in item lore */
    abstract public function getDescription(): string;

    /** Leather dye colour applied to every piece */
    abstract public function getColor(): Color;

    /** TextFormat colour prefix used for display names and chat output */
    abstract public function getLoreColor(): string;

    /**
     * Returns the active perks for wearing $pieces pieces of this set.
     * Keys are ArmorPerk->value strings; values are numeric amounts.
     *
     * @return array<string, float>
     */
    abstract public function getPerksForPieces(int $pieces): array;

    // -------------------------------------------------------------------------

    /**
     * Build and return a single coloured, tagged leather armor item.
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
        $item->setLore($this->buildLore());

        // Stamp the set identity into NBT so we can identify it later
        $nbt = $item->getNamedTag();
        $nbt->setString("vfArmorSet",  $this->getName());
        $nbt->setString("vfArmorSlot", $slot);
        $item->setNamedTag($nbt);

        return $item;
    }

    /**
     * Returns all 4 pieces keyed by slot constant.
     *
     * @return array<string, Item>
     */
    public function getFullSet(): array {
        $pieces = [];
        foreach (self::ALL_SLOTS as $slot) {
            $pieces[$slot] = $this->getItemForSlot($slot);
        }
        return $pieces;
    }

    /**
     * Returns true if the given item is tagged as part of this set.
     */
    public function isPartOfSet(Item $item): bool {
        return $item->getNamedTag()->getString("vfArmorSet", "") === $this->getName();
    }

    // -------------------------------------------------------------------------

    /** Build the item lore lines shared by every piece in the set. */
    protected function buildLore(): array {
        return [
            $this->getLoreColor() . "Set: " . $this->getName(),
            "§7" . $this->getDescription(),
            "",
            "§e2-piece: " . $this->describePerk(2),
            "§6Full set: " . $this->describePerk(4),
        ];
    }

    /** Summarise active perks for $pieces pieces into a short string. */
    private function describePerk(int $pieces): string {
        $perks = $this->getPerksForPieces($pieces);
        $parts = [];
        foreach ($perks as $perkValue => $amount) {
            $perk   = ArmorPerk::from($perkValue);
            $parts[] = match ($perk) {
                ArmorPerk::DAMAGE_REDUCTION    => sprintf("%.0f%% Damage Reduction", $amount * 100),
                ArmorPerk::DAMAGE_BOOST        => sprintf("+%.0f%% Damage", $amount * 100),
                ArmorPerk::KNOCKBACK_RESISTANCE => sprintf("%.0f%% KB Resist", $amount * 100),
                ArmorPerk::SPEED               => "Speed " . (int) $amount,
                ArmorPerk::REGENERATION        => "Regen " . (int) $amount,
                ArmorPerk::FIRE_RESISTANCE     => "Fire Resistance",
                ArmorPerk::LIFESTEAL           => sprintf("%.0f%% Lifesteal", $amount * 100),
                ArmorPerk::STRENGTH            => "Strength " . (int) $amount,
            };
        }
        return $parts !== [] ? implode(", ", $parts) : "None";
    }
}
