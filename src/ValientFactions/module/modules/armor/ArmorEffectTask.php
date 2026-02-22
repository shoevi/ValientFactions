<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\scheduler\Task;
use ValientFactions\Main;

/**
 * Runs every 100 ticks (5 s) and refreshes persistent potion effects for every
 * online player that is wearing a custom armor set.
 * Using 10-second effect durations keeps effects active between task ticks while
 * avoiding permanent effects.
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
            $this->armorManager->refreshEffects($player);
        }
    }
}
