#!/bin/bash
echo "Please verify that the database is done initializing ( it takes a few minutes). You can verify by running ./db.sh  and  then show tables;"
while [ true ] ; do
read -t 3 -n 1
if [ $? = 0 ] ; then
  #docker exec  ece-tools_fpm_1  php -d memory_limit=8G  /usr/local/bin/composer require "magento/magento-cloud-metapackage":2.3.5
  docker exec  ece-tools_fpm_1  composer install
  #docker exec  ece-tools_fpm_1  composer update
  docker exec  ece-tools_fpm_1  php -dmemory_limit=20G  bin/magento   app:config:import
  docker exec  ece-tools_fpm_1  php -dmemory_limit=2G  bin/magento set:upg
  docker exec  ece-tools_fpm_1  php -dmemory_limit=2G  bin/magento se:di:c

  #docker exec ece-tools_fpm_1 bin/magento app:config:import

  #docker exec -d ece-tools_fpm_1 bin/magento setup:upgrade

  #docker exec ece-tools_fpm_1 php -dmemory_limit=8G bin/magento setup:di:compile

  cd ../

  sudo chmod 777 -R *

  cd ece-tools/

  docker exec -d ece-tools_varnish_1 varnishadm 'ban req.url ~ .'
  docker exec -d ece-tools_fpm_1 rm -rf var/cache/* var/page_cache/* generated/*
  docker exec -d ece-tools_redis_1 redis-cli flushall

  if which xdg-open > /dev/null
then
  xdg-open 'http://local.pictureframes.com'
elif which gnome-open > /dev/null
then
  gnome-open URL
fi


  echo 'if you ran into any issues here run db.sh to and  check if all the database tables have been created already then try running this again'
  echo  'verify that all the db,web, tls, and varnish containers are up'
  docker ps
  exit
else
echo "press any key to continue "
fi
done
