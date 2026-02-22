<?php

declare(strict_types=1);

namespace ValientFactions\customitem;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

final class CustomItemManager {

    private const NBT_KEY = "vf_custom_item_id";

    /** @var array<string, CustomItemDefinition> */
    private array $items = [];

    public function registerDefaults(): void {
        $this->register(new CustomItemDefinition(
            "cosmic_orb",
            TF::LIGHT_PURPLE . TF::BOLD . "Cosmic Orb",
            [
                TF::GRAY . "Left-click to gain a speed burst",
                TF::DARK_GRAY . "Custom Item API"
            ],
            static fn() => VanillaItems::NETHER_STAR(),
            static function (Player $player): void {
                $player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 8, 1, false));
                $player->sendMessage(TF::LIGHT_PURPLE . "Cosmic Orb activated: Speed II for 8s");
            }
        ));

        $this->register(new CustomItemDefinition(
            "vampiric_apple",
            TF::RED . TF::BOLD . "Vampiric Apple",
            [
                TF::GRAY . "Consume to recover extra health",
                TF::DARK_GRAY . "Custom Item API"
            ],
            static fn() => VanillaItems::GOLDEN_APPLE(),
            null,
            static function (Player $player): void {
                $newHealth = min($player->getMaxHealth(), $player->getHealth() + 6.0);
                $player->setHealth($newHealth);
                $player->sendMessage(TF::RED . "Vampiric Apple drained power into your heart.");
            }
        ));
    }

    public function register(CustomItemDefinition $definition): void {
        $this->items[$definition->getId()] = $definition;
    }

    public function createItem(string $id, int $count = 1): ?Item {
        $definition = $this->items[$id] ?? null;
        return $definition?->createItem($count);
    }

    public function getDefinition(Item $item): ?CustomItemDefinition {
        $id = $item->getNamedTag()->getString(self::NBT_KEY, "");
        if ($id === "") {
            return null;
        }
        return $this->items[$id] ?? null;
    }

    public function handleInteract(PlayerInteractEvent $event): void {
        if ($event->getAction() !== PlayerInteractEvent::LEFT_CLICK_AIR
            && $event->getAction() !== PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            return;
        }

        $player = $event->getPlayer();
        $item = $event->getItem();
        $definition = $this->getDefinition($item);
        if ($definition === null) {
            return;
        }

        $definition->onInteract($player, $item, $event);
    }

    public function handleConsume(PlayerItemConsumeEvent $event): void {
        $player = $event->getPlayer();
        $definition = $this->getDefinition($event->getItem());
        if ($definition === null) {
            return;
        }

        $definition->onConsume($player, $event->getItem(), $event);
    }
}
