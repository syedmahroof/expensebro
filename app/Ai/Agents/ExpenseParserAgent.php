<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
class ExpenseParserAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Extract expense data from text. Use today\'s date if unspecified. Income keywords: salary,received,got,earned.';
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()->required(),
            'type' => $schema->string()->enum(['expense', 'income'])->required(),
            'description' => $schema->string()->required(),
            'category' => $schema->string()->required(),
            'date' => $schema->string()->required(),
        ];
    }
}
