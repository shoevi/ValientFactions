<?php

declare(strict_types=1);

namespace ValientFactions\module\modules;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use ValientFactions\module\BaseModule;
use ValientFactions\Main;

final class PowerModule extends BaseModule implements Listener {

    private array $playerPower = [];
    private const DEFAULT_POWER = 10;
    private const MAX_POWER = 20;
    private const POWER_LOSS_ON_DEATH = 2;

    public function getName(): string {
        return "Power";
    }

    public function getDescription(): string {
        return "Manages player and faction power system";
    }

    public function onEnable(): void {
        parent::onEnable();
        
        // Register event listeners
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        
        // Initialize power for online players
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->initializePlayerPower($player->getName());
        }
        
        $this->plugin->getLogger()->debug("PowerModule: Event listeners registered and player power initialized");
    }

    public function onDisable(): void {
        parent::onDisable();
        
        // Unregister event listeners
        PlayerJoinEvent::getHandlers()->unregister($this);
        PlayerQuitEvent::getHandlers()->unregister($this);
        PlayerDeathEvent::getHandlers()->unregister($this);
        
        // Save power data before disabling
        $this->savePowerData();
        
        // Clear power data from memory
        $this->playerPower = [];
        
        $this->plugin->getLogger()->debug("PowerModule: Event listeners unregistered and power data saved");
    }

    /**
     * Handle player join events
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->initializePlayerPower($player->getName());
        
        $this->plugin->getLogger()->debug(
            "PowerModule: Initialized power for {$player->getName()}: {$this->getPlayerPower($player->getName())}"
        );
    }

    /**
     * Handle player quit events
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        
        // Save player power before they leave
        $this->plugin->getLogger()->debug(
            "PowerModule: Saving power for {$playerName}: {$this->getPlayerPower($playerName)}"
        );
        
        // TODO: Save to database/file
    }

    /**
     * Handle player death events
     */
    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $playerName = $player->getName();
        
        $currentPower = $this->getPlayerPower($playerName);
        $newPower = max(0, $currentPower - self::POWER_LOSS_ON_DEATH);
        
        $this->setPlayerPower($playerName, $newPower);
        
        $this->plugin->getLogger()->debug(
            "PowerModule: {$playerName} died. Power: {$currentPower} -> {$newPower}"
        );
        
        // TODO: Implement additional logic
        // - Notify player of power loss
        // - Update faction total power
        // - Check if faction lost any claims due to low power
    }

    /**
     * Initialize power for a player
     */
    private function initializePlayerPower(string $playerName): void {
        if (!isset($this->playerPower[$playerName])) {
            // TODO: Load from database/file
            $this->playerPower[$playerName] = self::DEFAULT_POWER;
        }
    }

    /**
     * Get a player's current power
     */
    public function getPlayerPower(string $playerName): int {
        return $this->playerPower[$playerName] ?? self::DEFAULT_POWER;
    }

    /**
     * Set a player's power
     */
    public function setPlayerPower(string $playerName, int $power): void {
        $this->playerPower[$playerName] = min(self::MAX_POWER, max(0, $power));
    }

    /**
     * Add power to a player
     */
    public function addPlayerPower(string $playerName, int $amount): void {
        $currentPower = $this->getPlayerPower($playerName);
        $this->setPlayerPower($playerName, $currentPower + $amount);
    }

    /**
     * Save power data to storage
     */
    private function savePowerData(): void {
        // TODO: Implement data persistence
        // - Save to database or file
        // - Store player power values
        $this->plugin->getLogger()->debug("PowerModule: Saving power data for " . count($this->playerPower) . " players");
    }
}
