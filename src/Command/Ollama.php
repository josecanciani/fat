<?php

namespace Josecanciani\Fat\Command;

use Josecanciani\Fat\Image\ImageService;
use Josecanciani\Fat\Label\LabelManager;
use Josecanciani\Fat\Text\TextService;
use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'fat:classify:ollama', description: 'Classify a file using the Ollama backend')]
class Ollama extends Command {
    protected function configure(): void {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the file to classify')
            ->addOption('vision-model', null, InputOption::VALUE_OPTIONAL, 'Vision model name', 'llama3.2-vision')
            ->addOption('text-model', null, InputOption::VALUE_OPTIONAL, 'Text model name', 'llama3.2')
            ->addOption('image-labels', null, InputOption::VALUE_OPTIONAL, 'Path to custom image labels JSON file')
            ->addOption('text-labels', null, InputOption::VALUE_OPTIONAL, 'Path to custom text labels JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $filePath = $input->getArgument('file');
        $visionModel = $input->getOption('vision-model');
        $textModel = $input->getOption('text-model');
        $imageLabelsPath = $input->getOption('image-labels');
        $textLabelsPath = $input->getOption('text-labels');

        if (!is_string($filePath) || $filePath === '') {
            $output->writeln('<error>You must provide a file path.</error>');
            return Command::INVALID;
        }

        if (!file_exists($filePath)) {
            $output->writeln(sprintf('<error>Error: File "%s" not found.</error>', $filePath));
            return Command::FAILURE;
        }

        $customLabelFiles = [];

        if (is_string($imageLabelsPath) && $imageLabelsPath !== '') {
            $customLabelFiles['image'] = $imageLabelsPath;
        }

        if (is_string($textLabelsPath) && $textLabelsPath !== '') {
            $customLabelFiles['text'] = $textLabelsPath;
        }

        $labelManager = new LabelManager($customLabelFiles);

        $visionConfig = new OllamaConfig();
        $visionConfig->model = (string) $visionModel;
        $visionChat = new OllamaChat($visionConfig);

        $textConfig = new OllamaConfig();
        $textConfig->model = (string) $textModel;
        $textChat = new OllamaChat($textConfig);

        $imageService = new ImageService($visionChat, $labelManager);
        $textService  = new TextService($textChat, $labelManager);

        try {
            if ($imageService->supports($filePath)) {
                $result = $imageService->classify($filePath);
            } elseif ($textService->supports($filePath)) {
                $result = $textService->classify($filePath);
            } else {
                $output->writeln(sprintf('<error>Error: Unsupported file type for "%s".</error>', $filePath));
                return Command::FAILURE;
            }

            $labels = $result->getLabels();

            if ($labels === []) {
                $output->writeln(sprintf('Classification Result: No matching label found (Model returned: %s).', $result->getRaw()));
            } else {
                $output->writeln(sprintf('Classification Result: %s', implode(', ', $labels)));
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
