<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Marshalls required files.
 *
 * {@inheritdoc}
 */
class MarshallFiles implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShellInterface $shell
     * @param File $file
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        LoggerInterface $logger
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->shell->execute('rm -rf generated/code/*');
        $this->shell->execute('rm -rf generated/metadata/*');
        $this->shell->execute('rm -rf var/cache');

        try {
            $this->file->copy(
                MAGENTO_ROOT . 'app/etc/di.xml',
                MAGENTO_ROOT . 'app/di.xml'
            );

            $enterpriseFolder = MAGENTO_ROOT . 'app/enterprise';
            if (!$this->file->isExists($enterpriseFolder)) {
                $this->file->createDirectory($enterpriseFolder, 0777);
            }

            $this->file->copy(
                MAGENTO_ROOT . 'app/etc/enterprise/di.xml',
                MAGENTO_ROOT . 'app/enterprise/di.xml'
            );
        } catch (FileSystemException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
