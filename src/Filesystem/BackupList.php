<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Class contains the list of files for backup
 */
class BackupList
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * Suffix for backup files
     */
    const BACKUP_SUFFIX = '.bak';

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param FileList $fileList
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(FileList $fileList, MagentoVersion $magentoVersion)
    {
        $this->fileList = $fileList;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Returns a list of files for backup
     *
     * @return array
     */
    public function getList(): array
    {
        $fileList = [
            'app/etc/env.php' => $this->fileList->getEnv(),
            'app/etc/config.php' => $this->fileList->getConfig(),
        ];

        if (!$this->magentoVersion->isGreaterOrEqual('2.2')) {
            $fileList['app/etc/config.local.php'] = $this->fileList->getConfigLocal();
        }

        return $fileList;
    }
}
