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
        foreach ($this->items as $definition) {
            if ($definition->matches($item)) {
                return $definition;
            }
        }
        return null;
    }

    public function handleLeftClick(PlayerInteractEvent $event): void {
        if ($event->getAction() !== PlayerInteractEvent::LEFT_CLICK_AIR
            && $event->getAction() !== PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            return;
        }

        $player = $event->getPlayer();
        $definition = $this->getDefinition($event->getItem());
        if ($definition === null) {
            return;
        }

        $definition->onLeftClick($player, $event->getItem(), $event);
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

