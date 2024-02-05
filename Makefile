help: ## Show command list
	@awk -F ':|##' '/^[^\t].+?:.*?##/ {printf "\033[36m%-30s\033[0m %s\n", $$1, $$NF}' $(MAKEFILE_LIST)
start: ## Start the container
	docker-compose up -d --remove-orphans
stop: ## Stop the container
	docker-compose stop
shell: ## Enter the docker shell for the container
	docker exec -it laravel-chunky bash
