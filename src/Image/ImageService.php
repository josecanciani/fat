<?php

namespace Josecanciani\Fat\Image;

use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Result\ClassificationResult;
use LLPhant\Chat\ChatInterface;
use LLPhant\Chat\Vision\ImageSource;
use LLPhant\Chat\Vision\VisionMessage;

class ImageService {
    /** @var ChatInterface */
    private $chat;

    /** @var LabelManager */
    private $labelManager;

    /**
     * @param ChatInterface $chat Vision-capable chat implementation
     * @param LabelManager  $labelManager
     */
    public function __construct(ChatInterface $chat, LabelManager $labelManager) {
        $this->chat = $chat;
        $this->labelManager = $labelManager;
    }

    public function supports(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }

        $mimeType = mime_content_type($filePath) ?: '';

        return substr($mimeType, 0, 6) === 'image/';
    }

    /**
     * @param string $filePath
     *
     * @return ClassificationResult
     */
    public function classify(string $filePath): ClassificationResult {
        if (! $this->supports($filePath)) {
            throw new \InvalidArgumentException("File '$filePath' is not a supported image file.");
        }

        $labels = $this->labelManager->getLabelsForType('image');
        $labelsString = implode(', ', $labels);

        $prompt = "Task: Document Classification.
Examine the provided image and categorize it into one or more of these labels: [{$labelsString}].
If the document does not clearly match any label, respond with 'Unknown'.
Return one label per line in the output, using only the label names.";

        $fileContents = file_get_contents($filePath);
        if ($fileContents === false) {
            throw new \RuntimeException("Unable to read file '$filePath'.");
        }

        $base64 = base64_encode($fileContents);
        $imageSource = new ImageSource($base64);

        $messages = [
            VisionMessage::fromImages([$imageSource], $prompt),
        ];

        $response = $this->chat->generateChat($messages);
        $result = trim($response);

        $lines = preg_split('/\r\n|\r|\n/', $result);
        $validLabels = [];

        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $normalized = rtrim($line, " .\t\n\r\0\x0B");

                if (strcasecmp($normalized, 'Unknown') === 0) {
                    continue;
                }

                if (in_array($normalized, $labels, true)) {
                    $validLabels[] = $normalized;
                }
            }
        }

        if ($validLabels === []) {
            return new ClassificationResult([], $result);
        }

        $validLabels = array_values(array_unique($validLabels));

        return new ClassificationResult($validLabels, $result);
    }
}
