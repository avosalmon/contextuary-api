<?php

namespace App\Console\Commands;

use App\Enums\Tone;
use Illuminate\Console\Command;
use OpenAI\Laravel\Facades\OpenAI;

class ChatGPTFunction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat-function';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to test ChatGPT function call';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $word = $this->ask('What word/phrase would you like to translate?');
        $context = $this->ask('What is the context where the word/phrase is used?');
        $inputLanguage = $this->choice('What is the input language?', ['English', 'Japanese']);
        $outputLanguage = $this->choice('What is the output language?', ['English', 'Japanese']);
        $tone = $this->choice('What is the tone of the voice?', collect(Tone::cases())->map(fn (Tone $tone) => $tone->value)->toArray());
        $audience = $this->ask('Who is the audience? e.g. friend, family, colleague, stranger');

        $prompt = "Translate the following input: \"{$word}\" in {$outputLanguage}";
        $prompt .= $context ? ", which is used in this context: \"{$context}\".\n" : ".\n";
        $prompt .= "The intended tone is \"{$tone}\"";
        $prompt .= $audience ? " and the audience is \"{$audience}\".\n" : ".\n";
        $prompt .= "Provide the following in your response.\n\n";
        $prompt .= "Translation: the translation of the word/phrase in the given context and tone. Make it sound natural to native English speakers rather than just literally translating the input.\n";
        $prompt .= "Explanation: the explanation of the translation in {$inputLanguage}, touching on the {$outputLanguage} words/phrases used in the translation. Keep any {$outputLanguage} phrases you mention in the explanation in their original {$outputLanguage} form.\n";
        $prompt .= "Example conversation: an example conversation between 2 persons in {$outputLanguage} using the translation";

        $this->info($prompt);
        $this->newLine(2);

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
                ]
            ],
            'function_call' => [
                'name' => 'store_translation',
            ],
            'temperature' => 0,
        ]);

        dd($response);

        $content = $response->choices[0]->message->functionCall->arguments;

        $this->info($content);
    }
}
