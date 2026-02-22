<?php

declare(strict_types=1);

namespace ValientFactions\kit;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\customitem\CustomItemManager;

/**
 * Manages kit registration, cooldowns, and claiming.
 */
final class KitManager {

    /** @var array<string, Kit> */
    private array $kits = [];

    /** @var array<string, array<string, int>> Player UUID => Kit Name => Timestamp */
    private array $cooldowns = [];
    private ?CustomItemManager $customItemManager;

    public function __construct(?CustomItemManager $customItemManager = null) {
        $this->customItemManager = $customItemManager;
        $this->registerDefaultKits();
    }

    /**
     * Register all default kits with sample items.
     */
    private function registerDefaultKits(): void {
        // Regular Kits
        $this->registerKit(new Kit(
            "Starter",
            "kits",
            [
                VanillaItems::DIAMOND_SWORD(),
                VanillaItems::GOLDEN_APPLE()->setCount(16),
                VanillaItems::STEAK()->setCount(64),
                VanillaItems::DIAMOND_HELMET(),
                VanillaItems::DIAMOND_CHESTPLATE(),
                VanillaItems::DIAMOND_LEGGINGS(),
                VanillaItems::DIAMOND_BOOTS()
            ],
            3600 // 1 hour cooldown
        ));

        $this->registerKit(new Kit(
            "PvP",
            "kits",
            [
                VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 3)),
                VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 2)),
                VanillaItems::ARROW()->setCount(64),
                VanillaItems::GOLDEN_APPLE()->setCount(8),
                VanillaItems::COOKED_BEEF()->setCount(32)
            ],
            7200 // 2 hours cooldown
        ));

        $this->registerKit(new Kit(
            "Mining",
            "kits",
            [
                VanillaItems::DIAMOND_PICKAXE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 4)),
                VanillaItems::DIAMOND_SHOVEL()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3)),
                VanillaItems::TORCH()->setCount(64),
                VanillaItems::STEAK()->setCount(32)
            ],
            3600 // 1 hour cooldown
        ));

        // God Kits (gkits)
        $this->registerKit(new Kit(
            "Warrior",
            "gkits",
            [
                VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 2)),
                VanillaItems::DIAMOND_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::DIAMOND_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::DIAMOND_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), 4)),
                VanillaItems::GOLDEN_APPLE()->setCount(32),
                VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(4)
            ],
            86400 // 24 hours cooldown
        ));

        $this->registerKit(new Kit(
            "Archer",
            "gkits",
            [
                VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 5))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1)),
                VanillaItems::ARROW()->setCount(1),
                VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 3)),
                VanillaItems::CHAINMAIL_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::CHAINMAIL_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::CHAINMAIL_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::CHAINMAIL_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::GOLDEN_APPLE()->setCount(16)
            ],
            86400 // 24 hours cooldown
        ));

        // VIP Kits (vkits)
        $this->registerKit(new Kit(
            "VIP",
            "vkits",
            [
                VanillaItems::DIAMOND_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 4)),
                VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 3)),
                VanillaItems::ARROW()->setCount(128),
                VanillaItems::DIAMOND_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::DIAMOND_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::DIAMOND_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::DIAMOND_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3)),
                VanillaItems::GOLDEN_APPLE()->setCount(64),
                VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(8),
                VanillaItems::STEAK()->setCount(64)
            ],
            43200 // 12 hours cooldown
        ));

        $this->registerKit(new Kit(
            "Elite",
            "vkits",
            [
                VanillaItems::NETHERITE_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::NETHERITE_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4)),
                VanillaItems::NETHERITE_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4)),
                VanillaItems::NETHERITE_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4)),
                VanillaItems::NETHERITE_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4)),
                VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(16),
                VanillaItems::ENDER_PEARL()->setCount(16)
            ],
            86400 // 24 hours cooldown
        ));

        $this->registerKit(new Kit(
            "Cosmic",
            "vkits",
            [
                VanillaItems::NETHERITE_SWORD()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 5))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FIRE_ASPECT(), 2))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::BOW()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 5))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::ARROW()->setCount(256),
                VanillaItems::NETHERITE_HELMET()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::NETHERITE_CHESTPLATE()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::NETHERITE_LEGGINGS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::NETHERITE_BOOTS()->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FEATHER_FALLING(), 4))
                    ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3)),
                VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(64),
                VanillaItems::ENDER_PEARL()->setCount(32),
                VanillaItems::TOTEM()->setCount(4),
                $this->customItemManager?->createItem("cosmic_orb") ?? VanillaItems::NETHER_STAR(),
                $this->customItemManager?->createItem("vampiric_apple") ?? VanillaItems::GOLDEN_APPLE()
            ],
            172800 // 48 hours cooldown
        ));
    }

    /**
     * Register a kit.
     *
     * @param Kit $kit
     */
    public function registerKit(Kit $kit): void {
        $this->kits[$kit->getName()] = $kit;
    }

    /**
     * Get a kit by name.
     *
     * @param string $name
     * @return Kit|null
     */
    public function getKit(string $name): ?Kit {
        return $this->kits[$name] ?? null;
    }

    /**
     * Get all kits for a specific category.
     *
     * @param string $category
     * @return array<string, Kit>
     */
    public function getKitsByCategory(string $category): array {
        return array_filter($this->kits, fn(Kit $kit) => $kit->getCategory() === $category);
    }

    /**
     * Get all registered kits.
     *
     * @return array<string, Kit>
     */
    public function getAllKits(): array {
        return $this->kits;
    }

    /**
     * Check if a player can claim a kit (cooldown check).
     *
     * @param Player $player
     * @param Kit $kit
     * @return bool
     */
    public function canClaim(Player $player, Kit $kit): bool {
        $uuid = $player->getUniqueId()->toString();
        $kitName = $kit->getName();

        if (!isset($this->cooldowns[$uuid][$kitName])) {
            return true;
        }

        $lastClaim = $this->cooldowns[$uuid][$kitName];
        $cooldownEnd = $lastClaim + $kit->getCooldown();

        return time() >= $cooldownEnd;
    }

    /**
     * Get remaining cooldown time in seconds.
     *
     * @param Player $player
     * @param Kit $kit
     * @return int 0 if no cooldown
     */
    public function getRemainingCooldown(Player $player, Kit $kit): int {
        $uuid = $player->getUniqueId()->toString();
        $kitName = $kit->getName();

        if (!isset($this->cooldowns[$uuid][$kitName])) {
            return 0;
        }

        $lastClaim = $this->cooldowns[$uuid][$kitName];
        $cooldownEnd = $lastClaim + $kit->getCooldown();
        $remaining = $cooldownEnd - time();

        return max(0, $remaining);
    }

    /**
     * Attempt to claim a kit for a player.
     *
     * @param Player $player
     * @param Kit $kit
     * @return bool True if successfully claimed
     */
    public function claimKit(Player $player, Kit $kit): bool {
        // Check permission
        if ($kit->getPermission() !== null && !$player->hasPermission($kit->getPermission())) {
            $player->sendMessage(TF::RED . "You don't have permission to claim this kit!");
            return false;
        }

        // Check cooldown
        if (!$this->canClaim($player, $kit)) {
            $remaining = $this->getRemainingCooldown($player, $kit);
            $player->sendMessage(TF::RED . "You must wait " . $this->formatTime($remaining) . " before claiming this kit again!");
            return false;
        }

        // Give items
        $inventory = $player->getInventory();
        $leftover = [];

        foreach ($kit->getItems() as $item) {
            // Clone to prevent modifying the kit's template items
            $clone = clone $item;
            $notAdded = $inventory->addItem($clone);
            if (!empty($notAdded)) {
                // Add items that couldn't be added to leftover
                foreach ($notAdded as $leftoverItem) {
                    $leftover[] = $leftoverItem;
                }
            }
        }

        // Drop leftover items
        if (!empty($leftover)) {
            $world = $player->getWorld();
            $position = $player->getPosition();
            foreach ($leftover as $item) {
                $world->dropItem($position, $item);
            }
            $player->sendMessage(TF::YELLOW . "Some items were dropped because your inventory is full!");
        }

        // Set cooldown
        $uuid = $player->getUniqueId()->toString();
        if (!isset($this->cooldowns[$uuid])) {
            $this->cooldowns[$uuid] = [];
        }
        $this->cooldowns[$uuid][$kit->getName()] = time();

        $player->sendMessage(TF::GREEN . "You have claimed the " . TF::AQUA . $kit->getName() . TF::GREEN . " kit!");
        return true;
    }

    /**
     * Format time in a human-readable format.
     *
     * @param int $seconds
     * @return string
     */
    public function formatTime(int $seconds): string {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = $days . "d";
        if ($hours > 0) $parts[] = $hours . "h";
        if ($minutes > 0) $parts[] = $minutes . "m";
        if ($secs > 0 || empty($parts)) $parts[] = $secs . "s";

        return implode(" ", $parts);
    }
}
