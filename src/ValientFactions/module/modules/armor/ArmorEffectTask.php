<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\Main;

/**
 * Runs every 100 ticks (5 seconds).
 *
 * Each cycle for every online player:
 *  1. Refresh the per-player set cache via {@see ArmorManager::updateCache()}.
 *  2. On set-change: send equip/unequip title + fire ON_EQUIP triggers.
 *  3. Refresh persistent potion-effect perks.
 *  4. Fire ON_LOW_HP triggers (when HP ≤ 30%).
 *  5. Display an actionbar tip showing the active set and trigger cooldowns.
 */
final class ArmorEffectTask extends Task {

    /**
     * HP fraction at or below which ON_LOW_HP triggers are dispatched.
     *
     * Note: This is intentionally lower than VoidShroud's ON_HIT_TAKEN threshold
     * (35%) because ON_LOW_HP fires periodically (every 5 s) and is designed for
     * slow passive reactions.  ON_HIT_TAKEN triggers like VoidShroud fire
     * immediately during combat and use a higher threshold for faster response.
     */
    private const LOW_HP_THRESHOLD = 0.30;

    private Main $plugin;
    private ArmorManager $armorManager;

    public function __construct(Main $plugin, ArmorManager $armorManager) {
        $this->plugin       = $plugin;
        $this->armorManager = $armorManager;
    }

    public function onRun(): void {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $changed   = $this->armorManager->updateCache($player);
            $activeSet = $this->armorManager->getCachedActiveSet($player);

            // --- Equip / unequip notifications and ON_EQUIP triggers ----------
            if ($changed) {
                if ($activeSet !== null) {
                    $player->sendTitle(
                        $activeSet->getLoreColor() . "✦ " . $activeSet->getName() . " Set Active ✦",
                        TF::GRAY . "All 4 pieces equipped – abilities now active",
                        10, 40, 15
                    );
                    // Fire ON_EQUIP triggers
                    $this->armorManager->fireEquipTriggers($player);
                } else {
                    $info = $this->armorManager->getPlayerSetInfo($player);
                    if ($info !== null) {
                        [$set, $pieces] = $info;
                        $missing = $this->armorManager->getMissingSlots($player);
                        $player->sendTitle(
                            TF::RED . "✖ " . $set->getName() . " Set Incomplete",
                            TF::GRAY . $pieces . "/4 pieces – missing: " . implode(", ", $missing),
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

            // --- Refresh persistent effects -----------------------------------
            $this->armorManager->refreshEffects($player);

            // --- ON_LOW_HP triggers -------------------------------------------
            if ($activeSet !== null) {
                $hpFraction = $player->getMaxHealth() > 0
                    ? $player->getHealth() / $player->getMaxHealth()
                    : 1.0;
                if ($hpFraction <= self::LOW_HP_THRESHOLD) {
                    $this->armorManager->fireLowHpTriggers($player);
                }
            }

            // --- Actionbar: active set + trigger cooldowns --------------------
            if ($activeSet !== null) {
                $tip = $activeSet->getLoreColor() . "✦ " . $activeSet->getName() . " Set";

                $cdInfo = $this->armorManager->getPrimaryTriggerCooldown($player);
                if ($cdInfo !== null) {
                    [$triggerName, $remaining] = $cdInfo;
                    if ($remaining > 0) {
                        $cdSec = (int) ceil($remaining / 20);
                        $tip  .= TF::GRAY . " │ " . TF::RED . $triggerName . ": " . $cdSec . "s";
                    } else {
                        $tip .= TF::GRAY . " │ " . TF::GREEN . $triggerName . ": READY";
                    }
                }

                $player->sendTip($tip);
            }
        }
    }
}
