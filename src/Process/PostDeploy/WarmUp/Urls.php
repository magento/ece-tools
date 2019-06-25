<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy\WarmUp;

use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * Returns list of urls for warm up
 */
class Urls
{
    /**
     * @var PostDeployInterface
     */
    private $postDeploy;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlsPattern
     */
    private $urlsPattern;

    /**
     * @param PostDeployInterface $postDeploy
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     * @param UrlsPattern $urlsPattern
     */
    public function __construct(
        PostDeployInterface $postDeploy,
        UrlManager $urlManager,
        LoggerInterface $logger,
        UrlsPattern $urlsPattern
    ) {
        $this->postDeploy = $postDeploy;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
        $this->urlsPattern = $urlsPattern;
    }

    /**
     * Returns list of URLs which should be warm up.
     *
     * @return array
     */
    public function getAll(): array
    {
        $pages = $this->postDeploy->get(PostDeployInterface::VAR_WARM_UP_PAGES);
        $baseUrl = rtrim($this->urlManager->getBaseUrl(), '/');
        $urls = [];

        foreach ($pages as $page) {
            if ($this->urlsPattern->isValid($page)) {
                $patternUrls = $this->urlsPattern->get($page);
                $this->logger->info(sprintf('Found %d urls for pattern "%s"', count($patternUrls), $page));
                $urls = array_merge($urls, $patternUrls);
            } else if (strpos($page, 'http') === 0) {
                if (!$this->urlManager->isRelatedDomain($page)) {
                    $this->logger->error(
                        sprintf(
                            'Page "%s" can\'t be warmed-up because such domain ' .
                            'is not registered in current Magento installation',
                            $page
                        )
                    );
                } else {
                    $urls[] = $page;
                }
            } else if (strpos($page, ':') !== false) {
                $this->logger->error(sprintf('Page "%s" isn\'t correct and can\'t be warmed-up', $page));
            } else {
                $urls[] = $baseUrl . '/' . $page;
            }
        }

        return $urls;
    }
}
