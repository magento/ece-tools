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
class UpdateUrlsInEnvConfig implements ProcessInterface
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

        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrl) {
            if (isset($actualUrl['']) && isset($configBaseUrls[$typeUrl])) {
                $baseUrlHost = parse_url($configBaseUrls[$typeUrl])['host'];
                $actualUrlHost = parse_url($actualUrl[''])['host'];
                if ($baseUrlHost !== $actualUrlHost) {
                    $this->updateUrl($baseUrlHost, $actualUrlHost);
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getConfigBaseUrls()
    {
        $envConfig = $this->envConfigReader->read();
        $result = [];
        foreach (['secure', 'unsecure'] as $type) {
            $url = $envConfig['system']['default']['web'][$type]['base_url'] ?? null;
            if(null !== $url){
                $result[$type] = $url;
            }
        }

        return $result;
    }

    /**
     * @param $baseHost
     * @param $actualHost
     */
    private function updateUrl($baseHost, $actualHost)
    {
        $envConfigPath = $this->fileList->getEnv();
        $envConfContent = $this->file->fileGetContents($envConfigPath);
        $this->file->filePutContents($envConfigPath, str_replace($baseHost, $actualHost, $envConfContent));
    }
}