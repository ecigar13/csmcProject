docker-compose exec php composer install
docker-compose exec php yarn install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:schema:update --force
docker-compose exec php php bin/console doctrine:fixtures:load
docker-compose exec php yarn run encore dev

#Note to self: run sudo docker-compose build on Linux. Make sure to start docker service.
#Change the entire folder to chmod 755.

