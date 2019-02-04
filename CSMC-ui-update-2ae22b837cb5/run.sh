#!/bin/bash

sudo docker-compose exec php yarn run encore dev --watch
sudo docker-compose build
sudo docker-compose up -d

echo "This should now run on port 8080. Database access on port 8081."
