DOCKER_COMPOSE_COMMAND=cd docker && docker compose -p $(shell basename $(CURDIR))

.PHONY: build
build:
	$(DOCKER_COMPOSE_COMMAND) build app

.PHONY: up
up:
	$(DOCKER_COMPOSE_COMMAND) up -d

.PHONY: down
down:
	$(DOCKER_COMPOSE_COMMAND) down --remove-orphans

.PHONY: test
logs:
	$(DOCKER_COMPOSE_COMMAND) logs

.PHONY: test
test:
	$(DOCKER_COMPOSE_COMMAND) run app phpunit

.PHONY: bash
bash:
	$(DOCKER_COMPOSE_COMMAND) run app bash

.PHONY: update
update:
	$(DOCKER_COMPOSE_COMMAND) run app composer update

.PHONY: mysql
mysql:
	$(DOCKER_COMPOSE_COMMAND) exec mysql mysql
