docker-compose exec php composer install &&
docker-compose exec php yarn install &&
docker-compose exec php php bin/console doctrine:database:drop --force &&
docker-compose exec php php bin/console doctrine:database:create &&
docker-compose exec php php bin/console doctrine:schema:update --force &&
docker-compose exec php php bin/console doctrine:fixtures:load &&
docker-compose exec php yarn run encore dev --watch

docker-compose exec php php bin/console app:add-user yournetid developer

#to run tests:
docker-compose exec php php ./phpunit symfony

#to run individual test
docker-compose exec php php bin/phpunit tests/Controller/FileManagerControllerTest.php

#Note to self: run sudo docker-compose build on Linux. Make sure to start docker service.
#Change the entire folder to chmod 755.

#To install filemanager-bundle:
docker-compose exec php composer require artgris/filemanager-bundle

#Then add this in annotations.yaml
artgris_bundle_file_manager:
    resource: "@ArtgrisFileManagerBundle/Controller"
    type:     annotation
    prefix:   /manager

#prepare the web asset
docker-compose exec php php bin/console assets:install --symlink
#Add translator service in framework.yaml
translator: { fallbacks: [ "en" ] }

#The typical configuration for apache2 is the web folder. We need to change it to /public folder in this project. So add this to parameters in services.yaml
artgris_file_manager:
    web_dir: public                 # set your public Directory (not required, default value: web)
    conf:
        default:
            dir: "../public/uploads"

#Browse the /manager/?conf=default URL and you'll get access to your file manager

#remove the bundle. Need to clean up some configurations.
docker-compose exec php composer remove artgris/filemanager-bundle --update-with-dependencies

