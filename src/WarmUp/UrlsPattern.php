<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\WarmUp\UrlsPattern\PatternFactory;
use Psr\Log\LoggerInterface;

/**
 * Fetch urls from config:show:urls command and filtering the by given pattern
 */
class UrlsPattern
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_CMS_PAGE = 'cms-page';
    const ENTITY_PRODUCT = 'product';
    const ENTITY_STORE_PAGE = 'store-page';

    public const PATTERN_DELIMITER = '|';
    public const PATTERN_ALL = '*';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PatternFactory
     */
    private $patternFactory;

    /**
     * @param LoggerInterface $logger
     * @param PatternFactory $patternFactory
     */
    public function __construct(
        LoggerInterface $logger,
        PatternFactory $patternFactory
    ) {
        $this->logger = $logger;
        $this->patternFactory = $patternFactory;
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

            $urlsPattern = $this->patternFactory->create($entity);

            return array_unique($urlsPattern->getUrls($entity, $pattern, $storeIds));
        } catch (ShellException $e) {
            $this->logger->error('Command execution failed: ' . $e->getMessage());
        } catch (GenericException $e) {
            $this->logger->error($e->getMessage());
        }

        return [];
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
            '/^(%s|%s|%s|%s):.{1,}:(\w+|\*)/',
            self::ENTITY_CATEGORY,
            self::ENTITY_CMS_PAGE,
            self::ENTITY_PRODUCT,
            self::ENTITY_STORE_PAGE
        );

        return (bool)preg_match($regex, $warmUpPattern);
    }
}
