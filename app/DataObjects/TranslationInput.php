<?php

declare(strict_types=1);

namespace App\DataObjects;

use App\Enums\Tone;

readonly class TranslationInput
{
    public function __construct(
        public string $word,
        public string $context,
        public string $inputLanguage,
        public string $outputLanguage,
        public Tone $tone,
        public string $audience
    ) {}

    public function toPrompt(): string
    {
        return <<<PROMPT
Input: {$this->word}
Context: {$this->context}
From language: {$this->inputLanguage}
To language: {$this->outputLanguage}
Tone: {$this->tone->name}
Audience: {$this->audience}
PROMPT;
    }
}
