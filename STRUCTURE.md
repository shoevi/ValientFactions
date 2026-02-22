# ValientFactions Plugin Structure

## Overview
Complete advanced PMMP 5 plugin skeleton with modular architecture.

## File Structure

```
ValientFactions/
├── plugin.yml                              # PMMP 5 plugin manifest
├── composer.json                           # Composer package definition
├── README.md                               # Main documentation
├── CONTRIBUTING.md                         # Development guide
├── .gitignore                             # Git ignore rules
│
├── resources/
│   └── config.yml                         # Default plugin configuration
│
└── src/ValientFactions/
    ├── Main.php                           # Main plugin class (PluginBase)
    │
    ├── command/
    │   └── VFactionsCommand.php           # /vfactions command handler
    │
    └── module/
        ├── ModuleInterface.php            # Module interface definition
        ├── BaseModule.php                 # Abstract base module class
        ├── ModuleManager.php              # Module lifecycle manager
        │
        └── modules/                       # Built-in modules
            └── armor/                     # Custom leather armor sets
                ├── ArmorPerk.php          # Perk enum (damage reduction, boost, etc.)
                ├── ArmorSet.php           # Abstract armor set base class
                ├── ArmorSetRegistry.php   # Stores and looks up set definitions
                ├── ArmorManager.php       # Resolves worn sets, computes stats, applies effects
                ├── ArmorEffectTask.php    # Repeating task for potion effect refresh
                ├── ArmorListener.php      # Combat event handler (reduction/boost/lifesteal)
                ├── ArmorModule.php        # Module entry point
                ├── command/
                │   └── ArmorCommand.php  # /vfarmor command
                └── sets/
                    ├── BlazeArmorSet.php  # Offense set (+25% dmg, Fire Resist)
                    ├── VoidArmorSet.php   # Sustain set (30% reduction, lifesteal)
                    ├── StormArmorSet.php  # Mobility set (Speed II, +20% dmg)
                    └── WarlordArmorSet.php # Tank set (40% reduction, 40% KB resist)
```

## Key Components

### 1. Main.php
- Extends `PluginBase`
- Initializes `ModuleManager` on enable
- Registers all built-in modules
- Enables modules based on config
- Provides `getModuleManager()` accessor
- Handles config reload functionality

### 2. Module System

#### ModuleInterface.php
- `getName(): string`
- `getDescription(): string`
- `onEnable(): void`
- `onDisable(): void`
- `isEnabled(): bool`

#### BaseModule.php
- Abstract implementation of ModuleInterface
- Protected `$enabled` state
- Protected `$plugin` reference
- Default enable/disable with logging
- Template for module lifecycle

#### ModuleManager.php
- `registerModule(ModuleInterface): void`
- `enableModule(string): bool`
- `disableModule(string): bool`
- `getModule(string): ?ModuleInterface`
- `getAllModules(): array`
- `getEnabledModules(): array`
- `isModuleEnabled(string): bool`

### 3. Commands

#### VFactionsCommand.php
- `/vfactions modules` - List all modules
- `/vfactions enable <module>` - Enable module
- `/vfactions disable <module>` - Disable module
- `/vfactions reload` - Reload config
- Aliases: `/vf`, `/factions`
- Implements `PluginOwned`
- Permission-based access control

### 4. Built-in Modules

#### ChatModule
- Implements Listener
- Intercepts PlayerChatEvent
- Skeleton for faction chat formatting
- Event registration/unregistration

#### ProtectionModule
- Implements Listener
- Handles BlockBreakEvent
- Handles BlockPlaceEvent
- Handles EntityDamageByEntityEvent
- Skeleton for land claim protection

#### PowerModule
- Implements Listener
- Tracks player power levels
- Handles PlayerJoinEvent
- Handles PlayerQuitEvent
- Handles PlayerDeathEvent
- Power loss on death system
- Skeleton for faction power

## Configuration

### plugin.yml
- Name: ValientFactions
- Version: 1.0.0
- API: 5.0.0
- Commands defined with aliases
- Permission hierarchy
- Author and description

### config.yml
```yaml
modules:
  Armor: true

settings:
  debug: false
  auto-save-interval: 300
```

## Permissions

- `valientfactions.command` - Main command access (op)
- `valientfactions.admin` - All admin features (op)
- `valientfactions.module.enable` - Enable modules (op)
- `valientfactions.module.disable` - Disable modules (op)
- `valientfactions.reload` - Reload config (op)

## Technical Requirements

- PHP 8.1+
- PocketMine-MP 5.0.0+
- strict_types=1 in all files
- Proper type hints throughout
- PSR-4 autoloading
- Event-driven architecture

## Features

✅ Modular architecture
✅ Runtime module enable/disable
✅ Config-based module control
✅ Comprehensive command system
✅ Permission-based access
✅ Event listener management
✅ Proper resource cleanup
✅ Debug logging support
✅ Extensible design

## Usage Example

1. Install plugin in server
2. Configure modules in config.yml
3. Start server (modules auto-enable)
4. Use `/vfactions modules` to list status
5. Use `/vfactions enable Chat` to enable module
6. Use `/vfactions reload` to apply config changes

## Development

See CONTRIBUTING.md for:
- Creating new modules
- Code standards
- Best practices
- Testing guidelines
- Submission process
