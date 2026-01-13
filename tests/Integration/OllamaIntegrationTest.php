<?php

namespace Josecanciani\Fat\Tests\Integration;

use Josecanciani\Fat\Image\ImageService;
use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Text\TextService;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use PHPUnit\Framework\TestCase;

class OllamaIntegrationTest extends TestCase {
    private function createVisionService(): ImageService {
        $config = new OllamaConfig();
        $config->model = 'llama3.2-vision';

        $chat = new OllamaChat($config);
        $labelManager = new LabelManager();

        return new ImageService($chat, $labelManager);
    }

    private function createTextService(): TextService {
        $config = new OllamaConfig();
        $config->model = 'llama3.2';

        $chat = new OllamaChat($config);
        $labelManager = new LabelManager();

        return new TextService($chat, $labelManager);
    }

    public function testImageIsClassifiedAsNationalIdUsingOllama(): void {
        $service = $this->createVisionService();
        $filePath = __DIR__ . '/../../resources/testFiles/1.randomName.jpg';

        try {
            $result = $service->classify($filePath);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'Connection timed out')) {
                $this->markTestSkipped('Ollama is not reachable: ' . $e->getMessage());
            }

            throw $e;
        }

        $labels = $result->getLabels();

        $this->assertContains('National ID', $labels);
    }

    public function testPhpFileIsClassifiedUsingOllamaTextModel(): void {
        $service = $this->createTextService();
        $filePath = __FILE__;

        try {
            $result = $service->classify($filePath);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'Connection timed out')) {
                $this->markTestSkipped('Ollama is not reachable: ' . $e->getMessage());
            }

            throw $e;
        }

        $labels = $result->getLabels();

        $this->assertNotSame([], $labels, 'Expected at least one label from Ollama for PHP file.');
    }
}
