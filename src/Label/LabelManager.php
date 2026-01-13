<?php

namespace Josecanciani\Fat\Label;

class LabelManager {
    /**
     * @var array<string, string>
     */
    private $customLabelFiles;

    /**
     * @param array<string, string> $customLabelFiles Map of type => absolute or relative path to labels JSON
     */
    public function __construct(array $customLabelFiles = []) {
        $this->customLabelFiles = $customLabelFiles;
    }

    /**
     * @return string[]
     */
    public function getLabelsForType(string $type): array {
        if (isset($this->customLabelFiles[$type])) {
            $path = $this->customLabelFiles[$type];

            if (! file_exists($path)) {
                throw new \RuntimeException("Custom labels file not found for type '$type' at path '$path'.");
            }
        } else {
            $path = __DIR__ . '/../../resources/labels/' . $type . '.json';

            if (! file_exists($path)) {
                throw new \RuntimeException("Labels file not found for type '$type'.");
            }
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read labels file for type '$type'.");
        }

        $data = json_decode($contents, true);
        if (! is_array($data)) {
            throw new \RuntimeException("Invalid labels file format for type '$type'.");
        }

        return array_values(array_map('strval', $data));
    }
}
