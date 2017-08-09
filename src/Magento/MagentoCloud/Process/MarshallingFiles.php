<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Marshalls required files.
 */
class MarshallingFiles implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param File $file
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        File $file
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->shell->execute('rm -rf generated/code/*');
        $this->shell->execute('rm -rf generated/metadata/*');
        $this->shell->execute('rm -rf var/cache');

        $this->file->copy(
            Environment::MAGENTO_ROOT . 'app/etc/di.xml',
            Environment::MAGENTO_ROOT . 'app/di.xml'
        );

        $enterpriseFolder = Environment::MAGENTO_ROOT . 'app/enterprise';
        if (!$this->file->isExists($enterpriseFolder)) {
            $this->file->createDirectory($enterpriseFolder, 0777);
        }

        $this->file->copy(
            Environment::MAGENTO_ROOT . 'app/etc/enterprise/di.xml',
            Environment::MAGENTO_ROOT . 'app/enterprise/di.xml'
        );

        $sampleDataDir = Environment::MAGENTO_ROOT . 'vendor/magento/sample-data-media';
        if ($this->file->isExists($sampleDataDir)) {
            $this->logger->log("Sample data media found. Marshalling to pub/media.");
            $destination = Environment::MAGENTO_ROOT . '/pub/media';
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sampleDataDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $destinationPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
                if (!$item->isDir()) {
                    $this->file->copy($item, $destinationPath);
                    continue;
                }

                if (!$this->file->isExists($destinationPath)) {
                    $this->file->createDirectory($destinationPath);
                }
            }
        }
    }

}
