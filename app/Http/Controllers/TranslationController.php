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

        $messages = $this->exampleMessages();
        $messages->push(new ChatMessage(
            Role::User,
            (new TranslationInput(
                word: $word,
                context: $context,
                inputLanguage: $inputLanguage,
                outputLanguage: $outputLanguage,
                tone: $tone,
                audience: $audience,
            ))->toPrompt(),
        ));

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
        $input = new TranslationInput(
            word: '美味しいところを持って行ってごめんねw',
            context: 'チームで開発していて、自分はテックリードであまりコードは書いていないのに、自分が最初のリリースボタンを押した',
            inputLanguage: 'Japanese',
            outputLanguage: 'English',
            tone: Tone::Casual,
            audience: 'Colleagues',
        );

        return collect([
            new ChatMessage(
                Role::System,
                'Act as a native speaker of both Japanese and English. Your task is to translate a word/phrase/sentence based on the context, tone, and audience.'
            ),
            new ChatMessage(
                Role::System,
                $input->toPrompt(),
                SystemUserName::ExampleUser
            ),
            new ChatMessage(
                Role::System,
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
                PROMPT,
                SystemUserName::ExampleUser
            ),
            new ChatMessage(
                Role::System,
                <<<RESPONSE
                [
                    {
                        "translation": "Sorry for stealing the limelight, haha!",
                        "explanation": "The phrase '美味しいところを持って行ってごめんねw' literally translates to 'Sorry for taking the delicious part'. However, in the context provided, it's a metaphorical way of saying that the speaker has taken the best or most rewarding part (i.e., pressing the release button) despite not having contributed as much in terms of coding.",
                        "example": "I know you guys did most of the coding, but there I went, pushing the release button first. Sorry for stealing the limelight, haha!"
                    },
                    {
                        "translation": "My bad for hogging the glory moment, lol!",
                        "explanation": "The '美味しいところを持って行ってごめんねw' phrase can also imply the speaker 'hogging' or taking more than their fair share, which in this context would be the glory of the launch despite not being heavily involved in the development process.",
                        "example": "I'm not the one who did most of the coding, yet here I am pushing the release button. My bad for hogging the glory moment, lol!"
                    },
                    {
                        "translation": "Apologies for taking the best part for myself, haha!",
                        "explanation": "In this context, '美味しいところを持って行ってごめんねw' is equivalent to the speaker apologizing for taking the 'best part' for themselves, which is pressing the release button, despite not writing much of the code.",
                        "example": "Even though I haven't written much of the code, I'm the one who got to press the release button. Apologies for taking the best part for myself, haha!"
                    }
                ]
                RESPONSE,
                SystemUserName::ExampleAssistant
            ),
        ]);
    }
}
