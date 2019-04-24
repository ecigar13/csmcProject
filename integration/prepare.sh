docker-compose build
docker-compose up -d

docker-compose exec php composer install
docker-compose exec php yarn install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:schema:update --force
docker-compose exec php php bin/console doctrine:fixtures:load
docker-compose exec php yarn run encore dev

docker-compose exec php php bin/console app:add-user yournetid developer

docker-compose exec php composer require symfony/filesystem
docker-compose exec php composer require symfony/finder
docker-compose exec php composer require vich/uploader-bundle "^1.8"

docker-compose exec php composer require artgris/filemanager-bundle

docker-compose exec php php bin/console assets:install --symlink


