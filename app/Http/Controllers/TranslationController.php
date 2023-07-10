<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranslationRequest;
use App\Http\Resources\Translation;
use OpenAI\Laravel\Facades\OpenAI;

class TranslationController extends Controller
{
    public function store(StoreTranslationRequest $request): Translation
    {
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'user', 'content' => $request->toPrompt()],
            ],
            'functions' => [
                $request->toFunction(),
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
