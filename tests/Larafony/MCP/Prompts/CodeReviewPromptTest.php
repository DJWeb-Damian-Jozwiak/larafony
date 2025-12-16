<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\MCP\Prompts;

use Larafony\Framework\MCP\Prompts\CodeReviewPrompt;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Enum\Role;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class CodeReviewPromptTest extends TestCase
{
    private CodeReviewPrompt $prompt;

    protected function setUp(): void
    {
        $this->prompt = new CodeReviewPrompt();
    }

    public function testCodeReviewHasMcpPromptAttribute(): void
    {
        $reflection = new ReflectionClass(CodeReviewPrompt::class);
        $method = $reflection->getMethod('codeReview');
        $attributes = $method->getAttributes(McpPrompt::class);

        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertSame('code_review', $instance->name);
    }

    public function testCodeReviewReturnsPromptMessages(): void
    {
        $result = $this->prompt->codeReview('<?php echo "Hello";');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(PromptMessage::class, $result);
    }

    public function testCodeReviewIncludesCodeInMessage(): void
    {
        $code = '<?php echo "Hello World";';
        $result = $this->prompt->codeReview($code);

        $lastMessage = $result[1];
        $this->assertStringContainsString($code, $lastMessage->content->text);
    }

    public function testCodeReviewSecurityFocus(): void
    {
        $result = $this->prompt->codeReview('<?php', 'security');

        $systemMessage = $result[0];
        $this->assertStringContainsString('security', strtolower($systemMessage->content->text));
    }

    public function testCodeReviewPerformanceFocus(): void
    {
        $result = $this->prompt->codeReview('<?php', 'performance');

        $systemMessage = $result[0];
        $this->assertStringContainsString('performance', strtolower($systemMessage->content->text));
    }

    public function testCodeReviewStyleFocus(): void
    {
        $result = $this->prompt->codeReview('<?php', 'style');

        $systemMessage = $result[0];
        $this->assertStringContainsString('style', strtolower($systemMessage->content->text));
    }

    public function testRefactorSuggestionHasMcpPromptAttribute(): void
    {
        $reflection = new ReflectionClass(CodeReviewPrompt::class);
        $method = $reflection->getMethod('refactorSuggestion');
        $attributes = $method->getAttributes(McpPrompt::class);

        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertSame('refactor_suggestion', $instance->name);
    }

    public function testRefactorSuggestionIncludesTargetVersion(): void
    {
        $result = $this->prompt->refactorSuggestion('<?php', '8.5');

        $message = $result[0];
        $this->assertStringContainsString('8.5', $message->content->text);
    }

    public function testRefactorSuggestionMentionsModernFeatures(): void
    {
        $result = $this->prompt->refactorSuggestion('<?php');

        $message = $result[0];
        $text = $message->content->text;

        $this->assertStringContainsString('property hooks', $text);
        $this->assertStringContainsString('PSR', $text);
    }
}
