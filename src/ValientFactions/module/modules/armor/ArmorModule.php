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
 * Armor Module – custom leather armor sets with advanced PvP perks.
 *
 * Rules:
 *  • ALL 4 pieces of the same set must be worn simultaneously for any perk to activate.
 *  • No partial-set bonuses exist.
 *
 * Sub-systems wired up here:
 *  • ArmorSetRegistry   – stores every set definition
 *  • ArmorStatsTracker  – session-scoped per-player combat stats
 *  • ArmorManager       – set resolution, caching, ability cooldowns, combat triggers
 *  • ArmorListener      – real-time combat event handling
 *  • ArmorEffectTask    – cache refresh + effect application every 5 s
 *  • ArmorCommand       – /vfarmor + /vfability in-game interface
 */
final class ArmorModule extends BaseModule {

    private ArmorSetRegistry $registry;
    private ArmorManager $armorManager;
    private ArmorStatsTracker $stats;

    public function getName(): string {
        return "Armor";
    }

    public function getDescription(): string {
        return "Custom leather armor sets – stronger than Netherite – with active abilities, combat triggers, lifesteal, thorns, and more. Requires all 4 pieces.";
    }

    public function onEnable(): void {
        // Build set registry
        $this->registry = new ArmorSetRegistry();
        $this->registry->register(new BlazeArmorSet());
        $this->registry->register(new VoidArmorSet());
        $this->registry->register(new StormArmorSet());
        $this->registry->register(new WarlordArmorSet());

        // Stats tracker
        $this->stats = new ArmorStatsTracker();

        // Manager (depends on registry + stats)
        $this->armorManager = new ArmorManager($this->plugin, $this->registry, $this->stats);

        // Register combat + death + quit listener
        $this->plugin->getServer()->getPluginManager()->registerEvents(
            new ArmorListener($this->plugin, $this->armorManager),
            $this->plugin
        );

        // Effect refresh + cache + notification task every 100 ticks (5 s)
        $this->plugin->getScheduler()->scheduleRepeatingTask(
            new ArmorEffectTask($this->plugin, $this->armorManager),
            100
        );

        // Register commands
        $this->plugin->getServer()->getCommandMap()->register(
            "valientfactions",
            new ArmorCommand($this->plugin, $this->armorManager)
        );

        $this->plugin->getLogger()->info(
            TF::GREEN . "ArmorModule: registered " . count($this->registry->getAll()) . " armor sets"
        );

        parent::onEnable();
    }

    public function onDisable(): void {
        // Persistent effects will naturally expire within their 10 s window.
        parent::onDisable();
    }

    public function getArmorManager(): ArmorManager {
        return $this->armorManager;
    }

    public function getStats(): ArmorStatsTracker {
        return $this->stats;
    }
}
