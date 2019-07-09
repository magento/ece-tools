<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Config\Dist;

use Magento\MagentoCloud\Docker\Config\Relationship;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\PhpFormatter;

/**
 * Creates docker/config.php.dist file
 */
class Generator
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
     * @var PhpFormatter
     */
    private $phpFormatter;

    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @var array
     */
    private static $baseConfig = [
        'MAGENTO_CLOUD_ROUTES' => [
            'http://magento2.docker/' => [
                'type' => 'upstream',
                'original_url' => 'http://{default}'
            ],
            'https://magento2.docker/' => [
                'type' => 'upstream',
                'original_url' => 'https://{default}'
            ],
        ],
        'MAGENTO_CLOUD_VARIABLES' => [
            'ADMIN_EMAIL' => 'admin@example.com',
            'ADMIN_PASSWORD' => '123123q',
            'ADMIN_URL' => 'admin'
        ],
    ];

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     * @param Relationship $relationship
     * @param PhpFormatter $phpFormatter
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file,
        Relationship $relationship,
        PhpFormatter $phpFormatter
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->phpFormatter = $phpFormatter;
        $this->relationship = $relationship;
    }

    /**
     * Create docker/config.php.dist file
     * generate MAGENTO_CLOUD_RELATIONSHIPS according to services enablements.
     *
     * @throws FileSystemException if file can't be saved
     * @throws ConfigurationMismatchException if can't obtain relationships
     */
    public function generate()
    {
        $configPath = $this->directoryList->getDockerRoot() . '/config.php.dist';

        $config = array_merge(
            ['MAGENTO_CLOUD_RELATIONSHIPS' => $this->relationship->get()],
            self::$baseConfig
        );

        $this->saveConfig($configPath, $config);
    }

    /**
     * Formats and save configuration to file.
     *
     * @param string $filePath
     * @param array $config
     * @throws FileSystemException
     */
    private function saveConfig(string $filePath, array $config)
    {
        $result = "<?php\n\nreturn [";
        foreach ($config as $key => $value) {
            $result .= "\n    '{$key}' => ";
            $result .= 'base64_encode(json_encode(' . $this->phpFormatter->varExportShort($value, 2) . ')),';
        }
        $result .= "\n];\n";

        $this->file->filePutContents($filePath, $result);
    }
}
