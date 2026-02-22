<?php

declare(strict_types=1);

namespace ValientFactions\customitem;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class CustomItemDefinition {

    private const NBT_KEY = "vf_custom_item_id";

    /** @var \Closure(): Item */
    private \Closure $itemFactory;
    /** @var \Closure(Player, Item, PlayerInteractEvent): void|null */
    private ?\Closure $leftClickHandler;
    /** @var \Closure(Player, Item, PlayerItemConsumeEvent): void|null */
    private ?\Closure $consumeHandler;

    /**
     * @param \Closure(): Item $itemFactory
     * @param \Closure(Player, Item, PlayerInteractEvent): void|null $leftClickHandler
     * @param \Closure(Player, Item, PlayerItemConsumeEvent): void|null $consumeHandler
     */
    public function __construct(
        private string $id,
        private string $displayName,
        private array $lore,
        \Closure $itemFactory,
        ?\Closure $leftClickHandler = null,
        ?\Closure $consumeHandler = null
    ) {
        $this->itemFactory = $itemFactory;
        $this->leftClickHandler = $leftClickHandler;
        $this->consumeHandler = $consumeHandler;
    }

    public function getId(): string {
        return $this->id;
    }

    public function createItem(int $count = 1): Item {
        $item = ($this->itemFactory)();
        $item->setCount($count);
        $item->setCustomName($this->displayName);
        $item->setLore($this->lore);
        $nbt = $item->getNamedTag();
        $nbt->setString(self::NBT_KEY, $this->id);
        $item->setNamedTag($nbt);
        return $item;
    }

    public function matches(Item $item): bool {
        return $item->getNamedTag()->getString(self::NBT_KEY, "") === $this->id;
    }

    public function onInteract(Player $player, Item $item, PlayerInteractEvent $event): void {
        $this->leftClickHandler?->__invoke($player, $item, $event);
    }

    public function onConsume(Player $player, Item $item, PlayerItemConsumeEvent $event): void {
        $this->consumeHandler?->__invoke($player, $item, $event);
    }
}
