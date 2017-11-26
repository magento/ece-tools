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
 * @inheritdoc
 */
class Env implements ProcessInterface
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

        $config = $this->reader->read();

        $baseUrls = [];
        foreach (['secure', 'unsecure'] as $type) {
            $url = $config['system']['default']['web'][$type]['base_url'] ?? null;
            if (null !== $url) {
                $baseUrls[$type] = $url;
            }
        }

        $urlsChanged = false;
        foreach ($this->urlManager->getUrls() as $typeUrl => $actualUrls) {
            if (empty($actualUrls['']) || empty($baseUrls[$typeUrl])) {
                continue;
            }

            $baseHost = parse_url($baseUrls[$typeUrl])['host'];
            $actualHost = parse_url($actualUrls[''])['host'];

            if ($baseHost === $actualHost) {
                continue;
            }

            array_walk_recursive($config, function (&$value) use ($baseHost, $actualHost, &$replaceCount) {
                $value = str_replace($baseHost, $actualHost, $value, $replaceCount);
            });

            if (!$replaceCount) {
                continue;
            }

            $urlsChanged = true;

            $this->logger->info(sprintf('Replace host: [%s] => [%s]', $baseHost, $actualHost));

            $replaceCount = null;
        }

        if ($urlsChanged) {
            $this->logger->info('Write the updating configuration in the app/etc/env.php file');
            $this->writer->write($config);
        }
    }
}
