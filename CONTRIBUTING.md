# Contributing to ValientFactions

Thank you for your interest in contributing to ValientFactions!

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- PocketMine-MP 5.0.0 or higher
- Git

### Getting Started

1. Clone the repository
2. Place the plugin folder in your PocketMine-MP `plugins` directory
3. Start your test server
4. Make your changes
5. Test thoroughly

## Creating a New Module

### Step 1: Create the Module Class

Create a new file in `src/ValientFactions/module/modules/`:

```php
<?php

declare(strict_types=1);

namespace ValientFactions\module\modules;

use pocketmine\event\Listener;
use ValientFactions\module\BaseModule;

final class YourModule extends BaseModule implements Listener {

    public function getName(): string {
        return "YourModuleName";
    }

    public function getDescription(): string {
        return "Description of what your module does";
    }

    public function onEnable(): void {
        parent::onEnable();
        
        // Register event listeners if needed
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        
        // Your initialization code here
    }

    public function onDisable(): void {
        parent::onDisable();
        
        // Unregister event listeners
        // Clean up resources
        
        // Your cleanup code here
    }
}
```

### Step 2: Register the Module

In `src/ValientFactions/Main.php`, add your module to `registerBuiltInModules()`:

```php
private function registerBuiltInModules(): void {
    $this->moduleManager->registerModule(new ArmorModule($this));
    $this->moduleManager->registerModule(new YourModule($this));  // Add this line
}
```

### Step 3: Add to Default Config

In `resources/config.yml`, add your module:

```yaml
modules:
  Armor: true
  YourModuleName: true  # Add this line
```

## Code Standards

### PHP Standards

- Use strict types: `declare(strict_types=1);`
- Use PHP 8.1+ type hints for all parameters and return types
- Use `final` classes unless inheritance is intended
- Use visibility keywords (`private`, `protected`, `public`)
- Follow PSR-12 coding standards

### Naming Conventions

- Classes: PascalCase (e.g., `YourModule`)
- Methods: camelCase (e.g., `getPlayerPower`)
- Constants: UPPER_SNAKE_CASE (e.g., `MAX_POWER`)
- Variables: camelCase (e.g., `$playerName`)

### Documentation

- Add PHPDoc comments for all public methods
- Document complex logic with inline comments
- Update README.md if adding major features

## Event Handling

Always unregister event listeners in `onDisable()`:

```php
public function onDisable(): void {
    parent::onDisable();
    
    // Unregister specific event handlers
    PlayerChatEvent::getHandlers()->unregister($this);
}
```

## Error Handling

- Use try-catch blocks for operations that might fail
- Log errors using `$this->plugin->getLogger()->error()`
- Never let exceptions crash the plugin

## Testing

Before submitting:

1. Test module enable/disable functionality
2. Test with other modules enabled/disabled
3. Test plugin reload
4. Check for memory leaks (enable/disable multiple times)
5. Verify no errors in console

## Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Module Best Practices

### Do's ✅

- Call `parent::onEnable()` and `parent::onDisable()`
- Unregister all event listeners in `onDisable()`
- Use the plugin's logger for output
- Handle errors gracefully
- Clean up resources properly
- Use dependency injection where appropriate

### Don'ts ❌

- Don't use static properties for module state
- Don't forget to unregister listeners
- Don't use blocking operations
- Don't store sensitive data in config
- Don't hardcode values that should be configurable

## Example Module Features

### Data Persistence

```php
private function saveData(): void {
    $data = [
        'players' => $this->playerData,
        'settings' => $this->settings
    ];
    
    file_put_contents(
        $this->plugin->getDataFolder() . 'yourmodule.json',
        json_encode($data, JSON_PRETTY_PRINT)
    );
}
```

### Configuration

```php
public function onEnable(): void {
    parent::onEnable();
    
    $config = $this->plugin->getConfig();
    $moduleConfig = $config->get('yourmodule', []);
    
    $this->setting1 = $moduleConfig['setting1'] ?? 'default';
}
```

### Task Scheduling

```php
use pocketmine\scheduler\ClosureTask;

public function onEnable(): void {
    parent::onEnable();
    
    // Schedule a repeating task
    $this->plugin->getScheduler()->scheduleRepeatingTask(
        new ClosureTask(function(): void {
            $this->doPeriodicTask();
        }),
        20 * 60  // Every 60 seconds (20 ticks per second)
    );
}
```

## Questions?

If you have questions about development, please open an issue on GitHub.

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.
