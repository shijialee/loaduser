This is a command line tool that takes a csv file and load content into database.

Chunk database insertion is supported to handle large file.

## Few assumptions:

* If an email is invalid, error prints out and the line is not inserted.
* Name, surname and email are not empty and can't be 0. Otherwise, line won't be inserted.
* Email can be checked against DNS lookup using egulias/email-validator package.
* If it's a csv file, the first line is always the header.
* docker, php, php composer are installed in your local system.

## Usage

#### Setup

Run the following commands to setup development environment.

1. `make db-image`  : pull mysql:8 docker image.
1. `make start-db`  : start database docker container.
1. `composer install` : install required php packages.

#### Run the program

call user_load.php with users.csv file

* `make create-table` : call `create_table` argument.
* `make dry-run` : dry run load file.
* `make load-file` : load users.csv file to database table.
* `make select-table` : to see what's in the table.

#### Clean up

* `make clean`: remove docker container and network
