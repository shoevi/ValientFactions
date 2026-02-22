# ValientFactions

Advanced modular faction system for PocketMine-MP 5.

## Features

- **Modular Architecture**: Enable/disable features as needed
- **Built-in Modules**:
  - **Chat Module**: Faction chat formatting and channels
  - **Protection Module**: Land claims and territory protection
  - **Power Module**: Player and faction power system

## Installation

1. Download the plugin phar file
2. Place it in your server's `plugins` directory
3. Restart the server
4. Configure modules in `plugin_data/ValientFactions/config.yml`

## Commands

- `/vfactions modules` - List all modules and their status
- `/vfactions enable <module>` - Enable a specific module
- `/vfactions disable <module>` - Disable a specific module
- `/vfactions reload` - Reload the plugin configuration

**Aliases**: `/vf`, `/factions`

## Permissions

- `valientfactions.command` - Access to main command (default: op)
- `valientfactions.admin` - All administrative permissions (default: op)
- `valientfactions.module.enable` - Enable modules (default: op)
- `valientfactions.module.disable` - Disable modules (default: op)
- `valientfactions.reload` - Reload configuration (default: op)

## Configuration

Edit `plugin_data/ValientFactions/config.yml`:

```yaml
modules:
  Chat: true          # Enable/disable chat module
  Protection: true    # Enable/disable protection module
  Power: true         # Enable/disable power module

settings:
  debug: false
  auto-save-interval: 300
```

## Module System

### Creating Custom Modules

1. Create a new class extending `BaseModule`
2. Implement `getName()` and `getDescription()` methods
3. Override `onEnable()` and `onDisable()` for module lifecycle
4. Register your module in `Main.php`

Example:

```php
<?php

declare(strict_types=1);

namespace ValientFactions\module\modules;

use ValientFactions\module\BaseModule;

final class CustomModule extends BaseModule {

    public function getName(): string {
        return "Custom";
    }

    public function getDescription(): string {
        return "My custom module";
    }

    public function onEnable(): void {
        parent::onEnable();
        // Your initialization code here
    }

    public function onDisable(): void {
        parent::onDisable();
        // Your cleanup code here
    }
}
```

## Architecture

```
ValientFactions/
├── plugin.yml                          # Plugin manifest
├── resources/
│   └── config.yml                      # Default configuration
└── src/ValientFactions/
    ├── Main.php                        # Main plugin class
    ├── command/
    │   └── VFactionsCommand.php        # Main command handler
    └── module/
        ├── ModuleInterface.php         # Module interface
        ├── BaseModule.php              # Abstract base module
        ├── ModuleManager.php           # Module management
        └── modules/
            ├── ChatModule.php          # Chat system
            ├── ProtectionModule.php    # Land protection
            └── PowerModule.php         # Power system
```

## Development

### Requirements

- PHP 8.1+
- PocketMine-MP 5.0.0+

### Module Lifecycle

1. **Registration**: Modules are registered in `Main::registerBuiltInModules()`
2. **Enabling**: Modules are enabled based on config in `Main::enableConfiguredModules()`
3. **Runtime**: Modules handle events and provide functionality
4. **Disabling**: Modules cleanup resources in `onDisable()`

### Best Practices

- Always call `parent::onEnable()` and `parent::onDisable()` in modules
- Unregister event listeners in `onDisable()` to prevent memory leaks
- Use the plugin's logger for debugging and info messages
- Implement proper error handling in module methods

## License

This plugin skeleton is provided as-is for educational and development purposes.

## Support

For issues, questions, or contributions, please visit the GitHub repository.
