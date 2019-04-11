<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Process\PostDeploy\WarmUp\UrlRewriteTable;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUp implements ProcessInterface
{
    /**
     * @var PostDeployInterface
     */
    private $postDeploy;

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Variable for caching base urls hosts from config table
     *
     * @var string
     */
    private $baseHosts;

    /**
     * @var UrlRewriteTable
     */
    private $urlRewriteTable;

    /**
     * @param PostDeployInterface $postDeploy
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     * @param UrlRewriteTable $urlRewriteTable
     */
    public function __construct(
        PostDeployInterface $postDeploy,
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        UrlManager $urlManager,
        LoggerInterface $logger,
        UrlRewriteTable $urlRewriteTable
    ) {
        $this->postDeploy = $postDeploy;
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
        $this->urlRewriteTable = $urlRewriteTable;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function execute()
    {
        $client = $this->clientFactory->create();
        $promises = [];

        try {
            $this->logger->info('Starting page warming up');

            foreach ($this->getUrlsForWarmUp() as $url) {
                $request = $this->requestFactory->create('GET', $url);

                $promises[] = $client->sendAsync($request)->then(function () use ($url) {
                    $this->logger->info('Warmed up page: ' . $url);
                }, function (RequestException $exception) use ($url) {
                    $context = [];
                    if ($exception->getResponse()) {
                        $context = [
                            'error' => $exception->getResponse()->getReasonPhrase(),
                            'code' => $exception->getResponse()->getStatusCode(),
                        ];
                    } else if ($exception->getHandlerContext()) {
                        $context = [
                            'error' => $exception->getHandlerContext()['error'] ?? '',
                            'errno' => $exception->getHandlerContext()['errno'] ?? '',
                            'total_time' => $exception->getHandlerContext()['total_time'] ?? ''
                        ];
                    }

                    $this->logger->error('Warming up failed: ' . $url, $context);
                });
            }

            \GuzzleHttp\Promise\unwrap($promises);
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns list of URLs which should be warm up.
     *
     * @return array
     */
    private function getUrlsForWarmUp(): array
    {
        $pages = $this->postDeploy->get(PostDeployInterface::VAR_WARM_UP_PAGES);
        $baseUrl = rtrim($this->urlManager->getBaseUrl(), '/');
        $urls = [];

        $urlPattern = sprintf(
            '/^(%s|%s):(\d+|\*):.{1,}/',
            UrlRewriteTable::ENTITY_CATEGORY,
            UrlRewriteTable::ENTITY_CMS_PAGE
        );

        foreach ($pages as $page) {
            if (preg_match($urlPattern, $page)) {
                $patternUrls = $this->getUrlsByPattern($page);
                $this->logger->info(sprintf('Found %d urls for pattern "%s"', count($patternUrls), $page));
                $urls = array_merge($urls, $patternUrls);
            } else if (strpos($page, 'http') === 0) {
                if (!$this->isRelatedDomain($page)) {
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
            } else {
                $urls[] = $baseUrl . '/' . $page;
            }
        }

        return $urls;
    }

    /**
     * Checks that host from $url is using in current Magento installation
     *
     * @param string $url
     * @return bool
     */
    private function isRelatedDomain(string $url): bool
    {
        if ($this->baseHosts === null) {
            $this->baseHosts = array_map(
                function ($baseHostUrl) {
                    return parse_url($baseHostUrl, PHP_URL_HOST);
                },
                $this->urlManager->getBaseUrls()
            );
        }

        return in_array(parse_url($url, PHP_URL_HOST), $this->baseHosts);
    }

    private function getUrlsByPattern($pattern)
    {
        list($entity, $storeId, $pattern) = explode(':', $pattern);
        $patternUrls = $this->urlRewriteTable->getUrls(
            $entity,
            $pattern,
            $storeId == '*' ? null : (int)$storeId
        );

        return $patternUrls;
    }
}
