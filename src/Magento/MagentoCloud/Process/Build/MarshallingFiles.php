<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;

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
     * @var File
     */
    private $file;

    /**
     * @param ShellInterface $shell
     * @param File $file
     */
    public function __construct(
        ShellInterface $shell,
        File $file
    ) {
        $this->shell = $shell;
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
    }
}
