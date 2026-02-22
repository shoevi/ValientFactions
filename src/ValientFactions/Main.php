<?php

declare(strict_types=1);

namespace ValientFactions;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ValientFactions\module\ModuleManager;
use ValientFactions\module\modules\armor\ArmorModule;
use ValientFactions\command\VFactionsCommand;

final class Main extends PluginBase {

    private ModuleManager $moduleManager;

    protected function onEnable(): void {
        // Save default config if it doesn't exist
        $this->saveDefaultConfig();
        
        // Initialize module manager
        $this->moduleManager = new ModuleManager($this);
        
        // Register all built-in modules
        $this->registerBuiltInModules();
        
        // Enable modules based on configuration
        $this->enableConfiguredModules();
        
        // Register commands
        $this->registerCommands();
        
        $this->getLogger()->info(TextFormat::GREEN . "ValientFactions has been enabled!");
        $this->getLogger()->info(TextFormat::YELLOW . "Loaded " . count($this->moduleManager->getEnabledModules()) . " modules");
    }

    protected function onDisable(): void {
        // Disable all active modules
        foreach ($this->moduleManager->getEnabledModules() as $module) {
            $this->moduleManager->disableModule($module->getName());
        }
        
        $this->getLogger()->info(TextFormat::RED . "ValientFactions has been disabled!");
    }

    private function registerBuiltInModules(): void {
        $this->moduleManager->registerModule(new ArmorModule($this));
        
        $this->getLogger()->debug("Registered " . count($this->moduleManager->getAllModules()) . " built-in modules");
    }

    private function enableConfiguredModules(): void {
        $config = $this->getConfig();
        $modulesConfig = $config->get("modules", []);
        
        foreach ($modulesConfig as $moduleName => $enabled) {
            if ($enabled === true) {
                if ($this->moduleManager->enableModule($moduleName)) {
                    $this->getLogger()->info(TextFormat::GREEN . "Enabled module: " . $moduleName);
                } else {
                    $this->getLogger()->warning(TextFormat::RED . "Failed to enable module: " . $moduleName);
                }
            }
        }
    }

    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register("valientfactions", new VFactionsCommand($this));
    }

    public function getModuleManager(): ModuleManager {
        return $this->moduleManager;
    }

    public function reloadConfiguration(): void {
        $this->reloadConfig();
        
        // Disable all modules first
        foreach ($this->moduleManager->getEnabledModules() as $module) {
            $this->moduleManager->disableModule($module->getName());
        }
        
        // Re-enable modules based on updated config
        $this->enableConfiguredModules();
    }
}
