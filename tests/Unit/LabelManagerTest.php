<?php

namespace Josecanciani\Fat\Tests\Unit;

use Josecanciani\Fat\Label\LabelManager;
use PHPUnit\Framework\TestCase;

class LabelManagerTest extends TestCase {
    public function testDefaultsAreLoadedWhenNoCustomFilesAreProvided(): void {
        $manager = new LabelManager();

        $imageLabels = $manager->getLabelsForType('image');
        $textLabels = $manager->getLabelsForType('text');

        $this->assertIsArray($imageLabels);
        $this->assertNotEmpty($imageLabels);
        $this->assertIsArray($textLabels);
        $this->assertNotEmpty($textLabels);
    }

    public function testCustomFilesArePreferredOverDefaults(): void {
        $baseDir = __DIR__ . '/../../resources/labels';

        $customImagePath = $baseDir . '/image.json';

        $manager = new LabelManager([
            'image' => $customImagePath,
        ]);

        $imageLabels = $manager->getLabelsForType('image');

        $this->assertIsArray($imageLabels);
        $this->assertNotEmpty($imageLabels);
    }
}
