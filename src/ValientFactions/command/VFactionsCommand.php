<?php

declare(strict_types=1);

namespace ValientFactions\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\Main;

final class VFactionsCommand extends Command implements PluginOwned {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("vfactions", "Main ValientFactions command", "/vfactions <modules|enable|disable|reload> [args]", ["vf", "factions"]);
        $this->setPermission("valientfactions.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (empty($args)) {
            $this->sendUsage($sender);
            return true;
        }

        $subCommand = strtolower(array_shift($args));

        switch ($subCommand) {
            case "modules":
            case "list":
                $this->handleModulesList($sender);
                break;

            case "enable":
                if (empty($args)) {
                    $sender->sendMessage(TF::RED . "Usage: /vfactions enable <module>");
                    return true;
                }
                $this->handleModuleEnable($sender, $args[0]);
                break;

            case "disable":
                if (empty($args)) {
                    $sender->sendMessage(TF::RED . "Usage: /vfactions disable <module>");
                    return true;
                }
                $this->handleModuleDisable($sender, $args[0]);
                break;

            case "reload":
                $this->handleReload($sender);
                break;

            default:
                $this->sendUsage($sender);
                break;
        }

        return true;
    }

    private function sendUsage(CommandSender $sender): void {
        $sender->sendMessage(TF::GOLD . "=== ValientFactions Commands ===");
        $sender->sendMessage(TF::YELLOW . "/vfactions modules" . TF::WHITE . " - List all modules and their status");
        $sender->sendMessage(TF::YELLOW . "/vfactions enable <module>" . TF::WHITE . " - Enable a module");
        $sender->sendMessage(TF::YELLOW . "/vfactions disable <module>" . TF::WHITE . " - Disable a module");
        $sender->sendMessage(TF::YELLOW . "/vfactions reload" . TF::WHITE . " - Reload the plugin configuration");
    }

    private function handleModulesList(CommandSender $sender): void {
        $moduleManager = $this->plugin->getModuleManager();
        $allModules = $moduleManager->getAllModules();

        if (empty($allModules)) {
            $sender->sendMessage(TF::RED . "No modules registered!");
            return;
        }

        $sender->sendMessage(TF::GOLD . "=== ValientFactions Modules ===");
        
        foreach ($allModules as $module) {
            $status = $module->isEnabled() ? TF::GREEN . "ENABLED" : TF::RED . "DISABLED";
            $sender->sendMessage(
                TF::YELLOW . $module->getName() . TF::WHITE . " - " . $status
                . "\n" . TF::GRAY . "  " . $module->getDescription()
            );
        }
    }

    private function handleModuleEnable(CommandSender $sender, string $moduleName): void {
        if (!$sender->hasPermission("valientfactions.module.enable")) {
            $sender->sendMessage(TF::RED . "You don't have permission to enable modules!");
            return;
        }

        $moduleManager = $this->plugin->getModuleManager();
        
        if ($moduleManager->enableModule($moduleName)) {
            $sender->sendMessage(TF::GREEN . "Successfully enabled module: " . $moduleName);
        } else {
            $sender->sendMessage(TF::RED . "Failed to enable module: " . $moduleName);
        }
    }

    private function handleModuleDisable(CommandSender $sender, string $moduleName): void {
        if (!$sender->hasPermission("valientfactions.module.disable")) {
            $sender->sendMessage(TF::RED . "You don't have permission to disable modules!");
            return;
        }

        $moduleManager = $this->plugin->getModuleManager();
        
        if ($moduleManager->disableModule($moduleName)) {
            $sender->sendMessage(TF::GREEN . "Successfully disabled module: " . $moduleName);
        } else {
            $sender->sendMessage(TF::RED . "Failed to disable module: " . $moduleName);
        }
    }

    private function handleReload(CommandSender $sender): void {
        if (!$sender->hasPermission("valientfactions.reload")) {
            $sender->sendMessage(TF::RED . "You don't have permission to reload the plugin!");
            return;
        }

        try {
            $this->plugin->reloadConfiguration();
            $sender->sendMessage(TF::GREEN . "Successfully reloaded ValientFactions configuration!");
        } catch (\Exception $e) {
            $sender->sendMessage(TF::RED . "Failed to reload configuration: " . $e->getMessage());
        }
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }
}
