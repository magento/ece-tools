<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Container;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class Config
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var XmlEncoder
     */
    private $encoder;

    /**
     * @param Container $container
     * @param FileList $fileList
     * @param File $file
     * @throws BindingResolutionException
     */
    public function __construct(Container $container, FileList $fileList, File $file)
    {
        $this->container = $container;
        $this->fileList = $fileList;
        $this->file = $file;
        $this->encoder = $container->make(XmlEncoder::class);
    }

    /**
     * @throws FileSystemException
     */
    public function configure()
    {
        $data = $this->encoder->decode(
            $this->file->fileGetContents($this->fileList->getDIConfig()),
            XmlEncoder::FORMAT,
            [XmlEncoder::AS_COLLECTION => true]
        );

        $this->configureTypes($data);
        $this->configurePreferences($data);
    }

    /**
     * @param array $data
     */
    private function configureTypes(array $data)
    {
        foreach ($data['type'] ?? [] as $type) {
            if (isset($type['arguments'][0]['argument'])) {
                foreach ($type['arguments'][0]['argument'] as $argument) {
                    $this->container->when($type['@name'])
                        ->needs($argument['@name'])
                        ->give($argument['#']);
                }
            } else {
                $this->container->singleton($type['@name']);
            }

        }
    }

    /**
     * @param array $data
     */
    private function configurePreferences(array $data)
    {
        foreach ($data['preference'] ?? [] as $preference) {
            $this->container->singleton($preference['@for'], $preference['@type']);
        }
    }
}
