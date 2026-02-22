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
 * /kit command - Opens the main kit GUI with category selection.
 */
final class KitCommand extends Command implements PluginOwned {

    private Main $plugin;
    private KitManager $kitManager;

    public function __construct(Main $plugin, KitManager $kitManager) {
        parent::__construct(
            "kit",
            "Open the kit menu",
            "/kit",
            ["kits"]
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

        $this->openMainMenu($sender);
        return true;
    }

    /**
     * Open the main kit category selection menu.
     *
     * @param Player $player
     */
    public function openMainMenu(Player $player): void {
        $form = new SimpleButtonForm(
            TF::BOLD . TF::DARK_PURPLE . "Kit Menu",
            TF::GRAY . "Select a kit category:",
            function(Player $player, int $buttonIndex): void {
                match($buttonIndex) {
                    0 => $this->openCategoryMenu($player, "kits", "Regular Kits"),
                    1 => $this->openCategoryMenu($player, "gkits", "God Kits"),
                    2 => $this->openCategoryMenu($player, "vkits", "VIP Kits"),
                    default => null
                };
            }
        );

        $form->addButton(TF::GREEN . "Regular Kits");
        $form->addButton(TF::GOLD . "God Kits");
        $form->addButton(TF::LIGHT_PURPLE . "VIP Kits");

        $player->sendForm($form);
    }

    /**
     * Open a specific category menu.
     *
     * @param Player $player
     * @param string $category
     * @param string $title
     */
    public function openCategoryMenu(Player $player, string $category, string $title): void {
        $kits = $this->kitManager->getKitsByCategory($category);

        if (empty($kits)) {
            $player->sendMessage(TF::RED . "No kits available in this category!");
            return;
        }

        $kitNames = array_keys($kits);

        $form = new SimpleButtonForm(
            TF::BOLD . TF::DARK_PURPLE . $title,
            TF::GRAY . "Select a kit to claim:",
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
