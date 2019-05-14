<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\ClientFactory;
use Magento\MagentoCloud\Http\RequestFactory;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class WarmUp implements ProcessInterface
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_CMS_PAGE = 'cms-page';

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
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param PostDeployInterface $postDeploy
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        PostDeployInterface $postDeploy,
        ClientFactory $clientFactory,
        RequestFactory $requestFactory,
        UrlManager $urlManager,
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->postDeploy = $postDeploy;
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
        $this->shell = $shell;
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
            self::ENTITY_CATEGORY,
            self::ENTITY_CMS_PAGE
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

    /**
     * @param string $warmUpPattern
     * @return array
     */
    private function getUrlsByPattern(string $warmUpPattern): array
    {
        try {
            list($entity, $storeId, $pattern) = explode(':', $warmUpPattern);

            $command = sprintf('config:get:rewrite-urls --entity-type="%s"', $entity);
            if ($storeId && $storeId !== '*') {
                $command .= sprintf(' --store_id="%s"', $storeId);
            }

            $result = $this->shell->execute($command);

            $urls = json_decode($result[0]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Can\'t parse result from config:get:rewrite-urls: ' . json_last_error_msg());
                return [];
            }

            if ($pattern === '*') {
                return $urls;
            }

            $pattern = '/' . preg_quote($pattern) . '/';
            $urls = array_filter($urls, function($url) use ($pattern) {
                return preg_match($pattern, $url);
            });

            return $urls;

        } catch (ShellException $e) {
            $this->logger->error('Can\'t get result from config:get:rewrite-urls: ' . $e->getMessage());
            return [];
        }

    }
}
