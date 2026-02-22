<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\Main;

/**
 * Runs every 100 ticks (5 s).
 *
 * Per tick:
 *  1. Updates the per-player set cache via {@see ArmorManager::updateCache()}.
 *  2. Fires equip / unequip title notifications when the set changes.
 *  3. Refreshes persistent potion effects for players with a complete (4/4) set.
 *  4. Sends an actionbar tip showing the active set name and ability cooldown.
 */
final class ArmorEffectTask extends Task {

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    public function onRun(): void {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $changed = $this->armorManager->updateCache($player);

            $activeSet = $this->armorManager->getCachedActiveSet($player);

            // --- Notify on set change -------------------------------------------
            if ($changed) {
                if ($activeSet !== null) {
                    // Full set equipped
                    $player->sendTitle(
                        $activeSet->getLoreColor() . "✦ " . $activeSet->getName() . " Set Active ✦",
                        TF::GRAY . "All 4 pieces equipped – perks unlocked",
                        10, 40, 15
                    );
                } else {
                    // Set removed / broken
                    $info = $this->armorManager->getPlayerSetInfo($player);
                    if ($info !== null) {
                        [$set, $pieces] = $info;
                        $missing = $this->armorManager->getMissingSlots($player);
                        $player->sendTitle(
                            TF::RED . "✖ " . $set->getName() . " Set Incomplete",
                            TF::GRAY . ($pieces) . "/4 pieces – missing: " . implode(", ", $missing),
                            10, 40, 15
                        );
                    } else {
                        $player->sendTitle(
                            TF::DARK_GRAY . "Armor Deactivated",
                            TF::GRAY . "No custom set equipped",
                            5, 25, 10
                        );
                    }
                }
            }

            // --- Refresh persistent effects ------------------------------------
            $this->armorManager->refreshEffects($player);

            // --- Actionbar tip (active set + ability cooldown) -----------------
            if ($activeSet !== null) {
                $ability = $activeSet->getAbility();
                $tip     = $activeSet->getLoreColor() . "✦ " . $activeSet->getName() . " Set";

                if ($ability !== null) {
                    $cdTicks = $this->armorManager->getAbilityCooldownTicks($player);
                    if ($cdTicks > 0) {
                        $cdSec = (int) ceil($cdTicks / 20);
                        $tip  .= TF::GRAY . " │ " . TF::RED . $ability->getName() . ": " . $cdSec . "s";
                    } else {
                        $tip .= TF::GRAY . " │ " . TF::GREEN . $ability->getName() . ": READY §7(/vfability)";
                    }
                }

                $player->sendTip($tip);
            }
        }
    }
}
