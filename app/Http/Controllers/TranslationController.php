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
     * Example messages for "few-shot" prompting.
     *
     * @return Collection<ChatMessage>
     */
    private function exampleMessages(): Collection
    {
        return collect([
            new ChatMessage(
                Role::System,
                'Act as a native speaker of both Japanese and English. Your task is to translate a word/phrase/sentence based on the context, tone, and audience.'
            ),
            new ChatMessage(
                Role::System,
                "Please translate '次のスプリントに回す' into English in the following context.",
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
