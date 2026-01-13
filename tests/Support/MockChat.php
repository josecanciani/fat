<?php

namespace Josecanciani\Fat\Tests\Support;

use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\FunctionInfo\FunctionInfo;
use Psr\Http\Message\StreamInterface;

class MockChat implements ChatInterface {
    private string $textResponse;
    private string $chatResponse;

    public function __construct(string $textResponse = '', string $chatResponse = '') {
        $this->textResponse = $textResponse;
        $this->chatResponse = $chatResponse;
    }

    public function generateText(string $prompt): string {
        return $this->textResponse;
    }

    public function generateTextOrReturnFunctionCalled(string $prompt): string|array {
        return $this->generateText($prompt);
    }

    public function generateStreamOfText(string $prompt): StreamInterface {
        throw new BadMethodCallException('Not implemented in MockChat');
    }

    public function generateChat(array $messages): string {
        return $this->chatResponse;
    }

    public function generateChatOrReturnFunctionCalled(array $messages): string|array {
        return $this->generateChat($messages);
    }

    public function generateChatStream(array $messages): StreamInterface {
        throw new BadMethodCallException('Not implemented in MockChat');
    }

    public function setSystemMessage(string $message): void {}

    public function setTools(array $tools): void {}

    public function addTool(FunctionInfo $functionInfo): void {}

    public function setFunctions(array $functions): void {}

    public function addFunction(FunctionInfo $functionInfo): void {}

    public function setModelOption(string $option, mixed $value): void {}
}
