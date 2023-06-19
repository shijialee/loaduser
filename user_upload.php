<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use GetOpt\GetOpt;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;


define("TABLE_NAME", 'users');
define("DATABASE", 'catalyst');

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

function create_table(): void {
    DB::schema()->create(TABLE_NAME, function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('surname');
        $table->string('email')->unique();
        $table->timestamps();
    });
}

function main(): void {
    $db = new DB;
    $db->addConnection([
        'driver' => 'mysql',
        'host' => 'localhost',
        'database' => DATABASE,
        'username' => 'root',
        'password' => 'password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $db->setAsGlobal();
}

main();
