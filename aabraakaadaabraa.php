<?php

/**
 * aabraakaadaabraa.php
 * 
 * This script reads model metadata from a JSON file, and uses template files to generate
 * controllers, repositories, services, and routes for each model. It ensures directories 
 * are created as needed, and old generated code is deleted to avoid conflicts.
 */

// Define paths
$jsonFilePath = __DIR__ . '/modelnames.json';
$templatePath = __DIR__ . '/templates/';
$generatedCodePath = __DIR__ . '/generatedcode/';

// Function to log messages
function logMessage($message)
{
    echo "[" . date('Y-m-d H:i:s') . "] $message\n";
}

// Function to recursively delete a directory
function deleteDirectory($dirPath) {
    if (!is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, -1) !== '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDirectory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

// Function to generate files from templates
function generateFile($templateFileName, $subDirectoryPath, $model, $suffix) {
    global $templatePath;

    // Read the template file content
    $templateContent = file_get_contents($templatePath . $templateFileName);
    if ($templateContent === false) {
        throw new RuntimeException("Error reading template file: $templateFileName");
    }

    // Replace each placeholder with the respective model property
    foreach ($model as $placeholder => $value) {
        $templateContent = str_replace('{{' . $placeholder . '}}', $value, $templateContent);
    }

    // Check for unreplaced placeholders
    if (strpos($templateContent, '{{') !== false || strpos($templateContent, '}}') !== false) {
        logMessage("Warning: Not all placeholders were replaced in $subDirectoryPath{$model['modelName']}$suffix");
    }

    // Define the output file name
    $outputFileName = $subDirectoryPath . $model['modelName'] . $suffix;

    // Write the generated content to the output file
    file_put_contents($outputFileName, $templateContent);
    logMessage("Generated $outputFileName");
}

// Read the JSON file content
$jsonContent = file_get_contents($jsonFilePath);
if ($jsonContent === false) {
    die("Error reading the JSON file at: $jsonFilePath");
}

// Decode JSON content into an associative array
$models = json_decode($jsonContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error decoding JSON: " . json_last_error_msg());
}
if (!is_array($models)) {
    die("JSON file does not contain an array of models.");
}

// Process each model
foreach ($models as $model) {
    logMessage("----------");
    logMessage("Starting code generation for model: " . $model['modelName']);

    // Directory for the model
    $modelDirectoryBase = $generatedCodePath . $model['modelName'] . '/';

    // Delete existing directory for the model
    if (file_exists($modelDirectoryBase)) {
        deleteDirectory($modelDirectoryBase);
        logMessage("Deleted existing directory: " . $model['modelName']);
    }

    // Create the model directory and subdirectories
    $subDirectories = ['Controllers', 'Repositories', 'Services', 'Routes'];
    foreach ($subDirectories as $subDir) {
        $dirPath = $modelDirectoryBase . $subDir . '/';
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
            logMessage("Created directory: $dirPath");
        }
    }

    // Generate files from templates
    generateFile('ModelNameController.template.txt', $modelDirectoryBase . 'Controllers/', $model, 'Controller.php');
    generateFile('ModelNameRepository.template.txt', $modelDirectoryBase . 'Repositories/', $model, 'Repository.php');
    generateFile('ModelNameService.template.txt', $modelDirectoryBase . 'Services/', $model, 'Service.php');
    generateFile('ModelNameRoutes.template.txt', $modelDirectoryBase . 'Routes/', $model, 'Routes.php');

    logMessage("Completed code generation for model: " . $model['modelName']);
}
