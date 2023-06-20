<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Translation extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'translation' => $this['translation'],
            'explanation' => $this['explanation'] ?? null,
            'example' => $this['example'] ?? null,
        ];
    }
}
