# ValientFactions - Complete File Index

## Core Files

### `plugin.yml`
**Purpose**: PMMP 5 plugin manifest  
**Contains**: 
- Plugin metadata (name, version, api, author)
- Command definitions (/vfactions)
- Permission nodes and hierarchy
- Command aliases

### `composer.json`
**Purpose**: Composer package definition  
**Contains**:
- PHP version requirement (8.1+)
- PSR-4 autoloading configuration
- Package metadata

---

## Configuration

### `resources/config.yml`
**Purpose**: Default plugin configuration  
**Contains**:
- Module enable/disable toggles
- Plugin settings (debug, auto-save)
- Copied to plugin_data on first run

---

## Source Code

### `src/ValientFactions/Main.php`
**Purpose**: Main plugin class (entry point)  
**Extends**: `pocketmine\plugin\PluginBase`  
**Responsibilities**:
- Initialize ModuleManager
- Register built-in modules
- Enable modules from config
- Register commands
- Handle plugin lifecycle (onEnable/onDisable)
- Provide module manager access
- Reload configuration

**Key Methods**:
- `onEnable()`: Plugin initialization
- `onDisable()`: Cleanup and module shutdown
- `getModuleManager()`: Access to module system
- `reloadConfiguration()`: Hot reload support

---

## Command System

### `src/ValientFactions/command/VFactionsCommand.php`
**Purpose**: Main command handler  
**Implements**: `pocketmine\command\Command`, `PluginOwned`  
**Command**: `/vfactions` (aliases: `/vf`, `/factions`)  

**Subcommands**:
- `modules` / `list`: Show all modules and status
- `enable <module>`: Enable a module by name
- `disable <module>`: Disable a module by name
- `reload`: Reload config and restart modules

**Features**:
- Permission checking
- Input validation
- User feedback (colored messages)
- Error handling

---

## Module System

### `src/ValientFactions/module/ModuleInterface.php`
**Purpose**: Module contract definition  
**Type**: Interface  
**Methods**:
- `getName(): string` - Module identifier
- `getDescription(): string` - Module purpose
- `onEnable(): void` - Initialization
- `onDisable(): void` - Cleanup
- `isEnabled(): bool` - State check

### `src/ValientFactions/module/BaseModule.php`
**Purpose**: Abstract base implementation for modules  
**Type**: Abstract class  
**Implements**: `ModuleInterface`  

**Properties**:
- `protected bool $enabled` - Module state
- `protected Main $plugin` - Plugin instance

**Methods**:
- `__construct(Main $plugin)` - Dependency injection
- `isEnabled(): bool` - State getter
- `onEnable(): void` - Enable with logging
- `onDisable(): void` - Disable with logging
- `setEnabled(bool): void` - State setter
- `getPlugin(): Main` - Plugin accessor

**Child Requirements**:
- Must implement `getName()`
- Must implement `getDescription()`

### `src/ValientFactions/module/ModuleManager.php`
**Purpose**: Central module lifecycle manager  
**Type**: Final class  

**Responsibilities**:
- Module registration
- Module enabling/disabling
- Module state tracking
- Error handling

**Properties**:
- `private array $modules` - Registered modules
- `private Main $plugin` - Plugin instance

**Methods**:
- `registerModule(ModuleInterface)` - Add module to registry
- `enableModule(string): bool` - Activate module by name
- `disableModule(string): bool` - Deactivate module by name
- `getModule(string): ?ModuleInterface` - Get module instance
- `getAllModules(): array` - Get all registered modules
- `getEnabledModules(): array` - Get only active modules
- `isModuleEnabled(string): bool` - Check module state

---

## Built-in Modules

### `src/ValientFactions/module/modules/ChatModule.php`
**Purpose**: Faction chat system  
**Extends**: `BaseModule`  
**Implements**: `pocketmine\event\Listener`  

**Features** (Skeleton):
- Player chat event interception
- Faction tag/prefix formatting
- Faction-only chat channels
- Rank-based chat colors

**Events**:
- `PlayerChatEvent` - Chat message handling

**Current State**: Skeleton with logging only

### `src/ValientFactions/module/modules/ProtectionModule.php`
**Purpose**: Land claim and protection system  
**Extends**: `BaseModule`  
**Implements**: `pocketmine\event\Listener`  

**Features** (Skeleton):
- Land claim management
- Block break/place protection
- PvP protection in claims
- Permission verification

**Events**:
- `BlockBreakEvent` - Block destruction
- `BlockPlaceEvent` - Block placement
- `EntityDamageByEntityEvent` - PvP protection

**Current State**: Skeleton with logging only

### `src/ValientFactions/module/modules/PowerModule.php`
**Purpose**: Player and faction power system  
**Extends**: `BaseModule`  
**Implements**: `pocketmine\event\Listener`  

**Features**:
- Player power tracking (in-memory)
- Power loss on death
- Power initialization on join
- Power persistence (TODO)
- Faction total power (TODO)

**Constants**:
- `DEFAULT_POWER = 10`
- `MAX_POWER = 20`
- `POWER_LOSS_ON_DEATH = 2`

**Events**:
- `PlayerJoinEvent` - Initialize player power
- `PlayerQuitEvent` - Save player power
- `PlayerDeathEvent` - Deduct power on death

**Properties**:
- `private array $playerPower` - Power storage

**Methods**:
- `getPlayerPower(string): int`
- `setPlayerPower(string, int): void`
- `addPlayerPower(string, int): void`

**Current State**: Functional power tracking, needs persistence

---

## Documentation

### `README.md`
**Purpose**: Main project documentation  
**Contains**:
- Features overview
- Installation instructions
- Command reference
- Permission list
- Configuration guide
- Module system explanation
- Architecture overview

### `CONTRIBUTING.md`
**Purpose**: Developer guide  
**Contains**:
- Development setup
- Creating new modules
- Code standards
- Naming conventions
- Event handling best practices
- Testing checklist
- Submission guidelines

### `STRUCTURE.md`
**Purpose**: Architecture documentation  
**Contains**:
- Complete file structure
- Component descriptions
- Configuration details
- Permission hierarchy
- Technical requirements
- Feature list

### `QUICKREF.md`
**Purpose**: Quick reference guide  
**Contains**:
- Quick start commands
- Command cheat sheet
- Module creation template
- Common code patterns
- Debugging tips
- Testing checklist

### `FILE_INDEX.md` (this file)
**Purpose**: Complete file reference  
**Contains**: Detailed description of every file

---

## Build/Config Files

### `.gitignore`
**Purpose**: Git exclusion rules  
**Excludes**:
- IDE files (.idea, .vscode)
- Composer artifacts (vendor)
- Build files (.phar)
- OS files (.DS_Store)
- Runtime data (plugin_data)

---

## Summary

**Total Files**: 16
- **PHP Source**: 8 files (747 lines)
- **YAML Config**: 2 files
- **Documentation**: 5 markdown files
- **Build Config**: 1 file (.gitignore, composer.json)

**Namespaces**:
- `ValientFactions` - Main plugin
- `ValientFactions\command` - Commands
- `ValientFactions\module` - Module system
- `ValientFactions\module\modules` - Built-in modules

**Design Patterns**:
- Dependency Injection (modules receive plugin instance)
- Strategy Pattern (ModuleInterface implementations)
- Manager Pattern (ModuleManager)
- Command Pattern (VFactionsCommand)
- Observer Pattern (Event listeners)

**SOLID Principles**:
- Single Responsibility (each module has one purpose)
- Open/Closed (extend via new modules, closed for modification)
- Liskov Substitution (BaseModule substitutable)
- Interface Segregation (focused interfaces)
- Dependency Inversion (depend on abstractions)
