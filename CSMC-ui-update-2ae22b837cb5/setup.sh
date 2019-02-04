#!/bin/bash

sudo docker-compose exec php composer install
sudo docker-compose exec php yarn install
sudo docker-compose exec php php bin/console doctrine:database:create
sudo docker-compose exec php php bin/console doctrine:schema:update --force
sudo sudo docker-compose exec php php bin/console doctrine:fixtures:load




