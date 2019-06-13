<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use GuzzleHttp\Exception\RequestException;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Http\PoolFactory;
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
     * @var PoolFactory
     */
    private $poolFactory;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PostDeployInterface $postDeploy
     * @param PoolFactory $poolFactory
     * @param UrlManager $urlManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        PostDeployInterface $postDeploy,
        PoolFactory $poolFactory,
        UrlManager $urlManager,
        LoggerInterface $logger
    ) {
        $this->postDeploy = $postDeploy;
        $this->poolFactory = $poolFactory;
        $this->urlManager = $urlManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute()
    {
        $urls = $this->getUrls();

        $fulfilled = function ($response, $index) use ($urls) {
            $this->logger->info('Warmed up page: ' . $urls[$index]);
        };

        $rejected = function (RequestException $exception, $index) use ($urls) {
            $context = [];

            if ($exception->getResponse()) {
                $context = [
                    'error' => $exception->getResponse()->getReasonPhrase(),
                    'code' => $exception->getResponse()->getStatusCode(),
                ];
            }

            $this->logger->error('Warming up failed: ' . $urls[$index], $context);
        };

        try {
            $pool = $this->poolFactory->create($urls, compact('fulfilled', 'rejected'));

            $pool->promise()->wait();
        } catch (\Throwable $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Returns list of URLs which should be warm up.
     *
     * @return array
     */
    private function getUrls(): array
    {
        return array_filter(
            $this->postDeploy->get(PostDeployInterface::VAR_WARM_UP_PAGES),
            function ($page) {
                if (!$this->urlManager->isUrlValid($page)) {
                    $this->logger->warning(
                        sprintf(
                            'Page "%s" can\'t be warmed-up because such domain ' .
                            'is not registered in current Magento installation',
                            $page
                        )
                    );

                    return false;
                }

                return true;
            }
        );
    }
}
