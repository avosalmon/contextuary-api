<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Tone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'input' => ['required', 'string'],
            'input_language' => ['required', 'string'],
            'output_language' => ['required', 'string'],
            'tone' => [new Enum(Tone::class)],
            'requires_explaination' => ['required', 'boolean'],
            'requires_example' => ['required', 'boolean'],
        ];
    }

    public function toPrompt(): string
    {
        $input = $this->input('input');
        $context = $this->input('context');
        $inputLanguage = $this->input('input_language');
        $outputLanguage = $this->input('output_language');
        $tone = $this->enum('tone', Tone::class) ?? Tone::Neutral;
        $audience = $this->input('audience');

        $prompt = "Your task is to perform the following action(s):\n";
        $prompt .= "1. Translate the following {$inputLanguage} text to {$outputLanguage} in the given context and tone. ";
        $prompt .= "Make it sound natural to native {$outputLanguage} speakers rather than just literally translating the text.\n";

        if ($this->boolean('requires_explaination')) {
            $prompt .= "2. Explain the translation in {$inputLanguage}, touching on the {$outputLanguage} words/phrases used in the translation. Keep any {$outputLanguage} phrases you mention in the explanation in their original {$outputLanguage} form.\n";
        }

        if ($this->boolean('requires_example')) {
            $prompt .= "3. Provide an example conversation between 2 persons in {$outputLanguage} using the translation\n";
        }

        $prompt .= "\n";
        $prompt .= "Text: {$input}\n";
        $prompt .= $context ? "Context: {$context}\n" : '';
        $prompt .= "Tone: {$tone->value}\n";
        $prompt .= $audience ? "Audience: {$audience}" : '';

        return $prompt;
    }

    public function toFunction(): array
    {
        $inputLanguage = $this->input('input_language');
        $outputLanguage = $this->input('output_language');

        $schema = [
            'name' => 'store_translation',
            'description' => 'Store the translation in the database',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'translation' => [
                        'type' => 'string',
                        'description' => 'The translation of the word/phrase in the given context and tone',
                    ],
                ],
                'required' => ['translation'],
            ],
        ];

        if ($this->boolean('requires_explaination')) {
            $schema['parameters']['properties']['explanation'] = [
                'type' => 'string',
                'description' => "The explanation of the translation in {$inputLanguage}",
            ];
            $schema['parameters']['required'][] = 'explanation';
        }

        if ($this->boolean('requires_example')) {
            $schema['parameters']['properties']['example'] = [
                'type' => 'string',
                'description' => "An example conversation between 2 persons in {$outputLanguage} using the translation",
            ];
            $schema['parameters']['required'][] = 'example';
        }

        return $schema;
    }
}
