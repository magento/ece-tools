.PHONY: help

# https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.DEFAULT_GOAL := help

pull: ## Pull latest images
	docker-compose pull

down: ## Destroy containers
	docker-compose down -v

up: ## Re-create and start containers
	docker-compose down -v
	docker-compose up -d
	docker-compose run build cloud-build
	docker-compose run deploy cloud-deploy

stop: ## Stop containers
	docker-compose stop

start: ## Resume containers
	docker-compose start

bash: ## Connect to bash
	docker-compose run cli bash
