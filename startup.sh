
docker-compose up --force-recreate -d &&
docker-compose exec php php bin/console run:websocket-server
