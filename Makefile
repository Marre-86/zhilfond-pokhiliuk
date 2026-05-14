go:
	php artisan serve
install:
	composer install
validate:
	composer validate
lint:
	composer exec --verbose phpcs -- --standard=PSR12 app routes
phpstan:
	vendor/bin/phpstan analyse --memory-limit=2G
test:
	php artisan test --coverage --min=80
check: lint phpstan test

