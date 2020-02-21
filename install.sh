sh get-docker.sh
#composer create-project symfony/skeleton app
sudo apt-get install curl \
&& sudo apt-get remove docker-compose \
&& sudo rm /usr/local/bin/docker-compose \
&& sudo curl -L https://github.com/docker/compose/releases/download/1.25.4/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose \
&& sudo chmod +x /usr/local/bin/docker-compose \
&& docker-compose build --no-cache