<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
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
    protected $description = 'Translate a word in the context of a sentence.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $word = $this->ask('What word would you like to translate?');
        $sentence = $this->ask('What sentence would you like to translate the word in?');

        $exampleWord = 'unprecedented';
        $exampleSentence = 'Amid an unprecedented global situation that is graver than what Singapore has experienced for a very long time, it is important that the nation stays united.';
        $exampleOutput = 'この文脈での「unprecedented」の意味は、「これまでにない、前例のない、まったく新しくて珍しい」です。この文章は、シンガポールがこれまでにない状況に直面しており、国が団結し、他の国々のように分断されないようにすることが重要であることを伝えています。';
        // $exampleOutput = '{
        //     "translated_explanation": "この文脈での「unprecedented」の意味は、「これまでにない、前例のない、まったく新しくて珍しい」です。この文章は、シンガポールがこれまでにない状況に直面しており、国が団結し、他の国々のように分断されないようにすることが重要であることを伝えています。",
        //     "explanation": "In this context, \"unprecedented\" means never having happened or existed before, something completely new and unusual. The sentence conveys that Singapore is facing a unique and extraordinary global situation, and it is crucial for the nation to stay united and not become divided like other countries.",
        //     "part_of_speech": "形容詞",
        //     "phonetic_symbol": "[ˌʌnˈpresɪˌdentɪd]",
        //     "example_sentence": "The company faced unprecedented challenges during the economic downturn."
        // }';

        $messages = [
            ['role' => 'system', 'content' => 'You are an English-Japanese dictionary that provides the meaning of a word in the context of a sentence where the word is used.'],
            // ['role' => 'user', 'content' => 'The response should be a JSON object containing the following fields. Make sure to return only a JSON object without any text so that I can programatically process it.'],
            // ['role' => 'user', 'content' => '
            //     explanation: Explanation of the word(s) in the context of the sentence
            //     translated_explanation: Japanese explanation of the word(s) in the context of the sentence
            //     part_of_speech: the part of the speech of the word(s) in Japanese
            //     phonetic_symbol: the phonetic symbol of the word(s)
            //     example_sentence: an example sentence in English using the input word(s) in a similar context to the input sentence
            // '],
            ['role' => 'user', 'content' => "What is the meaning of '{$exampleWord}' in the context of the following sentence?"],
            ['role' => 'user', 'content' => $exampleSentence],
            ['role' => 'assistant', 'content' => $exampleOutput],
            ['role' => 'user', 'content' => "What is the meaning of '{$word}' in the context of the following sentence? The output should be in Japanese"],
            ['role' => 'user', 'content' => $sentence],
        ];

        $this->streamedRequest($messages);

        $this->newLine();
        $this->comment('Retrieving other information...');

        $messages = [
            ['role' => 'system', 'content' => 'You are an English-Japanese dictionary that provides the meaning of a word in the context of a sentence where the word is used.'],
            ['role' => 'user', 'content' => 'The response should be a JSON object containing the following fields. Make sure to return only a JSON object without any text so that I can programatically process it.'],
            ['role' => 'user', 'content' => '
                part_of_speech: the part of the speech of the word(s) in Japanese
                phonetic_symbol: the phonetic symbol of the word(s)
                example_sentence: an example sentence in English using the input word(s) in a similar context to the input sentence
            '],
            ['role' => 'user', 'content' => "What is the meaning of '{$exampleWord}' in the context of the following sentence?"],
            ['role' => 'user', 'content' => $exampleSentence],
            ['role' => 'assistant', 'content' => '{
                "part_of_speech": "形容詞",
                "phonetic_symbol": "[ˌʌnˈpresɪˌdentɪd]",
                "example_sentence": "The company faced unprecedented challenges during the economic downturn."
            }'],
            ['role' => 'user', 'content' => "What is the meaning of '{$word}' in the context of the following sentence?"],
            ['role' => 'user', 'content' => $sentence],
        ];

        $this->jsonRequest($messages);
    }

    private function streamedRequest(array $messages)
    {
        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);

        foreach ($stream as $response) {
            $delta = $response->choices[0]->delta->content ?? '';
            $this->getOutput()->write($delta);
        }

        $this->newLine();
    }

    private function request(array $messages)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);

        $content = $response->choices[0]->message->content;
        $this->info($content);
        $this->newLine();
    }

    private function jsonRequest(array $messages)
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);

        $json = $response->choices[0]->message->content;
        $array = json_decode($json, associative: true);
        foreach ($array as $key => $value) {
            $this->info("{$key}: {$value}");
        }
    }
}
