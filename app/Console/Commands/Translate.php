<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DataObjects\ChatMessage;
use App\DataObjects\TranslationInput;
use App\Enums\Role;
use App\Enums\Tone;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use OpenAI\Laravel\Facades\OpenAI;

class Translate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate a word/phrase in the given context.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $word = $this->ask('What word/phrase would you like to translate?');
        $context = $this->ask('What is the context where the word/phrase is used?');
        $inputLanguage = $this->choice('What is the input language?', ['English', 'Japanese']);
        $outputLanguage = $this->choice('What is the output language?', ['English', 'Japanese']);
        $tone = $this->choice('What is the tone of the voice?', collect(Tone::cases())->map(fn (Tone $tone) => $tone->value)->toArray());
        $audience = $this->ask('Who is the audience? e.g. friend, family, colleague, stranger');

        $messages = collect();

        // Provide steps
        // https://github.com/openai/openai-cookbook/blob/970d8261fbf6206718fe205e88e37f4745f9cf76/techniques_to_improve_reliability.md
        $prompt = "Translate the following input: \"{$word}\" in {$outputLanguage}";
        $prompt .= $context ? ", which is used in this context: \"{$context}\".\n" : ".\n";
        $prompt .= "The intended tone is \"{$tone}\"";
        $prompt .= $audience ? " and the audience is \"{$audience}\".\n" : ".\n";

        $messages->push(new ChatMessage(
            Role::User,
            $prompt,
        ));

        // $messages = $this->composeMessages(
        //     $word,
        //     $context,
        //     $inputLanguage,
        //     $outputLanguage,
        //     Tone::from($tone),
        //     $audience
        // );

        // $stream = OpenAI::chat()->createStreamed([
        //     'model' => 'gpt-4',
        //     'messages' => $messages->toArray(),
        //     'temperature' => 0,
        // ]);

        // foreach ($stream as $response) {
        //     $delta = $response->choices[0]->delta->content ?? '';
        //     $this->getOutput()->write($delta);
        // }

        // $this->newLine();
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
    ): Collection {
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
                The context and audience are optional. Ignore them if they are not provided.
                The output must be a JSON object containing the translation, explanation, and example."
            ),
            new ChatMessage(
                Role::User,
                $input->toPrompt()
            ),
            // new ChatMessage(
            //     Role::User,
            //     <<<PROMPT
            //     The output should contain 3 options in the following JSON format:
            //     ```
            //     [
            //         {
            //             "translation": "translation 1",
            //             "explanation": "explanation 1",
            //             "example": "example 1"
            //         },
            //         {
            //             "translation": "translation 2",
            //             "explanation": "explanation 2",
            //             "example": "example 2"
            //         },
            //         {
            //             "translation": "translation 3",
            //             "explanation": "explanation 3",
            //             "example": "example 3"
            //         }
            //     ]
            //     ```
            //     Your response should only contain the JSON object as I will programmatically parse it.
            //     PROMPT
            // ),
        ]);
    }
}
