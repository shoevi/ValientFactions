<?php

declare(strict_types=1);

namespace ValientFactions\customitem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;

final class CustomItemListener implements Listener {

    public function __construct(private CustomItemManager $customItemManager) {
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $this->customItemManager->handleLeftClick($event);
    }

    public function onConsume(PlayerItemConsumeEvent $event): void {
        $this->customItemManager->handleConsume($event);
    }
}

