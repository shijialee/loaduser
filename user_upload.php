<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use GetOpt\GetOpt;
use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;


define("TABLE_NAME", 'users');
define("DATABASE", 'catalyst');
# assume 1 line is 50 bytes.  20 Million lines is around 100M.
# change this value based on how much RAM is available to you..
define("CHUNK_SIZE", 20000000);

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

function load_file(string $file, bool $dry_run): void {
    if (mime_content_type($file) !== 'text/csv') {
        error_log("file is not in csv format.");
        return;
    }
    if (!$dry_run && !DB::Schema()->hasTable(TABLE_NAME)) {
        error_log("please create table first.");
        return;
    }


    $file = new SplFileObject($file);
    $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD);

    $data = [];
    foreach ($file as $num_of_row => $row) {
        // ignore csv header
        if ($num_of_row == 0) {
            continue;
        }

        list($name, $surname, $email) = $row;
        $name = ucfirst(strtolower(trim($name)));
        $surname = ucfirst(strtolower(trim($surname)));
        $email = strtolower(trim($email));

        // XXX line will also be skipped if value is 0
        if (empty($name)) {
            error_log(sprintf("name is missing and line is skipped"));
            continue;
        }
        if (empty($surname)) {
            error_log(sprintf("surname is missing and line is skipped"));
            continue;
        }
        if (empty($email)) {
            error_log(sprintf("email is missing and line is skipped"));
            continue;
        }

        $valid_email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$valid_email) {
            error_log(sprintf("%s is not a valid email address and not inserted", $email));
            continue;
        }

        if (!$dry_run) {
            $data[] = [
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
            ];
            if (count($data) % CHUNK_SIZE == 0) {
                DB::table(TABLE_NAME)->insertOrIgnore($data);
                $data = [];
            }
        }
    }
    if ($data) {
        DB::table(TABLE_NAME)->insertOrIgnore($data);
    }
}

function create_table(): void {
    if (DB::Schema()->hasTable(TABLE_NAME)) {
        printf("table already exist. skip creation\n");
        return;
    }

    DB::schema()->create(TABLE_NAME, function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->string('surname');
        $table->string('email')->unique();
        $table->timestamps();
    });
}

function main(): void {
    $options = get_options();

    $db = new DB;
    $db->addConnection([
        'driver' => 'mysql',
        'host' => $options['h'],
        'database' => DATABASE,
        'username' => $options['u'],
        'password' => $options['p'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $db->setAsGlobal();

    if (isset($options['create_table'])) {
        create_table();
    } elseif ($options['file']) {
        $dry_run = $options['dry_run'] ?? false;
        load_file($options['file'], $dry_run);
    }
}

main();
