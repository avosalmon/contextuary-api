<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * This enum represents the name of a system message for the OpenAI Chat Completion API.
 * It should be used along with the `system` role for few-shot prompting.
 * This helps the API to understand that the example messages are not part of a real conversation, and shouldn't be referred back to by the model.
 */
enum SystemUserName: string
{
    case ExampleUser = 'example_user';
    case ExampleAssistant = 'example_assistant';
}
