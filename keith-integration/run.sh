docker-compose build
docker-compose up -d

echo "This should now run on port 8080. Database access on port 8081."
echo "Add user with: localhost:8080?_switch_user=mxm000000"
echo "docker-compose exec php php bin/console app:add-user yournetid developer"

#Note to self: run sudo docker-compose build on Linux. Make sure to start docker service.
#Change the entire folder to chmod 755.
