<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Tone;
use App\Http\Requests\TranslationRequest;
use App\Http\Resources\Translation;
use OpenAI\Laravel\Facades\OpenAI;

class TranslationController extends Controller
{
    public function store(TranslationRequest $request)
    {
        $word = $request->input('word');
        $context = $request->input('context');
        $inputLanguage = $request->input('input_language');
        $outputLanguage = $request->input('output_language');
        /** @var Tone $tone */
        $tone = $request->enum('tone', Tone::class) ?? Tone::Neutral;
        $audience = $request->input('audience');

        $prompt = "Translate the following input: \"{$word}\" in {$outputLanguage}";
        $prompt .= $context ? ", which is used in this context: \"{$context}\".\n" : ".\n";
        $prompt .= "The intended tone is \"{$tone->value}\"";
        $prompt .= $audience ? " and the audience is \"{$audience}\".\n" : ".\n";
        $prompt .= "Provide the following in your response.\n\n";
        $prompt .= "Translation: the translation of the word/phrase in the given context and tone. Make it sound natural to native English speakers rather than just literally translating the input.\n";
        $prompt .= "Explanation: the explanation of the translation in {$inputLanguage}, touching on the {$outputLanguage} words/phrases used in the translation. Keep any {$outputLanguage} phrases you mention in the explanation in their original {$outputLanguage} form.\n";
        $prompt .= "Example conversation: an example conversation between 2 persons in {$outputLanguage} using the translation";

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4-0613',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'functions' => [
                [
                    'name' => 'store_translation',
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
                        'required' => ['translation', 'explanation', 'example'],
                    ],
                ],
            ],
            'function_call' => [
                'name' => 'store_translation',
            ],
            'temperature' => 0,
            // 'max_tokens' => xxx,
        ]);

        $arguments = $response->choices[0]->message->functionCall->arguments;
        $json = json_decode($arguments, associative: true);

        // TODO: Store the translation in the database

        return new Translation($json);
    }
}
