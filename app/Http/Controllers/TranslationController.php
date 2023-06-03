<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataObjects\ChatMessage;
use App\DataObjects\TranslationInput;
use App\Enums\Role;
use App\Enums\SystemUserName;
use App\Enums\Tone;
use App\Http\Requests\TranslationRequest;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

class TranslationController extends Controller
{
    public function __invoke(TranslationRequest $request)
    {
        $word = $request->input('word');
        $context = $request->input('context');
        $inputLanguage = $request->input('input_language');
        $outputLanguage = $request->input('output_language');
        /** @var Tone $tone */
        $tone = $request->enum('tone', Tone::class);
        $audience = $request->input('audience');

        $messages = $this->composeMessages(
            $word,
            $context,
            $inputLanguage,
            $outputLanguage,
            $tone,
            $audience
        );

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => $messages->toArray(),
            'temperature' => 0,
            // TODO: 'max_tokens' => 64,
        ]);

        $json = $response->choices[0]->message->content;
        $array = json_decode($json, associative: true);

        // Return `Translation` JSON resource
        return response()->json([
            'data' => $array,
        ]);
    }

    /**
     * @return Collection<ChatMessage>
     */
    private function composeMessages(
        string $word,
        ?string $context,
        string $inputLanguage,
        string $outputLanguage,
        Tone $tone,
        ?string $audience,
    ): Collection
    {
        $input = new TranslationInput(
            word: $word,
            context: $context,
            inputLanguage: $inputLanguage,
            outputLanguage: $outputLanguage,
            tone: $tone,
            audience: $audience,
        );

        return collect([
            new ChatMessage(
                Role::System,
                "Act as a native speaker of {$inputLanguage} and {$outputLanguage}.
                Your task is to translate a word/phrase/sentence based on the context, tone, and audience.
                The context and audience are optional. Ignore them if they are not provided."
            ),
            new ChatMessage(
                Role::User,
                $input->toPrompt()
            ),
            new ChatMessage(
                Role::User,
                <<<PROMPT
                The output should contain 3 options in the following JSON format:
                ```
                [
                    {
                        "translation": "translation 1",
                        "explanation": "explanation 1",
                        "example": "example 1"
                    },
                    {
                        "translation": "translation 2",
                        "explanation": "explanation 2",
                        "example": "example 2"
                    },
                    {
                        "translation": "translation 3",
                        "explanation": "explanation 3",
                        "example": "example 3"
                    }
                ]
                ```
                Your response should only contain the JSON object as I will programmatically parse it.
                PROMPT
            ),
        ]);
    }
}
