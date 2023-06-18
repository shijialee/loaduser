<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

define("TABLE_NAME", 'users');
define("DATABASE", 'catalyst');


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
