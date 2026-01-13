<?php

namespace Josecanciani\Fat\Text;

use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Result\ClassificationResult;
use LLPhant\Chat\ChatInterface;

class TextService {
    /** @var ChatInterface */
    private $chat;

    /** @var LabelManager */
    private $labelManager;

    /**
     * @param ChatInterface $chat
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

        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
            return true;
        }

        return substr($mimeType, 0, 5) === 'text/'
            || $mimeType === 'application/json'
            || $mimeType === 'application/xml'
            || $mimeType === 'application/x-httpd-php';
    }

    /**
     * @param string $filePath
     *
     * @return ClassificationResult
     */
    public function classify(string $filePath): ClassificationResult {
        if (! $this->supports($filePath)) {
            throw new \InvalidArgumentException("File '$filePath' is not a supported text file.");
        }

        $labels = $this->labelManager->getLabelsForType('text');
        $labelsString = implode(', ', $labels);

        $prompt = "Task: Document Classification.
You will be given the contents of a file.
Classify it into one or more of these labels: [{$labelsString}].
If the document does not clearly match any label, respond with 'Unknown'.
Return one label per line in the output, using only the label names.

Here is the file content:\n\n";

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read file '$filePath'.");
        }

        $response = $this->chat->generateText($prompt . $contents);
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
