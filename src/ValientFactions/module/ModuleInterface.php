<?php

declare(strict_types=1);

namespace ValientFactions\module;

interface ModuleInterface {

    /**
     * Get the name of the module
     */
    public function getName(): string;

    /**
     * Get the description of the module
     */
    public function getDescription(): string;

    /**
     * Called when the module is enabled
     */
    public function onEnable(): void;

    /**
     * Called when the module is disabled
     */
    public function onDisable(): void;

    /**
     * Check if the module is currently enabled
     */
    public function isEnabled(): bool;
}
