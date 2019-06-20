<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Config;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Read and combine infrastructure configuration.
 */
class Reader implements ReaderInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param FileList $fileList
     * @param File $file
     */
    public function __construct(FileList $fileList, File $file)
    {
        $this->fileList = $fileList;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function read(): array
    {
        try {
            $appConfig = Yaml::parse(
                $this->file->fileGetContents($this->fileList->getAppConfig())
            );
            $servicesConfig = Yaml::parse(
                $this->file->fileGetContents($this->fileList->getServicesConfig())
            );
        } catch (\Exception $exception) {
            throw new FileSystemException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!isset($appConfig['type'])) {
            throw new FileSystemException('PHP version could not be parsed.');
        }

        if (!isset($appConfig['relationships'])) {
            throw new FileSystemException('Relationships could not be parsed.');
        }

        $config = [
            'type' => $appConfig['type'],
            'crons' => $appConfig['crons'] ?? [],
            'services' => [],
            'runtime' => [
                'extensions' => $appConfig['runtime']['extensions'] ?? [],
                'disabled_extensions' => $appConfig['runtime']['disabled_extensions'] ?? []
            ]
        ];

        foreach ($appConfig['relationships'] as $constraint) {
            list($name) = explode(':', $constraint);

            if (!isset($servicesConfig[$name]['type'])) {
                throw new FileSystemException(sprintf(
                    'Service with name "%s" could not be parsed',
                    $name
                ));
            }

            list($service, $version) = explode(':', $servicesConfig[$name]['type']);

            if (array_key_exists($service, $config['services'])) {
                throw new FileSystemException(sprintf(
                    'Only one instance of service "%s" supported',
                    $service
                ));
            }

            $config['services'][$service] = [
                'service' => $service,
                'version' => $version
            ];
        }

        return $config;
    }
}
