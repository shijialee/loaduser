This is a command line tool that takes a csv file and load content into database.

Chunk database insertion is supported to handle large file.

## Few assumptions:

* If an email is invalid, error prints out and the line is not inserted.
* Name, surname and email are not empty and can't be 0. Otherwise, line won't be inserted.
* If it's a csv file, the first line is always the header.

## Usage

#### Setup

This setup uses docker and php so make sure they are installed on your local system.

Run the following commands to setup development environment.

1. `make install`  : Pulls docker images for database and php composer. Builds composer packages.
1. `make start-db`  : start database docker container on 127.0.0.1, port 3306.
  * this starts a mysql database with default user, password and database set to `catalyst`.

#### Run the program

* create table
  * `php user_upload.php --create_table -u catalyst -p catalyst -h 127.0.0.1`
* dry run
  * `php user_upload.php --dry_run --file users.csv -u catalyst -p catalyst -h 127.0.0.1`
* load users.csv file to database table.
  * `php user_upload.php --file users.csv -u catalyst -p catalyst -h 127.0.0.1

#### Clean up

* `make clean`: remove docker container and network

## Further Improvements

* Email can be checked against DNS lookup using `egulias/email-validator` package.

