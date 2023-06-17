<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranslationRequest;
use App\Http\Resources\Translation;
use OpenAI\Laravel\Facades\OpenAI;

class TranslationController extends Controller
{
    public const FUNCTION_NAME = 'store_translation';

    public function store(StoreTranslationRequest $request): Translation
    {
        $inputLanguage = $request->input('input_language');
        $outputLanguage = $request->input('output_language');

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-0613',
            'messages' => [
                ['role' => 'user', 'content' => $request->toPrompt()],
            ],
            'functions' => [
                $this->functionSchema($inputLanguage, $outputLanguage),
            ],
            'function_call' => [
                'name' => self::FUNCTION_NAME,
            ],
            'temperature' => 0,
            // 'max_tokens' => xxx,
        ]);

        $arguments = $response->choices[0]->message->functionCall->arguments;
        $json = json_decode($arguments, associative: true);

        // TODO: Store the translation in the database

        return new Translation($json);
    }

    private function functionSchema(string $inputLanguage, string $outputLanguage): array
    {
        return [
            'name' => self::FUNCTION_NAME,
            'description' => 'Store the translation in the database',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'translation' => [
                        'type' => 'string',
                        'description' => 'The translation of the word/phrase in the given context and tone',
                    ],
                    'explanation' => [
                        'type' => 'string',
                        'description' => "The explanation of the translation in {$inputLanguage}",
                    ],
                    'example' => [
                        'type' => 'string',
                        'description' => "An example conversation between 2 persons in {$outputLanguage} using the translation",
                    ],
                ],
                'required' => ['translation'],
            ],
        ];
    }
}
