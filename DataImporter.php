<?php

namespace catalyst;

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;


class DataImporter {
    const TABLE_NAME = 'users';
    const DATABASE = 'catalyst';
    # assume 1 line is 50 bytes.  20 Million lines is around 100M.
    # change this value based on how much RAM is available to you..
    const CHUNK_SIZE = 20000000;

    public function __construct($db_host, $db_username, $db_password) {
        $db = new DB;
        $db->addConnection([
            'driver' => 'mysql',
            'host' => $db_host,
            'database' => self::DATABASE,
            'username' => $db_username,
            'password' => $db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        $db->setAsGlobal();
    }

    public function load_file(string $file, bool $dry_run): void {
        if (!file_exists($file)) {
            error_log("file doesn't exist");
            return;
        }
        if (mime_content_type($file) !== 'text/csv') {
            error_log("file is not in csv format.");
            return;
        }
        if (!$dry_run) {
            if (!$this->_valid_db_connection()) {
                return;
            }
            if (!$this->_has_table()) {
                error_log("please create table first.");
                return;
            }
        }

        $file = new \SplFileObject($file);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE | \SplFileObject::READ_AHEAD);

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

            if (!$this->_check_csv_fields($name, $surname, $email)) {
                continue;
            }

            if (!$dry_run) {
                $data[] = [
                    'name' => $name,
                    'surname' => $surname,
                    'email' => $email,
                ];
                if (count($data) % self::CHUNK_SIZE == 0) {
                    DB::table(self::TABLE_NAME)->insertOrIgnore($data);
                    $data = [];
                }
            }
        }
        if (!$dry_run && $data) {
            DB::table(self::TABLE_NAME)->insertOrIgnore($data);
        }
    }

    public function create_table(): void {
        if (!$this->_valid_db_connection()) {
            return;
        }

        if ($this->_has_table()) {
            error_log("table alread exist.");
            return;
        }

        DB::schema()->create(self::TABLE_NAME, function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('surname');
            $table->string('email')->unique();
            $table->timestamps();
        });
        echo "Table is created\n";
    }

    private function _valid_db_connection(): bool {
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            error_log('Error connecting to database');
            return false;
        }
        return true;
    }

    private function _has_table(): bool {
        if (!DB::Schema()->hasTable(self::TABLE_NAME)) {
            return false;
        }
        return true;
    }

    private function _check_csv_fields($name, $surname, $email) {
        // XXX line will also be skipped if value is 0
        if (empty($name)) {
            error_log(sprintf("name is missing and line is skipped"));
            return false;
        }
        if (empty($surname)) {
            error_log(sprintf("surname is missing and line is skipped"));
            return false;
        }
        if (empty($email)) {
            error_log(sprintf("email is missing and line is skipped"));
            return false;
        }

        $valid_email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!$valid_email) {
            error_log(sprintf("%s is not a valid email address and not inserted", $email));
            return false;
        }
        return true;
    }

}
