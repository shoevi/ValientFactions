<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\BattleCryAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Warlord Armor Set – Hardened by a thousand battles.
 *
 * The supreme tank set. The wearer shrugs off colossal amounts of damage,
 * stands immovable to knockback, resists explosions, and reflects a portion
 * of every hit back at the attacker like plate mail studded with razors.
 *
 * Passive combat trigger: 25% of damage received is reflected back to the
 * attacker (Thorns).
 *
 * Active ability: Battle Cry – self-buff Strength II + Resistance II while
 * debuffing all nearby enemies.
 */
final class WarlordArmorSet extends ArmorSet {

    public function getName(): string {
        return "Warlord";
    }

    public function getDescription(): string {
        return "Hardened by a thousand battles – unbreakable and immovable";
    }

    public function getColor(): Color {
        return new Color(140, 110, 0); // dark gold
    }

    public function getLoreColor(): string {
        return TF::YELLOW;
    }

    public function getPerks(): array {
        return [
            ArmorPerk::DAMAGE_REDUCTION->value     => 0.45,
            ArmorPerk::KNOCKBACK_RESISTANCE->value  => 0.45,
            ArmorPerk::EXPLOSION_RESISTANCE->value  => 0.50,
            ArmorPerk::THORNS->value                => 0.25,
            ArmorPerk::RESISTANCE->value            => 1.0,
            ArmorPerk::FALL_DAMAGE_REDUCTION->value => 0.60,
        ];
    }

    public function getAbility(): ?SetAbility {
        return new BattleCryAbility();
    }

    public function getCombatTriggerDescription(): ?string {
        return "25% of damage taken is reflected back to the attacker";
    }
}
