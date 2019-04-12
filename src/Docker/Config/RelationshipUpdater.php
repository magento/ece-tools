<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Util\CloudVariableEncoder;
use Magento\MagentoCloud\Util\PhpFormatter;

/**
 * Updates MAGENTO_CLOUD_RELATIONSHIPS section in docker config.php.* files according to services enablements.
 */
class RelationshipUpdater
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var CloudVariableEncoder
     */
    private $encoder;

    /**
     * @var PhpFormatter
     */
    private $phpFormatter;

    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param CloudVariableEncoder $encoder
     * @param Relationship $relationship
     * @param PhpFormatter $phpFormatter
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        CloudVariableEncoder $encoder,
        Relationship $relationship,
        PhpFormatter $phpFormatter
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->encoder = $encoder;
        $this->phpFormatter = $phpFormatter;
        $this->relationship = $relationship;
    }

    /**
     * Updates MAGENTO_CLOUD_RELATIONSHIPS section in docker config.php.* files according to services enablements.
     */
    public function update()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();

        $files = [
            $magentoRoot . '/docker/config.php',
            $magentoRoot . '/docker/config.php.dist',
        ];

        foreach ($files as $filePath) {
            if (!$this->file->isExists($filePath)) {
                continue;
            }

            $fileConfig = $this->getFileConfig($filePath);

            $fileConfig['MAGENTO_CLOUD_RELATIONSHIPS'] = $this->relationship->get();

            $this->saveConfig($filePath, $fileConfig);
        }
    }

    /**
     * Read config from file and decode array elements values.
     *
     * @param $filePath
     * @return mixed
     */
    private function getFileConfig($filePath)
    {
        $fileConfig = $this->file->requireFile($filePath);

        foreach ($fileConfig as $service => $serviceConfig) {
            $fileConfig[$service] = $this->encoder->decode($serviceConfig);
        }

        return $fileConfig;
    }

    /**
     * Formats and save configuration to file.
     *
     * @param string $filePath
     * @param array $fileConfig
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    private function saveConfig(string $filePath, array $fileConfig)
    {
        $result = "<?php\n\nreturn [";
        foreach ($fileConfig as $key => $value) {
            $result .= "\n    '{$key}' => ";
            $result .= 'base64_encode(json_encode(' . $this->phpFormatter->varExportShort($value, 2) .')),';
        }
        $result .= "\n];\n";

        $this->file->filePutContents($filePath, $result);
    }
}
