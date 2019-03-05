<?php

return [
    'PHP_MEMORY_LIMIT' => '2048M',
    'DEBUG' => 'false',
    'ENABLE_SENDMAIL' => 'false',
    'UPLOAD_MAX_FILESIZE' => '64M',
    'MAGENTO_ROOT' => '/var/www/magento',
    'PHP_ENABLE_XDEBUG' => 'false',
    'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker', #name of your server in IDE
    'XDEBUG_CONFIG' => 'remote_host=host.docker.internal', #docker host for developer environments, can be different for your OS
];
