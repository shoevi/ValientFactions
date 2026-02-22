<?php

declare(strict_types=1);

namespace ValientFactions\module;

use pocketmine\utils\TextFormat;
use ValientFactions\Main;

abstract class BaseModule implements ModuleInterface {

    protected bool $enabled = false;
    protected Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function onEnable(): void {
        $this->setEnabled(true);
        $this->plugin->getLogger()->info(TextFormat::GREEN . "Module enabled: " . $this->getName());
    }

    public function onDisable(): void {
        $this->setEnabled(false);
        $this->plugin->getLogger()->info(TextFormat::RED . "Module disabled: " . $this->getName());
    }

    protected function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    protected function getPlugin(): Main {
        return $this->plugin;
    }

    /**
     * Get the name of the module
     * Must be implemented by child classes
     */
    abstract public function getName(): string;

    /**
     * Get the description of the module
     * Must be implemented by child classes
     */
    abstract public function getDescription(): string;
}
