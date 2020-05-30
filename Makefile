install:
	composer install

lint:
	composer run-script phpcs -- --standard=PSR12 src bin

test:
	composer run-script phpunit tests

test-coverage:
	composer phpunit tests -- --coverage-clover build/logs/clover.xml