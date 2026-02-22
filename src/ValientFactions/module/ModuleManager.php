<?php

declare(strict_types=1);

namespace ValientFactions\module;

use ValientFactions\Main;

final class ModuleManager {

    private array $modules = [];
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Register a module
     */
    public function registerModule(ModuleInterface $module): void {
        $name = $module->getName();
        
        if (isset($this->modules[$name])) {
            $this->plugin->getLogger()->warning("Module '{$name}' is already registered!");
            return;
        }
        
        $this->modules[$name] = $module;
        $this->plugin->getLogger()->debug("Registered module: {$name}");
    }

    /**
     * Enable a module by name
     */
    public function enableModule(string $name): bool {
        $module = $this->getModule($name);
        
        if ($module === null) {
            $this->plugin->getLogger()->error("Module '{$name}' not found!");
            return false;
        }
        
        if ($module->isEnabled()) {
            $this->plugin->getLogger()->warning("Module '{$name}' is already enabled!");
            return false;
        }
        
        try {
            $module->onEnable();
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to enable module '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable a module by name
     */
    public function disableModule(string $name): bool {
        $module = $this->getModule($name);
        
        if ($module === null) {
            $this->plugin->getLogger()->error("Module '{$name}' not found!");
            return false;
        }
        
        if (!$module->isEnabled()) {
            $this->plugin->getLogger()->warning("Module '{$name}' is already disabled!");
            return false;
        }
        
        try {
            $module->onDisable();
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Failed to disable module '{$name}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a module by name
     */
    public function getModule(string $name): ?ModuleInterface {
        return $this->modules[$name] ?? null;
    }

    /**
     * Get all registered modules
     */
    public function getAllModules(): array {
        return $this->modules;
    }

    /**
     * Get all enabled modules
     */
    public function getEnabledModules(): array {
        return array_filter($this->modules, fn(ModuleInterface $module) => $module->isEnabled());
    }

    /**
     * Check if a module is enabled
     */
    public function isModuleEnabled(string $name): bool {
        $module = $this->getModule($name);
        return $module !== null && $module->isEnabled();
    }
}
