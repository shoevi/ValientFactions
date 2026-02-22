# ValientFactions Quick Reference

## Quick Start

```bash
# 1. Place plugin in PocketMine-MP plugins directory
cp -r ValientFactions /path/to/pmmp/plugins/

# 2. Start your server
cd /path/to/pmmp && ./start.sh

# 3. Plugin auto-loads with modules enabled per config
```

## Command Cheat Sheet

```
/vfactions modules                 # List all modules
/vf modules                        # Same (alias)
/factions modules                  # Same (alias)

/vfactions enable Armor             # Enable Armor module
/vfactions disable Armor            # Disable Armor module
/vfactions reload                   # Reload config & modules
```

## Module Quick Add

```php
// 1. Create: src/ValientFactions/module/modules/NewModule.php
<?php
declare(strict_types=1);
namespace ValientFactions\module\modules;

use ValientFactions\module\BaseModule;

final class NewModule extends BaseModule {
    public function getName(): string { return "New"; }
    public function getDescription(): string { return "My new module"; }
    
    public function onEnable(): void {
        parent::onEnable();
        // Your code here
    }
    
    public function onDisable(): void {
        parent::onDisable();
        // Cleanup here
    }
}

// 2. Register in Main.php -> registerBuiltInModules()
$this->moduleManager->registerModule(new NewModule($this));

// 3. Add to resources/config.yml
modules:
  Armor: true
  New: true
```

## Common Patterns

### Access Plugin Instance
```php
$this->plugin->getServer()
$this->plugin->getLogger()
$this->plugin->getConfig()
$this->plugin->getDataFolder()
```

### Register Event Listener
```php
public function onEnable(): void {
    parent::onEnable();
    $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
}
```

### Unregister Events
```php
public function onDisable(): void {
    parent::onDisable();
    PlayerChatEvent::getHandlers()->unregister($this);
}
```

### Schedule Task
```php
use pocketmine\scheduler\ClosureTask;

$this->plugin->getScheduler()->scheduleRepeatingTask(
    new ClosureTask(fn() => $this->doSomething()),
    20 * 60  // Every 60 seconds
);
```

### Save/Load Data
```php
// Save
$file = $this->plugin->getDataFolder() . 'mydata.json';
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

// Load
$file = $this->plugin->getDataFolder() . 'mydata.json';
if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
}
```

## Module Lifecycle

```
Server Start
    ↓
Main::onEnable()
    ↓
ModuleManager created
    ↓
Modules registered
    ↓
Modules enabled (from config)
    ↓
Module::onEnable() called
    ↓
[Runtime - modules active]
    ↓
Server Stop / Reload
    ↓
Module::onDisable() called
    ↓
Main::onDisable()
```

## File Locations

```
ValientFactions/
├── plugin.yml              ← Plugin manifest (required)
├── resources/
│   └── config.yml          ← Default config (copied to plugin_data)
└── src/ValientFactions/
    ├── Main.php            ← Entry point
    ├── command/            ← Commands
    ├── module/             ← Module system
    │   ├── ModuleInterface.php
    │   ├── BaseModule.php
    │   ├── ModuleManager.php
    │   └── modules/        ← Your modules here
    └── [other]/            ← Add your own packages
```

## Debugging

```php
// Enable debug mode in config.yml
settings:
  debug: true

// Use logger
$this->plugin->getLogger()->debug("Debug message");
$this->plugin->getLogger()->info("Info message");
$this->plugin->getLogger()->warning("Warning message");
$this->plugin->getLogger()->error("Error message");
```

## Common Issues

**Module not enabling?**
- Check module name matches config.yml exactly (case-sensitive)
- Check for errors in console
- Verify module is registered in Main.php

**Events not working?**
- Ensure `registerEvents()` is called in `onEnable()`
- Check event priority if needed
- Verify event is not cancelled by another plugin

**Module stays enabled after disable?**
- Make sure to call `parent::onDisable()`
- Unregister all event listeners
- Clean up scheduled tasks

## Testing Checklist

- [ ] Module enables without errors
- [ ] Module disables without errors
- [ ] Events register correctly
- [ ] Events unregister on disable
- [ ] No memory leaks (enable/disable multiple times)
- [ ] Config reload works
- [ ] Permissions work correctly
- [ ] No errors on server shutdown

## API References

- PocketMine-MP Docs: https://doc.pmmp.io/
- Event List: Search for `*Event` classes
- Scheduler: `pocketmine\scheduler\*`
- Config: `pocketmine\utils\Config`

---
**Note**: All code uses PHP 8.1+ features and strict types.
