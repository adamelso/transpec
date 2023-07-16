install:
	composer install --optimize-autoloader

test:
	./bin/phpspec run --format=pretty --no-interaction
	./bin/console transpec:convert spec --no-interaction -v
	./bin/phpunit
