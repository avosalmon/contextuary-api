<?php

declare(strict_types=1);

namespace App\Enums;

enum Tone: string
{
    case Formal = 'formal';
    case Casual = 'casual';
    case Neutral = 'neutral';
}
