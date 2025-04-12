server:
	@echo "Starting PHP development server..."
	php -S localhost:6969 -t public 2>&1 | grep -E -v "\[200\]|Accepted|Closing"
	# php -S localhost:5656 -t public 2>&1 | grep --color=always -E -v "Accepted|Closing"

sw:
	@echo "Generating Service Worker..."
	rm public/workbox-*
	bunx workbox generateSW workbox-config.js

bump:
	@./version-bump.sh

upload:
	@echo "Uploading to testing..."
	@./sync_v2.sh /testing.promety.tn/

commit:
	@echo "Uploading to prod..."
	@./sync_v2.sh /promety.tn/

dev:
	@echo "Starting development environment..."
	@$(MAKE) bump &
	@$(MAKE) server

.PHONY: server sw bump upload commit
