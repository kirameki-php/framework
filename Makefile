DOCKER_COMPOSE_COMMAND=docker-compose -p $(shell basename $(CURDIR))
TARGET=local

.PHONY: build
build:
	docker build -t app:latest --target $(TARGET) .

.PHONY: up
up:
	$(DOCKER_COMPOSE_COMMAND) up -d

.PHONY: down
down:
	$(DOCKER_COMPOSE_COMMAND) down --remove-orphans

.PHONY: test
test:
	$(DOCKER_COMPOSE_COMMAND) exec app phpunit

.PHONY: bash
bash:
	$(DOCKER_COMPOSE_COMMAND) exec app bash

.PHONY: update
update:
	$(DOCKER_COMPOSE_COMMAND) run app composer update

.PHONY: mysql
mysql:
	$(DOCKER_COMPOSE_COMMAND) exec mysql mysql
