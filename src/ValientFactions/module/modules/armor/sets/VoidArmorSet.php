<?php

declare(strict_types=1);

namespace ValientFactions\module\modules\armor\sets;

use pocketmine\utils\Color;
use pocketmine\utils\TextFormat as TF;
use ValientFactions\module\modules\armor\abilities\VoidShroudAbility;
use ValientFactions\module\modules\armor\ArmorPerk;
use ValientFactions\module\modules\armor\ArmorSet;
use ValientFactions\module\modules\armor\SetAbility;

/**
 * Void Armor Set – Infused with void energy.
 *
 * The ultimate sustain set. Every hit absorbed feeds the wearer back health
 * and a regenerative aura keeps them in the fight far longer than normal.
 *
 * Passive combat trigger: When HP would drop below 25%, a one-time emergency
 * shield absorbs the killing blow (45 s internal cooldown).
 *
 * Active ability: Void Shroud – 3 s of complete invulnerability + 4 HP heal.
 */
final class VoidArmorSet extends ArmorSet {

    public function getName(): string {
        return "Void";
    }

    public function getDescription(): string {
        return "Infused with void energy – absorbs attacks and heals";
    }

    public function getColor(): Color {
        return new Color(40, 0, 80); // deep purple
    }

    public function getLoreColor(): string {
        return TF::DARK_PURPLE;
    }

    public function getPerks(): array {
        return [
            ArmorPerk::DAMAGE_REDUCTION->value => 0.35,
            ArmorPerk::LIFESTEAL->value         => 0.15,
            ArmorPerk::REGENERATION->value      => 2.0,
            ArmorPerk::ABSORPTION->value        => 1.0,
        ];
    }

    public function getAbility(): ?SetAbility {
        return new VoidShroudAbility();
    }

    public function getCombatTriggerDescription(): ?string {
        return "Emergency shield absorbs lethal hit when HP < 25% (45s CD)";
    }
}
