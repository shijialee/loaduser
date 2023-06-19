<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/DataImporter.php';

use GetOpt\GetOpt;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;


function get_options(): array {
    $getOpt = new GetOpt([
       [null, 'file', GetOpt::REQUIRED_ARGUMENT, 'requires a csv file name'],
       ['u', null, GetOpt::REQUIRED_ARGUMENT, 'MySQL username'],
       ['p', null, GetOpt::REQUIRED_ARGUMENT, 'MySQL password'],
       ['h', null, GetOpt::REQUIRED_ARGUMENT, 'MySQL host'],
       [null, 'create_table', GetOpt::NO_ARGUMENT, 'build user table'],
       [null, 'help', GetOpt::NO_ARGUMENT, 'help message'],
       [null, 'dry_run', GetOpt::NO_ARGUMENT,
          "used with the --file directive, run script but the database won't be altered"],
    ]);

    try {
        $getOpt->process();
        if (!$getOpt->getOptions()) {
            throw new Missing('missing arguments.');
        }
        if ($getOpt->getOption('help')) {
            echo PHP_EOL . $getOpt->getHelpText();
            exit;
        }
        if ($getOpt->getOption('dry_run') && !$getOpt->getOption('file')) {
            throw new Missing('--file must be used with --dry_run directive');
        }
        if ($getOpt->getOption('create_table') && $getOpt->getOption('file')) {
            throw new Missing('only one of arguments allowed, pick one from --file and --create_table');
        }
        if (!$getOpt->getOption('u')
            || !$getOpt->getOption('p')
            || !$getOpt->getOption('h')
        ) {
            throw new Missing('database username/password/host are required');
        }
    } catch (Missing | ArgumentException $exception) {
        error_log($exception->getMessage());
        echo PHP_EOL . $getOpt->getHelpText();
        exit;
    }
    return $getOpt->getOptions();
}

function main(): void {
    $options = get_options();
    $importer = new Catalyst\DataImporter(
        $options['h'],
        $options['u'],
        $options['p'],
    );

    if (isset($options['create_table'])) {
        $importer->create_table();
    } elseif ($options['file']) {
        $dry_run = $options['dry_run'] ?? false;
        $importer->load_file($options['file'], $dry_run);
    }
}

main();
