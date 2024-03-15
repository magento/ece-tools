<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Urls;

use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;

/**
 * Updates the base_url configuration in the <magento_root>/app/etc/env.php file
 *
 * {@inheritdoc}
 */
class Environment implements StepInterface
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
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var array
     */
    private $envConfig;

    /**
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param ReaderInterface $reader
     * @param WriterInterface $writer
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        ReaderInterface $reader,
        WriterInterface $writer
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
                if (!is_string($value)) {
                    return;
                }
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
