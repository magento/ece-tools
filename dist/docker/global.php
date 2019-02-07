<?php

return [
    'PHP_MEMORY_LIMIT' => '2048M',
    'PHP_ENABLE_XDEBUG' => 'false',
    'DEBUG' => 'false',
    'ENABLE_SENDMAIL' => 'false',
    'UPLOAD_MAX_FILESIZE' => '64M',
    'MAGENTO_ROOT' => '/var/www/magento',
    'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
    'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
];
