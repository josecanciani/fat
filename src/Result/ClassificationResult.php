<?php

namespace Josecanciani\Fat\Result;

final class ClassificationResult {
    /** @var string[] */
    private array $labels;

    private string $raw;

    /**
     * @param string[] $labels
     */
    public function __construct(array $labels, string $raw) {
        $this->labels = array_values($labels);
        $this->raw = $raw;
    }

    /**
     * @return string[]
     */
    public function getLabels(): array {
        return $this->labels;
    }

    public function getRaw(): string {
        return $this->raw;
    }
}
