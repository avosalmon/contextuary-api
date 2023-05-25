<?php

declare(strict_types=1);

namespace App;

use App\Enums\Role;
use App\Enums\SystemUserName;
use InvalidArgumentException;

/**
 * This class represents a chat message for the OpenAI Chat Completion API.
 * The `name` property is optional and only used for few-shot prompting with the `system` role.
 * This helps the API to understand that the example messages are not part of a real conversation, and shouldn't be referred back to by the model.
 */
class ChatMessage
{
    public function __construct(
        protected Role $role,
        protected string $content,
        protected ?SystemUserName $name = null
    ) {
        if ($name && $role !== Role::System) {
            throw new InvalidArgumentException('The `name` property can only be used with the `system` role.');
        }
    }

    public static function fromArray(array $data): static
    {
        return new static(
            role: Role::from($data['role']),
            content: $data['content'],
            name: $data['name'] ? SystemUserName::from($data['name']) : null,
        );
    }

    public function toArray(): array
    {
        $array = [
            'role' => $this->role->value,
            'content' => $this->content,
        ];

        if ($this->name) {
            $array['name'] = $this->name->value;
        }

        return $array;
    }
}
