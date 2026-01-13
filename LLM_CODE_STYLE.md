# LLM Code Style Guide for this Project

This document describes how generated code should look in this repository so that it matches the existing style.

## General PHP style

- **PHP version**: Target PHP 8.1.
- **Namespaces**:
  - Production code lives under `Josecanciani\Fat\...`.
  - Test code lives under `Josecanciani\Fat\Tests\...`.
- **PSR-4**: Respect the existing PSR-4 mappings in `composer.json`.

## Formatting

- **Indentation**:
  - Use **4 spaces**, no tabs.
- **Braces**:
  - Opening braces go on the **same line** as class, method, and control-structure declarations.
  - Examples:
    ```php
    class ExampleClass {
        public function doSomething(): void {
            if ($condition) {
                // ...
            }
        }
    }
    ```
- **Whitespace**:
  - Ensure a **final newline** at the end of each file.
  - Trim trailing whitespace.
  - **Do not** leave blank lines between property declarations.
    ```php
    class Example {
        private string $foo;
        private int $bar;
    }
    ```
  - Inside methods, avoid blank lines just to separate sections of logic.
    - If separation is genuinely needed and not obvious from the code itself, use a **short, meaningful comment** instead of an empty line.
    - Do **not** add comments that merely restate what the next line of code clearly does.

## Naming

- **Classes/DTOs**: `PascalCase` (e.g. `ImageService`, `TextService`, `ClassificationResult`).
- **Methods**: `camelCase` (e.g. `classify`, `supports`, `testPhpFileIsClassifiedAsSourceCodeAndPhpSourceCode`).
- **Properties**: `camelCase`.
- **Tests**:
  - Test classes under `tests/Unit` and `tests/Integration` should be named `SomethingTest`.
  - Test method names should be `camelCase` and start with `test`.

## Types and DTOs

- Prefer **strong typing** and DTOs over associative arrays for public APIs.
- Example DTO pattern (as in `ClassificationResult`):
  - `final` class.
  - Private properties with typed declarations.
  - Constructor receives all required data.
  - Public getters (e.g. `getLabels()`, `getRaw()`).

## Services and dependencies

- Services (`ImageService`, `TextService`) should:
  - Receive dependencies via **constructor injection**.
  - Depend on `LLPhant\Chat\ChatInterface` and `LabelManager` rather than concrete chat implementations.
- Use existing value objects and DTOs instead of inventing new ones unless explicitly requested.

## Tests

- **Unit tests**:
  - Use the `Josecanciani\Fat\Tests\Unit` namespace.
  - Prefer shared test utilities like `Josecanciani\Fat\Tests\Support\MockChat` instead of anonymous mocks duplicated across files.
  - Assert on DTO getters (e.g. `$result->getLabels()`), not on raw arrays.
- **Integration tests**:
  - Use the `Josecanciani\Fat\Tests\Integration` namespace.
  - May use real `OllamaChat` and `OllamaConfig`, but should skip gracefully if Ollama is not reachable.

## CLI and Composer

- CLI entry points live under `bin/` and use Symfony Console.
- Composer scripts in `composer.json` should be used to wire commands like `composer test` and `composer test:integration`.

## Documentation and comments

- Do **not** add or remove comments or documentation unless explicitly requested by the user.
- When updating README examples, reflect the actual current API (e.g. use DTO getters instead of array access).

LLM-generated changes should follow these rules unless the user explicitly asks to deviate from them.
