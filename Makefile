help:
	@echo
	@echo 'make db-image: pull mysql image'
	@echo 'make start-db: start mysql container'
	@echo 'make clean: clean up container'
	@echo
	@true

db-image:
	docker pull mysql:8

clean:
	docker stop catalyst && docker rm catalyst && docker network rm mysql

start-db:
	docker network create mysql
	docker run --name catalyst \
		--net=mysql \
		-e MYSQL_ROOT_PASSWORD=catalyst  \
		-e MYSQL_DATABASE=catalyst  \
		-e MYSQL_USER=catalyst \
		-e MYSQL_PASSWORD=catalyst \
		-p 127.0.0.1:3306:3306 \
		-d mysql:8

create-table:
	php user_upload.php --create_table -u catalyst -p catalyst -h 127.0.0.1

dry-run:
	php user_upload.php --dry_run --file users.csv -u catalyst -p catalyst -h 127.0.0.1

load-file:
	php user_upload.php --file users.csv -u catalyst -p catalyst -h 127.0.0.1

select-table:
	docker run -it --network mysql --rm mysql:8 mysql -hcatalyst -ucatalyst -pcatalyst catalyst -e 'select * from users;'

