docker-compose build
docker-compose up -d

docker-compose exec php composer install
docker-compose exec php yarn install
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:schema:update --force
docker-compose exec php php bin/console doctrine:fixtures:load
docker-compose exec php yarn run encore dev

docker-compose exec php php bin/console app:add-user dxl170030 developer
