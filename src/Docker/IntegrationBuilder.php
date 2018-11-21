<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\Service\ServiceFactory;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * Docker integration test builder.
 *
 * @codeCoverageIgnore
 */
class IntegrationBuilder implements BuilderInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @param FileList $fileList
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(FileList $fileList, ServiceFactory $serviceFactory)
    {
        $this->fileList = $fileList;
        $this->factory = $serviceFactory;
    }

    /**
     * @inheritdoc
     */
    public function build(Repository $repository): array
    {
        return [
            'version' => '2',
            'services' => [
                'cli' => $this->factory->create(
                    ServiceFactory::SERVICE_CLI,
                    $repository->get(self::PHP_VERSION),
                    [
                        'links' => [
                            'db',
                        ],
                        'volumes' => [
                            '~/.composer/cache:/root/.composer/cache',
                        ],
                        'volumes_from' => [
                            'appdata',
                        ],
                        'env_file' => [
                            './docker/global.env',
                            './docker/composer.env',
                        ]
                    ]
                ),
                'fpm' => $this->factory->create(
                    ServiceFactory::SERVICE_FPM,
                    $repository->get(self::PHP_VERSION),
                    [
                        'ports' => [
                            9000,
                        ],
                        'links' => [
                            'db',
                        ],
                        'volumes_from' => [
                            'appdata',
                        ],
                        'env_file' => [
                            './docker/global.env',
                            './docker/composer.env',
                        ],
                    ]
                ),
                'db' => $this->factory->create(
                    ServiceFactory::SERVICE_DB,
                    $repository->get(self::DB_VERSION),
                    [
                        'ports' => [
                            3306,
                        ],
                        'volumes' => [
                            '/var/lib/mysql',
                        ],
                        'environment' => [
                            'MYSQL_ROOT_PASSWORD=magento2',
                            'MYSQL_DATABASE=magento2',
                            'MYSQL_USER=magento2',
                            'MYSQL_PASSWORD=magento2',
                        ],
                    ]
                ),
                'web' => $this->factory->create(
                    ServiceFactory::SERVICE_NGINX,
                    $repository->get(self::NGINX_VERSION),
                    [
                        'ports' => [
                            '8080:80',
                        ],
                        'links' => [
                            'fpm',
                            'db',
                        ],
                        'volumes_from' => [
                            'appdata',
                        ],
                        'env_file' => [
                            './docker/global.env',
                            './docker/composer.env',
                        ],
                    ]
                ),
                'appdata' => [
                    'image' => 'tianon/true',
                    'volumes' => [
                        '.:/var/www/ece-tools',
                        '/var/www/magento',
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getConfigPath(): string
    {
        return $this->fileList->getToolsDockerCompose();
    }
}
