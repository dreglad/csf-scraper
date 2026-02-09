#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpCfdi\CsfScraper\Scraper;

function showUsage(): void
{
    echo "Uso: php bin/scraper.php <archivo.pdf|url|--json-schema>" . PHP_EOL;
    echo "  Opciones:" . PHP_EOL;
    echo "    archivo.pdf   : Ruta al archivo PDF local" . PHP_EOL;
    echo "    url           : URL pública al archivo PDF" . PHP_EOL;
    echo "    --json-schema : Muestra el esquema JSON de la salida" . PHP_EOL;
    echo "    -h, --help    : Muestra este mensaje de ayuda" . PHP_EOL;
    echo PHP_EOL;
    echo "Ejemplos:" . PHP_EOL;
    echo "  php bin/scraper.php constancia.pdf" . PHP_EOL;
    echo "  php bin/scraper.php https://dominio.com/mi_constancia.pdf" . PHP_EOL;
    echo "  php bin/scraper.php --json-schema" . PHP_EOL;
}

if ($argc < 2 || in_array($argv[1], ['-h', '--help'])) {
    showUsage();
    exit(0);
}

if ($argv[1] === '--json-schema') {
    echo file_get_contents(__DIR__ . '/../docs/schemas/csf.schema.json');
    exit(0);
}

$input = $argv[1];
$isUrl = filter_var($input, FILTER_VALIDATE_URL);
$tempFile = null;

try {
    if ($isUrl) {
        $tempFile = tempnam(sys_get_temp_dir(), 'csf_');
        $content = file_get_contents($input);
        if ($content === false) {
            throw new Exception("No se pudo descargar el archivo desde la URL: $input");
        }
        file_put_contents($tempFile, $content);
        $path = $tempFile;
    } else {
        $path = $input;
        if (!file_exists($path)) {
            throw new Exception("El archivo no existe en la ruta: $path");
        }
    }

    $scraper = Scraper::create();
    $person = $scraper->obtainFromPdfPath($path);

    echo json_encode($person, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($tempFile && file_exists($tempFile)) {
        unlink($tempFile);
    }
}
