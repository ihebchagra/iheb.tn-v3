server:
	@echo "Starting PHP development server..."
	php -S localhost:6969 -t public 2>&1 | grep -E -v "\[200\]|Accepted|Closing"

sw:
	@echo "Generating Service Worker..."
	rm public/workbox-*
	bunx workbox generateSW workbox-config.js

bump:
	@./bump.sh

upload:
	@echo "Uploading to testing..."
	@./sync.sh

dl_analytics:
	@echo "Downloading analytics.sqlite from FTP server..."
	@./sync.sh download_analytics

# commit:
# 	@echo "Uploading to prod..."
# 	@./sync_v2.sh /promety.tn/

dev:
	@echo "Starting development environment..."
	@$(MAKE) server &
	@$(MAKE) bump

.PHONY: server sw bump upload commit dl_analytics
