<?php

namespace Josecanciani\Fat\Tests\Unit;

use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Tests\Support\MockChat;
use Josecanciani\Fat\Text\TextService;
use PHPUnit\Framework\TestCase;

class TextServiceTest extends TestCase {
    public function testPhpFileIsClassifiedAsSourceCodeAndPhpSourceCode(): void {
        $labelManager = new LabelManager();

        $chat = new MockChat("Source Code\nPHP Source Code", '');

        $service = new TextService($chat, $labelManager);

        $filePath = __FILE__;
        $result = $service->classify($filePath);

        $labels = $result->getLabels();

        $this->assertContains('Source Code', $labels);
        $this->assertContains('PHP Source Code', $labels);
    }
}
