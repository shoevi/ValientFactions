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
use ValientFactions\module\modules\armor\ArmorStatsTracker;

/**
 * /vfarmor  – manage and receive custom leather armor sets.
 * /vfability – activate the active ability of the wearer's current set.
 *
 * Subcommands:
 *   sets                       List all registered sets
 *   get <set> [slot|all]       Give yourself armor pieces
 *   give <player> <set>        Give a player a full set  (requires vf.armor.give)
 *   info                       Show your active set, pieces worn, and all perks
 *   inspect <player>           Show another player's set info  (requires vf.armor)
 *   stats [player]             View session combat stats
 *   ability                    Activate your set's active ability  (alias: /vfability)
 */
final class ArmorCommand extends Command implements PluginOwned {

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        parent::__construct(
            "vfarmor",
            "ValientFactions custom armor command",
            "/vfarmor <sets|get|give|info|inspect|stats|ability> [args]",
            ["vfa", "vfability"]
        );
        $this->setPermission("valientfactions.armor");
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            return false;
        }

        // /vfability is an alias that directly triggers the ability
        if (strtolower($commandLabel) === "vfability") {
            if (!$sender instanceof Player) {
                $sender->sendMessage(TF::RED . "This command can only be used in-game.");
                return true;
            }
            $this->handleAbility($sender);
            return true;
        }

        if ($args === []) {
            $this->sendUsage($sender);
            return true;
        }

        $sub = strtolower(array_shift($args));

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
                $this->handleInfo($sender, $sender);
                break;

            case "inspect":
                if (empty($args)) {
                    $sender->sendMessage(TF::RED . "Usage: /vfarmor inspect <player>");
                    return true;
                }
                $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
                if ($target === null) {
                    $sender->sendMessage(TF::RED . "Player not found: " . $args[0]);
                    return true;
                }
                $this->handleInfo($sender, $target);
                break;

            case "stats":
                $target = null;
                if (!empty($args)) {
                    $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
                    if ($target === null) {
                        $sender->sendMessage(TF::RED . "Player not found: " . $args[0]);
                        return true;
                    }
                } elseif (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "Console must specify a player: /vfarmor stats <player>");
                    return true;
                } else {
                    $target = $sender;
                }
                $this->handleStats($sender, $target);
                break;

            case "ability":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "This command can only be used in-game.");
                    return true;
                }
                $this->handleAbility($sender);
                break;

            default:
                $this->sendUsage($sender);
                break;
        }

        return true;
    }

    // =========================================================================
    // Subcommand handlers
    // =========================================================================

    private function sendUsage(CommandSender $sender): void {
        $sender->sendMessage(TF::GOLD . "=== ValientFactions Armor ===");
        $sender->sendMessage(TF::YELLOW . "/vfarmor sets"                         . TF::WHITE . " – List all armor sets");
        $sender->sendMessage(TF::YELLOW . "/vfarmor get <set> [slot|all]"         . TF::WHITE . " – Receive armor pieces");
        $sender->sendMessage(TF::YELLOW . "/vfarmor give <player> <set>"          . TF::WHITE . " – Give a full set to a player");
        $sender->sendMessage(TF::YELLOW . "/vfarmor info"                          . TF::WHITE . " – View your active set & perks");
        $sender->sendMessage(TF::YELLOW . "/vfarmor inspect <player>"             . TF::WHITE . " – Inspect another player's set");
        $sender->sendMessage(TF::YELLOW . "/vfarmor stats [player]"               . TF::WHITE . " – View combat stats");
        $sender->sendMessage(TF::YELLOW . "/vfarmor ability  §8or §e/vfability"   . TF::WHITE . " – Activate your set's ability");
    }

    private function handleSets(CommandSender $sender): void {
        $sets = $this->armorManager->getRegistry()->getAll();
        $sender->sendMessage(TF::GOLD . "=== Armor Sets (" . count($sets) . ") – Requires 4/4 Pieces ===");
        foreach ($sets as $set) {
            $sender->sendMessage(
                $set->getLoreColor() . "§l" . $set->getName()
                . TF::RESET . TF::WHITE . " – "
                . TF::GRAY  . $set->getDescription()
            );
            // Show top perks in one line
            $perkParts = [];
            foreach ($set->getPerks() as $perkValue => $amount) {
                $perk = ArmorPerk::from($perkValue);
                $perkParts[] = $this->shortPerkLabel($perk, $amount);
                if (count($perkParts) >= 3) {
                    $perkParts[] = "…";
                    break;
                }
            }
            $sender->sendMessage(TF::DARK_GRAY . "  Perks: " . TF::GRAY . implode("  ", $perkParts));
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
            $player->sendMessage($set->getLoreColor() . "✦ Equipped full " . $set->getName() . " set! §7(4/4 pieces)");
            return;
        }

        if (!in_array($slot, ArmorSet::ALL_SLOTS, true)) {
            $player->sendMessage(TF::RED . "Invalid slot. Use: helmet, chestplate, leggings, boots, or all");
            return;
        }

        $player->getInventory()->addItem($set->getItemForSlot($slot));
        $player->sendMessage($set->getLoreColor() . "✦ Received " . $set->getName() . " " . ucfirst($slot) . TF::GREEN . "!");
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
        $target->sendMessage($set->getLoreColor() . "✦ You received the " . $set->getName() . " armor set! §7(4/4)");
        $sender->sendMessage(TF::GREEN . "Gave " . $set->getLoreColor() . $set->getName() . TF::GREEN . " armor to " . $target->getName() . "!");
    }

    private function handleInfo(CommandSender $viewer, Player $target): void {
        $isOwn = ($viewer instanceof Player && $viewer->getUniqueId() === $target->getUniqueId());
        $prefix = $isOwn ? "Your" : $target->getName() . "'s";

        $info = $this->armorManager->getPlayerSetInfo($target);
        if ($info === null) {
            $viewer->sendMessage(TF::YELLOW . $prefix . " armor: §cNo custom set");
            return;
        }

        [$set, $pieces] = $info;
        $isActive = ($pieces === 4);
        $viewer->sendMessage(TF::GOLD . "=== " . $prefix . " Armor: " . $set->getLoreColor() . $set->getName() . TF::GOLD . " ===");

        // Pieces / status
        $statusColor = $isActive ? TF::GREEN : TF::RED;
        $viewer->sendMessage(TF::YELLOW . "Pieces: " . $statusColor . $pieces . "/4 "
            . ($isActive ? TF::GREEN . "✔ ACTIVE" : TF::RED . "✖ INACTIVE – missing: " . implode(", ", $this->armorManager->getMissingSlots($target))));

        if (!$isActive) {
            $viewer->sendMessage(TF::RED . "Perks are LOCKED until all 4 pieces are equipped.");
            return;
        }

        // Perks
        $viewer->sendMessage(TF::YELLOW . "Active Perks:");
        foreach ($set->getPerks() as $perkValue => $amount) {
            $perk = ArmorPerk::from($perkValue);
            $viewer->sendMessage("  " . $this->fullPerkLine($perk, $amount));
        }

        // Combat trigger
        $trigger = $set->getCombatTriggerDescription();
        if ($trigger !== null) {
            $viewer->sendMessage(TF::DARK_PURPLE . "Passive Trigger: " . TF::GRAY . $trigger);
        }

        // Ability
        $ability = $set->getAbility();
        if ($ability !== null && $isOwn) {
            $cdTicks = $this->armorManager->getAbilityCooldownTicks($target);
            $cdText  = $cdTicks > 0 ? TF::RED . (int) ceil($cdTicks / 20) . "s" : TF::GREEN . "READY";
            $viewer->sendMessage(TF::AQUA . "Ability: §f" . $ability->getName() . "  " . $cdText . TF::GRAY . "  /vfability");
        } elseif ($ability !== null) {
            $viewer->sendMessage(TF::AQUA . "Ability: §f" . $ability->getName());
        }
    }

    private function handleStats(CommandSender $viewer, Player $target): void {
        $isOwn  = ($viewer instanceof Player && $viewer->getUniqueId() === $target->getUniqueId());
        $prefix = $isOwn ? "Your" : $target->getName() . "'s";
        $stats  = $this->armorManager->getStats()->getAllStats($target);

        if (empty($stats)) {
            $viewer->sendMessage(TF::YELLOW . $prefix . " session stats: §7No data yet.");
            return;
        }

        $viewer->sendMessage(TF::GOLD . "=== " . $prefix . " Armor Stats (Session) ===");
        $map = [
            ArmorStatsTracker::HITS_DEALT           => ["§c", "Hits Dealt"],
            ArmorStatsTracker::HITS_TAKEN           => ["§c", "Hits Taken"],
            ArmorStatsTracker::DAMAGE_DEALT_BOOSTED => ["§c", "Bonus Dmg Dealt"],
            ArmorStatsTracker::DAMAGE_ABSORBED      => ["§a", "Damage Absorbed"],
            ArmorStatsTracker::LIFESTEAL_HEALED     => ["§d", "HP Healed (Lifesteal)"],
            ArmorStatsTracker::THORNS_REFLECTED     => ["§4", "Dmg Reflected (Thorns)"],
            ArmorStatsTracker::KILLS_WITH_SET       => ["§6", "Kills With Set"],
            ArmorStatsTracker::ABILITY_USES         => ["§b", "Ability Uses"],
            ArmorStatsTracker::COMBAT_TRIGGERS      => ["§5", "Combat Triggers Fired"],
        ];

        foreach ($map as $key => [$color, $label]) {
            if (isset($stats[$key]) && $stats[$key] > 0.0) {
                $value = $stats[$key];
                $display = (fmod($value, 1.0) === 0.0) ? (int) $value : round($value, 2);
                $viewer->sendMessage("  " . $color . $label . ": §f" . $display);
            }
        }
    }

    private function handleAbility(Player $player): void {
        [$success, $message] = $this->armorManager->activateAbility($player);
        if ($message !== "") {
            $player->sendMessage($message);
        }
        if ($success) {
            $this->armorManager->getStats()->increment($player, ArmorStatsTracker::ABILITY_USES);
        }
    }

    // =========================================================================
    // Formatting helpers
    // =========================================================================

    /** Short inline perk label (used in /vfarmor sets). */
    private function shortPerkLabel(ArmorPerk $perk, float $amount): string {
        return match ($perk) {
            ArmorPerk::DAMAGE_REDUCTION     => sprintf("§a%.0f%% DR",       $amount * 100),
            ArmorPerk::DAMAGE_BOOST         => sprintf("§c+%.0f%% Dmg",     $amount * 100),
            ArmorPerk::THORNS               => sprintf("§4%.0f%% Thorns",   $amount * 100),
            ArmorPerk::KNOCKBACK_RESISTANCE => sprintf("§a%.0f%% KB",       $amount * 100),
            ArmorPerk::FALL_DAMAGE_REDUCTION => sprintf("§a%.0f%% Fall",    $amount * 100),
            ArmorPerk::EXPLOSION_RESISTANCE  => sprintf("§a%.0f%% Expl",    $amount * 100),
            ArmorPerk::SLOWNESS_ON_HIT      => "§9Slow",
            ArmorPerk::LIFESTEAL            => sprintf("§d%.0f%% LS",       $amount * 100),
            ArmorPerk::SPEED               => "§aSpd" . (int) $amount,
            ArmorPerk::REGENERATION        => "§aRegen" . (int) $amount,
            ArmorPerk::STRENGTH            => "§cStr" . (int) $amount,
            ArmorPerk::RESISTANCE          => "§aRes" . (int) $amount,
            ArmorPerk::JUMP_BOOST          => "§aJmp" . (int) $amount,
            ArmorPerk::ABSORPTION          => "§6Abs" . (int) $amount,
            ArmorPerk::FIRE_RESISTANCE     => "§6FireRes",
        };
    }

    /** Full coloured perk line (used in /vfarmor info). */
    private function fullPerkLine(ArmorPerk $perk, float $amount): string {
        return match ($perk) {
            ArmorPerk::DAMAGE_REDUCTION     => sprintf(TF::AQUA  . "Damage Reduction: "      . TF::WHITE . "%.0f%%",  $amount * 100),
            ArmorPerk::DAMAGE_BOOST         => sprintf(TF::RED   . "Damage Boost: "           . TF::WHITE . "+%.0f%%", $amount * 100),
            ArmorPerk::THORNS               => sprintf(TF::DARK_RED . "Thorns: "              . TF::WHITE . "%.0f%% reflected", $amount * 100),
            ArmorPerk::KNOCKBACK_RESISTANCE => sprintf(TF::GREEN . "KB Resistance: "          . TF::WHITE . "%.0f%%",  $amount * 100),
            ArmorPerk::FALL_DAMAGE_REDUCTION => sprintf(TF::GREEN . "Fall Dmg Reduction: "   . TF::WHITE . "%.0f%%",  $amount * 100),
            ArmorPerk::EXPLOSION_RESISTANCE  => sprintf(TF::GREEN . "Explosion Resistance: " . TF::WHITE . "%.0f%%",  $amount * 100),
            ArmorPerk::SLOWNESS_ON_HIT      => sprintf(TF::BLUE  . "Slowness on Hit: "        . TF::WHITE . "%.1fs",  $amount / 20),
            ArmorPerk::LIFESTEAL            => sprintf(TF::LIGHT_PURPLE . "Lifesteal: "       . TF::WHITE . "%.0f%%", $amount * 100),
            ArmorPerk::SPEED               => TF::GREEN           . "Speed "         . $this->roman((int) $amount),
            ArmorPerk::REGENERATION        => TF::GREEN           . "Regeneration "  . $this->roman((int) $amount),
            ArmorPerk::STRENGTH            => TF::RED             . "Strength "      . $this->roman((int) $amount),
            ArmorPerk::RESISTANCE          => TF::GREEN           . "Resistance "    . $this->roman((int) $amount),
            ArmorPerk::JUMP_BOOST          => TF::GREEN           . "Jump Boost "    . $this->roman((int) $amount),
            ArmorPerk::ABSORPTION          => TF::GOLD            . "Absorption "    . $this->roman((int) $amount),
            ArmorPerk::FIRE_RESISTANCE     => TF::GOLD            . "Fire Resistance",
        };
    }

    private function roman(int $n): string {
        return match ($n) {
            1 => "I", 2 => "II", 3 => "III", 4 => "IV", 5 => "V",
            default => (string) $n,
        };
    }

    public function getOwningPlugin(): Plugin {
        return $this->plugin;
    }
}
