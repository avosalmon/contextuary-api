<?php

declare(strict_types=1);

namespace App;

use App\Enums\Role;

class ChatMessage
{
    public function __construct(protected Role $role, protected string $content)
    {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            role: Role::from($data['role']),
            content: $data['content'],
        );
    }

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'content' => $this->content,
        ];
    }
}
