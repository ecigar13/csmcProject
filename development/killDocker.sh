sudo netstat -anpt | awk '/docker*/{print $7;}'|tr -dc "[0-9]\n" | sudo xargs kill
pkill docker-

