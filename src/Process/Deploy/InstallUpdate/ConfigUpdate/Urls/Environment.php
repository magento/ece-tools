<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;

/**
 * Updates the base_url configuration in the <magento_root>/app/etc/env.php file
 *
 * {@inheritdoc}
 */
class Environment implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var array
     */
    private $envConfig;

    /**
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param Reader $reader
     * @param Writer $writer
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Reader $reader,
        Writer $writer
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating secure and unsecure URLs in app/etc/env.php file');

        $config = $this->getEnvConfig();
        $baseUrls = $this->getBaseUrls();

        $urlsChanged = false;
        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrls) {
            if (empty($actualUrls['']) || empty($baseUrls[$typeUrl])) {
                continue;
            }

            if (in_array($baseUrls[$typeUrl], ['{{base_url}}', '{{unsecure_base_url}}'])) {
                continue;
            }

            $baseHost = parse_url($baseUrls[$typeUrl])['host'];
            $actualHost = parse_url($actualUrls[''])['host'];

            if ($baseHost === $actualHost) {
                continue;
            }

            array_walk_recursive($config, function (&$value) use ($baseHost, $actualHost, &$replaced) {
                $value = str_replace($baseHost, $actualHost, $value, $replaceCount);
                if ($replaceCount) {
                    $replaced = true;
                }
            });

            if (!$replaced) {
                continue;
            }

            $replaced = null;
            $urlsChanged = true;

            $this->logger->info(sprintf('Host was replaced: [%s] => [%s]', $baseHost, $actualHost));
        }

        if ($urlsChanged) {
            $this->logger->info('Write the updating base URLs configuration in the app/etc/env.php file');
            $this->writer->create($config);
        }
    }

    /**
     * @return array
     */
    private function getBaseUrls(): array
    {
        $config = $this->getEnvConfig();
        $baseUrls = [];
        foreach (['secure', 'unsecure'] as $type) {
            $url = $config['system']['default']['web'][$type]['base_url'] ?? null;
            if (null !== $url) {
                $baseUrls[$type] = $url;
            }
        }

        return $baseUrls;
    }

    /**
     * @return array
     */
    private function getEnvConfig(): array
    {
        if (!$this->envConfig) {
            $this->envConfig = $this->reader->read();
        }
        return $this->envConfig;
    }
}
