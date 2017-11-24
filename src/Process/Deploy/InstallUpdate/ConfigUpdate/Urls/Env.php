<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy\Reader as EnvConfigReader;

/**
 * @inheritdoc
 */
class Env implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var EnvConfigReader
     */
    private $envConfigReader;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param EnvConfigReader $envConfigReader
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(
        Environment $environment,
        LoggerInterface $logger,
        UrlManager $urlManager,
        EnvConfigReader $envConfigReader,
        File $file,
        FileList $fileList
    ) {
        $this->environment = $environment;
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->envConfigReader = $envConfigReader;
        $this->file = $file;
        $this->fileList = $fileList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating secure and unsecure URLs in app/etc/env.php file');

        $configBaseUrls = $this->getConfigBaseUrls();
        $envConfigPath = $this->fileList->getEnv();
        $envConfContent = $this->file->fileGetContents($envConfigPath);
        $configUrlsChanges = false;

        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrl) {
            if (empty($actualUrl['']) || empty($configBaseUrls[$typeUrl])) {
                continue;
            }

            $baseUrlHost = parse_url($configBaseUrls[$typeUrl])['host'];
            $actualUrlHost = parse_url($actualUrl[''])['host'];

            if ($baseUrlHost === $actualUrlHost) {
                continue;
            }

            $envConfContent = str_replace($baseUrlHost, $actualUrlHost, $envConfContent, $replaceCount);

            if (0 === $replaceCount) {
                continue;
            }

            $configUrlsChanges = true;

            $this->logger->info(sprintf('Replace host: [%s] => [%s]', $baseUrlHost, $actualUrlHost));

            $replaceCount = null;
        }

        if (true === $configUrlsChanges) {
            $this->logger->info(sprintf('Write the updating configuration in %s file', $envConfigPath));
            $this->file->filePutContents($envConfigPath, $envConfContent);
        }
    }

    /**
     * Returns the base_url configuration with <magento_root>/app/etc/env.php file.
     *
     * ```
     * array(
     *     'secure' => 'https://example.com',
     *     'unsecure' => 'http://example.com',
     * )
     * ```
     * @return array
     */
    private function getConfigBaseUrls()
    {
        $envConfig = $this->envConfigReader->read();
        $result = [];
        foreach (['secure', 'unsecure'] as $type) {
            $url = $envConfig['system']['default']['web'][$type]['base_url'] ?? null;
            if (null !== $url) {
                $result[$type] = $url;
            }
        }

        return $result;
    }
}
