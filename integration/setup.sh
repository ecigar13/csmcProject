docker-compose exec php composer install
docker-compose exec php yarn install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:schema:update --force
docker-compose exec php php bin/console doctrine:fixtures:load
docker-compose exec php yarn run encore dev

docker-compose exec php composer require symfony/filesystem
docker-compose exec php composer require symfony/finder
docker-compose exec php composer require vich/uploader-bundle "^1.8"
docker-compose exec php composer require artgris/filemanager-bundle

#Note to self: run sudo docker-compose build on Linux. Make sure to start docker service.
#Change the entire folder to chmod 755.

