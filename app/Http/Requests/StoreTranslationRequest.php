<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Tone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'word' => ['required', 'string'],
            'context' => ['string'],
            'input_language' => ['required', 'string'],
            'output_language' => ['required', 'string'],
            'tone' => ['required', new Enum(Tone::class)],
            'audience' => ['string'],
        ];
    }

    public function toPrompt(): string
    {
        $word = $this->input('word');
        $context = $this->input('context');
        $inputLanguage = $this->input('input_language');
        $outputLanguage = $this->input('output_language');
        /** @var Tone $tone */
        $tone = $this->enum('tone', Tone::class) ?? Tone::Neutral;
        $audience = $this->input('audience');

        $prompt = "Translate the following input: \"{$word}\" in {$outputLanguage}";
        $prompt .= $context ? ", which is used in this context: \"{$context}\".\n" : ".\n";
        $prompt .= "The intended tone is \"{$tone->value}\"";
        $prompt .= $audience ? " and the audience is \"{$audience}\".\n" : ".\n";
        $prompt .= "Provide the following in your response.\n\n";
        $prompt .= "Translation: the translation of the word/phrase in the given context and tone. Make it sound natural to native English speakers rather than just literally translating the input.\n";
        $prompt .= "Explanation: the explanation of the translation in {$inputLanguage}, touching on the {$outputLanguage} words/phrases used in the translation. Keep any {$outputLanguage} phrases you mention in the explanation in their original {$outputLanguage} form.\n";
        $prompt .= "Example conversation: an example conversation between 2 persons in {$outputLanguage} using the translation";

        return $prompt;
    }
}
