<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\PostDeploy\WarmUp;

use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Psr\Log\LoggerInterface;

/**
 * Fetch urls from config:show:urls command and filtering the by given pattern
 */
class UrlsPattern
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_CMS_PAGE = 'cms-page';
    const ENTITY_PRODUCT = 'product';

    private const PATTERN_DELIMITER = '|';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @param LoggerInterface $logger
     * @param ShellFactory $shellFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ShellFactory $shellFactory
    ) {
        $this->logger = $logger;
        $this->magentoShell = $shellFactory->createMagento();
    }

    /**
     * Fetch urls from config:show:urls command and filtering the by given pattern
     *
     * @param string $warmUpPattern
     * @return array
     */
    public function get(string $warmUpPattern): array
    {
        try {
            if (!$this->isValid($warmUpPattern)) {
                $this->logger->error(sprintf('Warm-up pattern "%s" isn\'t valid.', $warmUpPattern));
                return [];
            }

            list($entity, $pattern, $storeIds) = explode(':', $warmUpPattern);

            $command = 'config:show:urls';

            $process = $this->magentoShell->execute(
                $command,
                $this->buildCommandArguments($entity, $pattern, $storeIds)
            );

            $urls = json_decode($process->getOutput(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error(sprintf(
                    'Can\'t parse result from command %s: %s',
                    $command,
                    json_last_error_msg()
                ));
                return [];
            }

            if ($pattern === '*' || $entity == self::ENTITY_PRODUCT) {
                return $urls;
            }

            $urls = array_filter($urls, function ($url) use ($pattern) {
                return @preg_match($pattern, '') !== false ?
                    preg_match($pattern, $url) :
                    trim($pattern, '/') === trim(parse_url($url, PHP_URL_PATH), '/');
            });

            return $urls;
        } catch (ShellException $e) {
            $this->logger->error('Command execution failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Checks if pattern for warm up is configured properly.
     *
     * @param string $warmUpPattern
     * @return bool
     */
    public function isValid(string $warmUpPattern): bool
    {
        $regex = sprintf(
            '/^(%s|%s|%s):.{1,}:(\w+|\*)/',
            self::ENTITY_CATEGORY,
            self::ENTITY_CMS_PAGE,
            self::ENTITY_PRODUCT
        );

        return (bool)preg_match($regex, $warmUpPattern);
    }

    /**
     * @param string $entity
     * @param string $pattern
     * @param string $storeIds
     * @return array
     */
    private function buildCommandArguments(string $entity, string $pattern, string $storeIds): array
    {
        $commandArguments = [sprintf('--entity-type=%s', $entity)];
        if ($storeIds && $storeIds !== '*') {
            foreach (explode('|', $storeIds) as $storeId) {
                $commandArguments[] = sprintf('--store-id=%s', $storeId);
            }
        }

        if ($entity === self::ENTITY_PRODUCT) {
            if ($pattern === '*') {
                $this->logger->info('In case when product SKUs weren\'t provided product limits set to 100');
                return $commandArguments;
            }

            foreach (explode(self::PATTERN_DELIMITER, $pattern) as $productSku) {
                $commandArguments[] = sprintf('--product-sku="%s"', $productSku);
            }
        }

        return $commandArguments;
    }
}
