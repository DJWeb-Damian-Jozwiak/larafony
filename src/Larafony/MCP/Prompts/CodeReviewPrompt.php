<?php

declare(strict_types=1);

namespace Larafony\Framework\MCP\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

/**
 * MCP prompt for code review assistance.
 *
 * Generates structured prompts for AI-assisted code review,
 * following Larafony framework conventions and best practices.
 */
class CodeReviewPrompt
{
    /**
     * @return array<int, PromptMessage>
     */
    #[McpPrompt(
        name: 'code_review',
        description: 'Generate a code review prompt for PHP/Larafony code',
    )]
    public function codeReview(
        #[Schema(description: 'The code to review', type: 'string')]
        string $code,
        #[Schema(description: 'Focus areas (security, performance, style)', type: 'string')]
        string $focus = 'all',
    ): array {
        $systemPrompt = $this->buildSystemPrompt($focus);

        return [
            new PromptMessage(
                role: Role::User,
                content: new TextContent($systemPrompt),
            ),
            new PromptMessage(
                role: Role::User,
                content: new TextContent("Please review the following code:\n\n```php\n{$code}\n```"),
            ),
        ];
    }

    /**
     * @return array<int, PromptMessage>
     */
    #[McpPrompt(
        name: 'refactor_suggestion',
        description: 'Generate refactoring suggestions for legacy code',
    )]
    public function refactorSuggestion(
        #[Schema(description: 'The code to refactor', type: 'string')]
        string $code,
        #[Schema(description: 'Target PHP version', type: 'string')]
        string $targetVersion = '8.5',
    ): array {
        return [
            new PromptMessage(
                role: Role::User,
                content: new TextContent(
                    'You are a PHP modernization expert. Analyze the following code and suggest ' .
                    "refactoring improvements targeting PHP {$targetVersion}. " .
                    "Focus on:\n" .
                    "- Modern PHP syntax (property hooks, asymmetric visibility)\n" .
                    "- PSR compliance\n" .
                    "- Type safety improvements\n" .
                    "- Attribute-based configuration\n\n" .
                    "Code to refactor:\n\n```php\n{$code}\n```"
                ),
            ),
        ];
    }

    private function buildSystemPrompt(string $focus): string
    {
        $basePrompt = 'You are a code review assistant specializing in PHP 8.5 and Larafony framework. ';

        return match ($focus) {
            'security' => $basePrompt . 'Focus on security vulnerabilities: SQL injection, XSS, CSRF, authentication issues.',
            'performance' => $basePrompt . 'Focus on performance: N+1 queries, memory usage, caching opportunities.',
            'style' => $basePrompt . 'Focus on code style: PSR-12, naming conventions, documentation.',
            default => $basePrompt . 'Review for security, performance, maintainability, and adherence to Larafony conventions.',
        };
    }
}
