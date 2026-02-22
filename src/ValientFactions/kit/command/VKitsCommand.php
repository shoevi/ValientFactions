<?php

declare(strict_types=1);

namespace ValientFactions\kit\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\form\SimpleButtonForm;
use ValientFactions\kit\KitManager;
use ValientFactions\Main;

/**
 * /vkits command - Opens the VIP Kits category directly.
 */
final class VKitsCommand extends Command implements PluginOwned {

    private Main $plugin;
    private KitManager $kitManager;

    public function __construct(Main $plugin, KitManager $kitManager) {
        parent::__construct(
            "vkits",
            "Open the VIP Kits menu",
            "/vkits",
            ["vkit"]
        );
        $this->setPermission("valientfactions.kit");
        $this->plugin = $plugin;
        $this->kitManager = $kitManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$sender instanceof Player) {
            $sender->sendMessage(TF::RED . "This command can only be used in-game.");
            return true;
        }

        $this->openVKitsMenu($sender);
        return true;
    }

    /**
     * Open the VIP Kits menu.
     *
     * @param Player $player
     */
    private function openVKitsMenu(Player $player): void {
        $kits = $this->kitManager->getKitsByCategory("vkits");

        if (empty($kits)) {
            $player->sendMessage(TF::RED . "No VIP Kits available!");
            return;
        }

        $kitNames = array_keys($kits);

        $form = new SimpleButtonForm(
            TF::BOLD . TF::LIGHT_PURPLE . "VIP Kits",
            TF::GRAY . "Select a VIP Kit to claim:",
            function(Player $player, int $buttonIndex) use ($kitNames): void {
                $kitName = $kitNames[$buttonIndex] ?? null;
                if ($kitName === null) {
                    return;
                }

                $kit = $this->kitManager->getKit($kitName);
                if ($kit === null) {
                    $player->sendMessage(TF::RED . "Kit not found!");
                    return;
                }

                $this->kitManager->claimKit($player, $kit);
            }
        );

        foreach ($kits as $kit) {
            $remaining = $this->kitManager->getRemainingCooldown($player, $kit);
            if ($remaining > 0) {
                $cooldownText = TF::RED . " (Cooldown: " . $this->kitManager->formatTime($remaining) . ")";
            } else {
                $cooldownText = TF::GREEN . " (Available)";
            }

            $form->addButton(TF::AQUA . $kit->getName() . $cooldownText);
        }

        $player->sendForm($form);
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }
}
