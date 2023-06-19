
Chunk database insertion is supported to handle large file.

Few assumptions:

* If an email is invalid, error prints out and the line is not inserted.
* Name, surname and email are not empty and can't be 0. Otherwise, line won't be inserted.
* Email can be checked against DNS lookup using egulias/email-validator package.
