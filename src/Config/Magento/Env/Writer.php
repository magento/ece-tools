<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento\Env;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * @inheritdoc
 */
class Writer implements WriterInterface
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param ReaderInterface $reader
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(
        ReaderInterface $reader,
        File $file,
        FileList $fileList
    ) {
        $this->reader = $reader;
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * @inheritdoc
     */
    public function create(array $config)
    {
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';

        $this->file->filePutContents($this->fileList->getEnv(), $updatedConfig);
    }

    /**
     * @inheritdoc
     */
    public function update(array $config)
    {
        $updatedConfig = array_replace_recursive($this->reader->read(), $config);

        $this->create($updatedConfig);
    }
}
