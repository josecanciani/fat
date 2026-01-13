<?php

namespace Josecanciani\Fat\Tests\Unit;

use Josecanciani\Fat\Image\ImageService;
use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Tests\Support\MockChat;
use PHPUnit\Framework\TestCase;

class ImageServiceTest extends TestCase {
    public function testItClassifiesRandomNameJpgAsNationalId(): void {
        $labelManager = new LabelManager();

        $chat = new MockChat('', 'National ID');

        $service = new ImageService($chat, $labelManager);

        $filePath = __DIR__ . '/../../resources/testFiles/1.randomName.jpg';
        $result = $service->classify($filePath);

        $labels = $result->getLabels();

        $this->assertContains('National ID', $labels);
    }
}
