# FAT – File Analyzer & Tagger

FAT is a PHP project that classifies files using LLPhant (with for example, an Ollama backend). It supports both image and text files and returns one or more semantic labels for each file.

It contains a CLI tool and the services that can be used in your own PHP code.

## Quick start

### Run from the CLI

From the project root, after installing dependencies:

```bash
$ php bin/fatOllama.php --help
Description:
  Classify a file using the Ollama backend

Usage:
  fat:classify:ollama [options] [--] <file>

Arguments:
  file                               Path to the file to classify

Options:
      --vision-model[=VISION-MODEL]  Vision model name [default: "llama3.2-vision"]
      --text-model[=TEXT-MODEL]      Text model name [default: "llama3.2"]
      --image-labels[=IMAGE-LABELS]  Path to custom image labels JSON file
      --text-labels[=TEXT-LABELS]    Path to custom text labels JSON file
```

Example:
```bash
$ php bin/fatOllama.php resources/testFiles/1.randomName.jpg
Classification Result: National ID
```

### Use the services in your own PHP code

You can also use the underlying services directly in your own code by wiring an LLPhant chat implementation:

```php

use Josecanciani\Fat\Image\ImageService;
use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Text\TextService;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;

$labelManager = new LabelManager([
    'image' => __DIR__ . '/my-labels/image.json'
]);

$visionConfig = new OllamaConfig();
$visionConfig->model = 'llama3.2-vision';
$visionChat = new OllamaChat($visionConfig);
$imageService = new ImageService($visionChat, $labelManager);

$imageResult = $imageService->classify('resources/testFiles/1.randomName.jpg');
// Example:
// $imageResult->getLabels(); // ['National ID']
// $imageResult->getRaw();    // "National ID\nPortrait Photo" (full model output before filtering)
```

For text files, use the TextService instead:

```php
$textConfig = new OllamaConfig();
$textConfig->model = 'llama3.2';
$textChat = new OllamaChat($textConfig);
$textService = new TextService($textChat, $labelManager);
```

## Requirements

- PHP **8.1+**
- Composer
- Ollama running locally (default: `http://localhost:11434`)
- An Ollama vision model (e.g. `llama3.2-vision`) and text model (e.g. `llama3.2`) already pulled

## Installation

From the project root:

```bash
composer install
```

This installs:

- `theodo-group/llphant` – LLPhant PHP framework
- `symfony/console` – CLI framework

## High-level architecture

- `bin/fatOllama.php`
  - Symfony Console entrypoint.
  - Registers the `fat:classify:ollama` command and runs it by default.
- `src/Command/Ollama.php`
  - Symfony Console command `fat:classify:ollama`.
  - Parses CLI arguments/options, wires up LLPhant `ChatInterface` implementations, and calls the services.
- `src/Image/ImageService.php`
  - Accepts a `ChatInterface` and a `LabelManager`.
  - Validates that the file is an image (`mime_type` starts with `image/`).
  - Uses `VisionMessage` + `ImageSource` to send image content to the model.
  - Normalizes and validates the model output against allowed labels.
- `src/Text/TextService.php`
  - Accepts a `ChatInterface` and a `LabelManager`.
  - Accepts text-ish MIME types (`text/*`, `application/json`, `application/xml`).
  - Reads the file as text and prompts a text model to classify it.
- `src/Label/LabelManager.php`
  - Loads the set of allowed labels per file type from JSON files.
- `resources/labels/*.json`
  - `image.json` and `text.json` define the labels used by the services.

All services depend only on LLPhant's **`ChatInterface`**, so you can swap backends (OpenAIChat, AnthropicChat, etc.) by changing only the command wiring.

## Directory structure

```text
bin/
  fatOllama.php         # Main CLI entry for Ollama backend

resources/
  labels/
    image.json          # Labels for image classification
    text.json           # Labels for text classification
  testFiles/
    ...                 # Sample files for manual testing

src/
  Command/
    Ollama.php          # Symfony Console command (fat:classify:ollama)
  Image/
    ImageService.php    # Image classification service
  Text/
    TextService.php     # Text classification service
  Label/
    LabelManager.php    # Loads label definitions

vendor/                 # Composer dependencies
composer.json
composer.lock
README.md
```

## Usage

### Basic classification (Ollama backend)

From the project root:

```bash
php bin/fatOllama.php path/to/file
```

Examples:

```bash
php bin/fatOllama.php resources/testFiles/1.randomName.jpg
php bin/fatOllama.php some/code/file.php
```

The command will:

1. Detect whether the file is an **image** or a **text** file.
2. Route to `ImageService` or `TextService` accordingly.
3. Print the classification result, which may contain **one or more labels**, for example:

```text
Classification Result: National ID, Portrait Photo
```

If the model produces something that does not match the allowed labels, you will see:

```text
Classification Result: No matching label found (Model returned: ...).
```

### Choosing models

You can override the default models from the command line:

```bash
php bin/fatOllama.php path/to/file \
  --vision-model=llama3.2-vision \
  --text-model=llama3.2
```

- `--vision-model` is used for **image** files (must be a vision-capable Ollama model).
- `--text-model` is used for **text** files.

## Testing

This project ships with both unit tests and integration tests.

### Unit tests (mocked ChatInterface)

Unit tests exercise the services with a mocked `ChatInterface` implementation (no real Ollama calls):

```bash
composer test
```

This runs the suite under `tests/Unit/`:

- `ImageServiceTest` – verifies that an image like `resources/testFiles/1.randomName.jpg` can be classified as `National ID`.
- `TextServiceTest` – verifies that a PHP file can be classified as both `Source Code` and `PHP Source Code`.

### Integration tests (real Ollama)

Integration tests call a local Ollama instance using `OllamaChat` and the models configured in code:

```bash
composer test:integration
```

This runs the suite under `tests/Integration/`, for example:

- `OllamaIntegrationTest` – checks that:
  - `resources/testFiles/1.randomName.jpg` is classified with `National ID` among its labels.
  - A real PHP file yields at least one label using the text model.

If Ollama is not reachable, the integration tests will be marked as **skipped** rather than failed.

## How classification works

### Labels

Labels are defined in JSON files under `resources/labels/`:

- `image.json` – labels for images
- `text.json` – labels for text/code

The services load these labels via `LabelManager` and include them in the prompt as the only allowed outputs.

You can also provide **custom label files**:

- From the CLI, use:

  ```bash
  php bin/fatOllama.php path/to/file \
    --image-labels=/absolute/or/relative/path/to/image-labels.json \
    --text-labels=/absolute/or/relative/path/to/text-labels.json
  ```

  - `--image-labels` sets the labels used for image classification.
  - `--text-labels` sets the labels used for text/code classification.
  - If these options are omitted, the built-in `resources/labels/*.json` files are used.

- In your own PHP code, you can construct `LabelManager` with a custom map of label files per type:

  ```php
  use Josecanciani\Fat\Label\LabelManager;

  $labelManager = new LabelManager([
      'image' => __DIR__ . '/my-labels/image.json',
      'text'  => __DIR__ . '/my-labels/text.json',
  ]);
  ```

When custom label files are provided for a given type, they are **used in preference** to the default project label files.

### Image flow

1. `ImageService::supports($filePath)` checks `mime_content_type($filePath)` starts with `image/`.
2. The file is read, base64-encoded and wrapped in an LLPhant `ImageSource`.
3. A `VisionMessage` is built with the prompt + image.
4. `ChatInterface::generateChat()` is called on the injected chat (currently an `OllamaChat` with a vision model).
5. The returned text is split into lines; each non-empty line is normalized and, if it matches a known label, is collected. Multiple labels may be returned.

### Text flow

1. `TextService::supports($filePath)` checks for `text/*`, `application/json`, or `application/xml`.
2. The file contents are read as text.
3. A prompt including the label list and the file contents is sent with `ChatInterface::generateText()`, instructing the model to return one or more labels, one per line.
4. The output is split into lines; each is normalized and checked against the allowed labels. Multiple labels may be returned.

## Changing backends later

Right now `src/Command/Ollama.php` wires in `OllamaChat` instances. To switch to another LLPhant backend (e.g. `OpenAIChat`):

1. Create the appropriate `Config` object and `Chat` implementation in the command.
2. Inject those into `ImageService` and `TextService` instead of `OllamaChat`.

The services themselves do not depend on Ollama-specific types—only on `ChatInterface`, `VisionMessage`, and `ImageSource` from LLPhant.

## Editor / tooling

- VS Code workspace settings under `.vscode/settings.json`:
  - `intelephense.environment.phpVersion` set to `8.1.0`.
  - Reasonable defaults for whitespace, ignored folders, and Intelephense performance.

These settings are there so static analysis and tooling match the runtime environment.

## Contributing

Contributions are welcome. Before opening a pull request or generating code with an LLM, please read:

- `LLM_CODE_STYLE.md` – defines the code style rules and conventions (brace placement, spacing, naming, DTO usage, test layout, etc.) that should be followed across this project.

Following these guidelines helps keep the codebase consistent and makes reviews simpler.
