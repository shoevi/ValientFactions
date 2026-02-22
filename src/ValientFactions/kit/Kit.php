<?php

declare(strict_types=1);

namespace ValientFactions\kit;

use pocketmine\item\Item;

/**
 * Represents a claimable kit with items and cooldown.
 */
final class Kit {

    /** @var string */
    private string $name;

    /** @var string */
    private string $category;

    /** @var array<int, Item> */
    private array $items;

    /** @var int Cooldown in seconds */
    private int $cooldown;

    /** @var string|null Optional permission */
    private ?string $permission;

    /**
     * @param string $name Kit name
     * @param string $category Kit category (kits, gkits, vkits)
     * @param array<int, Item> $items Items to give
     * @param int $cooldown Cooldown in seconds
     * @param string|null $permission Optional permission required to claim
     */
    public function __construct(string $name, string $category, array $items, int $cooldown, ?string $permission = null) {
        $this->name = $name;
        $this->category = $category;
        $this->items = $items;
        $this->cooldown = $cooldown;
        $this->permission = $permission;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getCategory(): string {
        return $this->category;
    }

    /**
     * @return array<int, Item>
     */
    public function getItems(): array {
        return $this->items;
    }

    public function getCooldown(): int {
        return $this->cooldown;
    }

    public function getPermission(): ?string {
        return $this->permission;
    }
}
