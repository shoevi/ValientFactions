<?php

declare(strict_types=1);

namespace ValientFactions\form;

use pocketmine\form\Form;
use pocketmine\player\Player;

/**
 * Simple button menu form implementing pocketmine\form\Form.
 * Provides a reusable UI for menu flows with button-based navigation.
 */
final class SimpleButtonForm implements Form {

    /** @var string */
    private string $title;

    /** @var string */
    private string $content;

    /** @var array<int, string> */
    private array $buttons = [];

    /** @var \Closure(Player, int): void */
    private \Closure $handler;

    /**
     * @param string $title Form title
     * @param string $content Form content/description
     * @param \Closure(Player, int): void $handler Callback when button is clicked (receives player and button index)
     */
    public function __construct(string $title, string $content, \Closure $handler) {
        $this->title = $title;
        $this->content = $content;
        $this->handler = $handler;
    }

    /**
     * Add a button to the form.
     *
     * @param string $text Button text
     * @return self For method chaining
     */
    public function addButton(string $text): self {
        $this->buttons[] = $text;
        return $this;
    }

    /**
     * @param Player $player
     * @param mixed $data Form response data (int for button index, null if closed)
     */
    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            // Form was closed
            return;
        }

        if (!is_int($data) || !isset($this->buttons[$data])) {
            return;
        }

        ($this->handler)($player, $data);
    }

    public function jsonSerialize(): array {
        $buttons = [];
        foreach ($this->buttons as $text) {
            $buttons[] = ["text" => $text];
        }

        return [
            "type" => "form",
            "title" => $this->title,
            "content" => $this->content,
            "buttons" => $buttons
        ];
    }
}
