<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\item\Item;

/**
 * Holds every registered ArmorSet instance, keyed by set name.
 */
final class ArmorSetRegistry {

    /** @var array<string, ArmorSet> */
    private array $sets = [];

    /** Register an armor set. Overwrites any existing set with the same name. */
    public function register(ArmorSet $set): void {
        $this->sets[$set->getName()] = $set;
    }

    /** Retrieve a set by exact name, or null if not found. */
    public function get(string $name): ?ArmorSet {
        return $this->sets[$name] ?? null;
    }

    /**
     * Case-insensitive name lookup.
     * Checks exact match first, then falls back to lowercased comparison.
     */
    public function find(string $name): ?ArmorSet {
        if (isset($this->sets[$name])) {
            return $this->sets[$name];
        }
        $lower = strtolower($name);
        foreach ($this->sets as $set) {
            if (strtolower($set->getName()) === $lower) {
                return $set;
            }
        }
        return null;
    }

    /**
     * Return the ArmorSet that owns the given item (via NBT tag), or null.
     */
    public function getSetFromItem(Item $item): ?ArmorSet {
        $setName = $item->getNamedTag()->getString("vfArmorSet", "");
        return $setName !== "" ? ($this->sets[$setName] ?? null) : null;
    }

    /**
     * @return array<string, ArmorSet>
     */
    public function getAll(): array {
        return $this->sets;
    }
}
