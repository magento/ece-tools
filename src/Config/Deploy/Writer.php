<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Deploy;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;

class Writer
{
    /**
     * @var Reader
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
     * @param Reader $reader
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(
        Reader $reader,
        File $file,
        FileList $fileList
    ) {
        $this->reader = $reader;
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * Writes given configuration to file.
     *
     * @param array $config
     */
    public function write(array $config)
    {
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';

        $this->file->filePutContents($this->fileList->getEnv(), $updatedConfig);
    }

    /**
     * Updates existence configuration.
     *
     * @param array $config
     */
    public function update(array $config)
    {
        $updatedConfig = array_replace_recursive($this->reader->read(), $config);

        $this->write($updatedConfig);
    }
}
