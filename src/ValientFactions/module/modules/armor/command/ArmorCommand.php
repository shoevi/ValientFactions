<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\Main;
use ValientFactions\module\modules\armor\ArmorManager;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;

/**
 * /vfarmor – manage and receive custom leather armor sets.
 *
 * Subcommands:
 *   sets                           List all registered sets
 *   get <set> [slot|all]           Give yourself armor pieces (requires vf.armor)
 *   give <player> <set>            Give a player a full set (requires vf.armor.give)
 *   info                           Display your currently active set & perks
 */
final class ArmorCommand extends Command implements PluginOwned {

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        parent::__construct(
            "vfarmor",
            "ValientFactions custom armor command",
            "/vfarmor <sets|get|give|info> [args]",
            ["vfa"]
        );
        $this->setPermission("valientfactions.armor");
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        if ($args === []) {
            $this->sendUsage($sender);
            return true;
        }

        $sub  = strtolower(array_shift($args));

        switch ($sub) {
            case "sets":
            case "list":
                $this->handleSets($sender);
                break;

            case "get":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "This command can only be used in-game.");
                    return true;
                }
                if ($args === []) {
                    $sender->sendMessage(TF::RED . "Usage: /vfarmor get <set> [helmet|chestplate|leggings|boots|all]");
                    return true;
                }
                $this->handleGet($sender, $args[0], $args[1] ?? "all");
                break;

            case "give":
                if (!$sender->hasPermission("valientfactions.armor.give")) {
                    $sender->sendMessage(TF::RED . "You don't have permission to give armor sets.");
                    return true;
                }
                if (count($args) < 2) {
                    $sender->sendMessage(TF::RED . "Usage: /vfarmor give <player> <set>");
                    return true;
                }
                $this->handleGive($sender, $args[0], $args[1]);
                break;

            case "info":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "This command can only be used in-game.");
                    return true;
                }
                $this->handleInfo($sender);
                break;

            default:
                $this->sendUsage($sender);
                break;
        }

        return true;
    }

    // -------------------------------------------------------------------------

    private function sendUsage(CommandSender $sender): void {
        $sender->sendMessage(TF::GOLD . "=== ValientFactions Armor ===");
        $sender->sendMessage(TF::YELLOW . "/vfarmor sets"                        . TF::WHITE . " – List all armor sets");
        $sender->sendMessage(TF::YELLOW . "/vfarmor get <set> [slot|all]"        . TF::WHITE . " – Receive armor pieces");
        $sender->sendMessage(TF::YELLOW . "/vfarmor give <player> <set>"         . TF::WHITE . " – Give a full set to a player");
        $sender->sendMessage(TF::YELLOW . "/vfarmor info"                         . TF::WHITE . " – View your active set & perks");
    }

    private function handleSets(CommandSender $sender): void {
        $sets = $this->armorManager->getRegistry()->getAll();
        $sender->sendMessage(TF::GOLD . "=== Armor Sets (" . count($sets) . ") ===");
        foreach ($sets as $set) {
            $sender->sendMessage(
                $set->getLoreColor() . $set->getName()
                . TF::WHITE . " – "
                . TF::GRAY  . $set->getDescription()
            );
        }
    }

    private function handleGet(Player $player, string $setName, string $slot): void {
        $set = $this->armorManager->getRegistry()->find($setName);
        if ($set === null) {
            $player->sendMessage(TF::RED . "Unknown armor set: " . $setName);
            $player->sendMessage(TF::YELLOW . "Available: " . implode(", ", array_keys($this->armorManager->getRegistry()->getAll())));
            return;
        }

        if ($slot === "all") {
            $this->armorManager->giveFullSet($player, $set);
            $player->sendMessage(TF::GREEN . "Equipped full " . $set->getLoreColor() . $set->getName() . TF::GREEN . " set!");
            return;
        }

        if (!in_array($slot, ArmorSet::ALL_SLOTS, true)) {
            $player->sendMessage(TF::RED . "Invalid slot. Use: helmet, chestplate, leggings, boots, or all");
            return;
        }

        $player->getInventory()->addItem($set->getItemForSlot($slot));
        $player->sendMessage(TF::GREEN . "Received " . $set->getLoreColor() . $set->getName() . " " . ucfirst($slot) . TF::GREEN . "!");
    }

    private function handleGive(CommandSender $sender, string $targetName, string $setName): void {
        $target = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($target === null) {
            $sender->sendMessage(TF::RED . "Player not found: " . $targetName);
            return;
        }

        $set = $this->armorManager->getRegistry()->find($setName);
        if ($set === null) {
            $sender->sendMessage(TF::RED . "Unknown armor set: " . $setName);
            return;
        }

        $this->armorManager->giveFullSet($target, $set);
        $target->sendMessage(TF::GREEN . "You received the " . $set->getLoreColor() . $set->getName() . TF::GREEN . " armor set!");
        $sender->sendMessage(TF::GREEN . "Gave " . $set->getLoreColor() . $set->getName() . TF::GREEN . " armor to " . $target->getName() . "!");
    }

    private function handleInfo(Player $player): void {
        $info = $this->armorManager->getPlayerSetInfo($player);
        if ($info === null) {
            $player->sendMessage(TF::YELLOW . "You are not wearing any custom armor set.");
            return;
        }

        [$set, $pieces] = $info;
        $perks = $set->getPerksForPieces($pieces);

        $player->sendMessage(TF::GOLD . "=== Active: " . $set->getLoreColor() . $set->getName() . TF::GOLD . " ===");
        $player->sendMessage(TF::YELLOW . "Pieces worn: " . TF::WHITE . $pieces . "/4");
        $player->sendMessage(TF::YELLOW . "Active perks:");

        foreach ($perks as $perkValue => $amount) {
            $perk = ArmorPerk::from($perkValue);
            $line = match ($perk) {
                ArmorPerk::DAMAGE_REDUCTION    => sprintf(TF::AQUA    . "  Damage Reduction: "     . TF::WHITE . "%.0f%%", $amount * 100),
                ArmorPerk::DAMAGE_BOOST        => sprintf(TF::RED     . "  Damage Boost: "         . TF::WHITE . "+%.0f%%", $amount * 100),
                ArmorPerk::KNOCKBACK_RESISTANCE => sprintf(TF::GREEN  . "  KB Resistance: "        . TF::WHITE . "%.0f%%", $amount * 100),
                ArmorPerk::SPEED               => TF::GREEN           . "  Speed: "                . TF::WHITE . (int) $amount,
                ArmorPerk::REGENERATION        => TF::GREEN           . "  Regeneration: "         . TF::WHITE . (int) $amount,
                ArmorPerk::FIRE_RESISTANCE     => TF::GOLD            . "  Fire Resistance",
                ArmorPerk::LIFESTEAL           => sprintf(TF::DARK_RED . "  Lifesteal: "           . TF::WHITE . "%.0f%%", $amount * 100),
                ArmorPerk::STRENGTH            => TF::RED             . "  Strength: "             . TF::WHITE . (int) $amount,
            };
            $player->sendMessage($line);
        }
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }
}
