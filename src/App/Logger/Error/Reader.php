<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Error;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * @inheritDoc
 */
class Reader implements ReaderInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param FileList $fileList
     */
    public function __construct(FileList $fileList)
    {
        $this->fileList = $fileList;
    }

    /**
     * @inheritDoc
     */
    public function read(): array
    {
        try {
            $handle = @fopen($this->fileList->getCloudErrorLog(), 'r');
            if (!$handle) {
                return [];
            }
            $logs = [];
            while (($line = fgets($handle)) !== false) {
                $error = json_decode($line, true);
                $logs[$error['errorCode']] = $error;
            }
            fclose($handle);

            return $logs;
        } catch (UndefinedPackageException $e) {
            return [];
        }
    }
}
