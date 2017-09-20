<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Deploy;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

class Writer
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @param Reader $reader
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Reader $reader,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->reader = $reader;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Writes given configuration to file.
     *
     * @param array $config
     */
    public function write(array $config)
    {
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';

        $this->file->filePutContents($this->reader->getPath(), $updatedConfig);
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
