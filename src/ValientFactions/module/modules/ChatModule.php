<?php

declare(strict_types=1);

namespace ValientFactions\module\modules;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use ValientFactions\module\BaseModule;
use ValientFactions\Main;

final class ChatModule extends BaseModule implements Listener {

    public function getName(): string {
        return "Chat";
    }

    public function getDescription(): string {
        return "Manages faction chat and formatting";
    }

    public function onEnable(): void {
        parent::onEnable();
        
        // Register event listeners
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        
        $this->plugin->getLogger()->debug("ChatModule: Event listeners registered");
    }

    public function onDisable(): void {
        parent::onDisable();
        
        // Unregister event listeners
        PlayerChatEvent::getHandlers()->unregister($this);
        
        $this->plugin->getLogger()->debug("ChatModule: Event listeners unregistered");
    }

    /**
     * Handle player chat events
     * This is a skeleton implementation that logs chat messages
     */
    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        
        // Skeleton implementation - just log for now
        $this->plugin->getLogger()->debug("ChatModule: {$player->getName()} said: {$message}");
        
        // TODO: Implement faction chat formatting
        // - Check if player is in a faction
        // - Format message with faction tag/prefix
        // - Handle faction-only chat channels
        // - Apply custom chat colors based on faction rank
    }
}
