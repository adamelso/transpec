install:
	composer install --optimize-autoloader

test:
	./bin/phpspec run --format=pretty --no-interaction
	./bin/console transpec:convert spec --no-interaction -v
	./bin/phpunit

test_candidates:
	./bin/console transpec:convert ./vendor/sylius/addressing/spec  var/candidates/sylius/addressing -v -n
	./bin/phpunit var/candidates
