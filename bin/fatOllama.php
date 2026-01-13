#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Josecanciani\Fat\Command\Ollama;
use Symfony\Component\Console\Application;

$application = new Application('fat-ollama', '1.0.0');
$application->add(new Ollama());
$application->setDefaultCommand('fat:classify:ollama', true);
$application->run();
