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
use Magento\MagentoCloud\Process\ProcessComposite;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @throws BindingResolutionException
     */
    public function configure()
    {
        $data = $this->encoder->decode(
            $this->file->fileGetContents($this->fileList->getDIConfig()),
            XmlEncoder::FORMAT,
            [XmlEncoder::AS_COLLECTION => true]
        );

        $this->configurePreferences($data);
        $this->configureTypes($data);
    }

    /**
     * @param array $data
     *
     * @throws BindingResolutionException
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
            } elseif (isset($type['composite'][0]['item'])) {
                $processedItems = [];

                foreach ($type['composite'][0]['item'] as $item) {
                    $processedItems[] = $this->container->make($item);
                }

                $this->container->when($type['@name'])
                    ->needs(ProcessInterface::class)
                    ->give(function () use ($processedItems) {
                        return $this->container->make(ProcessComposite::class, [
                            'processes' => $processedItems
                        ]);
                    });
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
