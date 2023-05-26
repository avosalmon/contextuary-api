<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ChatMessage;
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
        // Get request data
        $word = $request->input('word');
        $context = $request->input('context');
        $inputLanguage = $request->input('input_language');
        $outputLanguage = $request->input('output_language');
        $tone = $request->enum('tone', Tone::class);
        $audience = $request->input('audience');

        // Prepare prompt
        $messages = $this->exampleMessages();
        $messages->push(new ChatMessage(Role::User,"Please translate '${word}' in the following context."));
        $messages->push(new ChatMessage(Role::User, $context));

        // Call OpenAI API
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => $messages,
        ]);

        $json = $response->choices[0]->message->content;
        $array = json_decode($json, associative: true);

        // Format response

        // Return response
        return response()->json([
            'data' => $array,
        ]);
    }

    /**
     * @return Collection<ChatMessage>
     */
    private function exampleMessages(): Collection
    {
        return collect([
            new ChatMessage(
                Role::System,
                'You are an English-Japanese dictionary that provides the meaning of a word in the context of a sentence where the word is used.'
            ),
            new ChatMessage(
                Role::System,
                "Please translate 'example phrase' in the following context.",
                SystemUserName::ExampleUser
            ),
            new ChatMessage(
                Role::System,
                'Example context',
                SystemUserName::ExampleUser
            ),
            new ChatMessage(
                Role::System,
                'Example output',
                SystemUserName::ExampleAssistant
            ),
        ]);
    }
}
