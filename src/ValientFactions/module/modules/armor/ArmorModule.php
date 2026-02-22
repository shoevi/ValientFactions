<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor;

use pocketmine\utils\TextFormat as TF;
use ValientFactions\Main;
use ValientFactions\module\BaseModule;
use ValientFactions\module\modules\armor\command\ArmorCommand;
use ValientFactions\module\modules\armor\sets\BlazeArmorSet;
use ValientFactions\module\modules\armor\sets\StormArmorSet;
use ValientFactions\module\modules\armor\sets\VoidArmorSet;
use ValientFactions\module\modules\armor\sets\WarlordArmorSet;

/**
 * Armor Module – custom leather armor sets with PvP perks.
 *
 * Registers four built-in sets (Blaze, Void, Storm, Warlord), each with
 * unique scaling perks depending on how many pieces the player wears.
 * All armor is leather but is stronger than Netherite through event-based
 * stat manipulation.
 *
 * Sub-systems wired up here:
 *  • ArmorSetRegistry  – stores every set definition
 *  • ArmorManager      – resolves worn sets and computes stat values
 *  • ArmorListener     – modifies combat events in real time
 *  • ArmorEffectTask   – refreshes persistent potion effects every 5 s
 *  • ArmorCommand      – /vfarmor in-game interface
 */
final class ArmorModule extends BaseModule {

    private ArmorSetRegistry $registry;
    private ArmorManager $armorManager;

    public function getName(): string {
        return "Armor";
    }

    public function getDescription(): string {
        return "Custom leather armor sets with PvP perks (damage reduction, damage boost, lifesteal, and more)";
    }

    public function onEnable(): void {
        // Build set registry and register all built-in sets
        $this->registry = new ArmorSetRegistry();
        $this->registry->register(new BlazeArmorSet());
        $this->registry->register(new VoidArmorSet());
        $this->registry->register(new StormArmorSet());
        $this->registry->register(new WarlordArmorSet());

        // Create the manager (depends on the populated registry)
        $this->armorManager = new ArmorManager($this->plugin, $this->registry);

        // Register combat event listener
        $this->plugin->getServer()->getPluginManager()->registerEvents(
            new ArmorListener($this->plugin, $this->armorManager),
            $this->plugin
        );

        // Schedule repeating effect refresh task every 100 ticks (5 s)
        $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ArmorEffectTask($this->plugin, $this->armorManager),
            100
        );

        // Register /vfarmor command
        $this->plugin->getServer()->getCommandMap()->register(
            "valientfactions",
            new ArmorCommand($this->plugin, $this->armorManager)
        );

        $this->plugin->getLogger()->info(
            TF::GREEN . "ArmorModule: registered "
            . count($this->registry->getAll()) . " armor sets"
        );

        parent::onEnable();
    }

    public function onDisable(): void {
        // Persistent effects are not forcibly removed; they will naturally expire
        // within their 10-second window after the task stops running.
        parent::onDisable();
    }

    public function getArmorManager(): ArmorManager {
        return $this->armorManager;
    }
}
