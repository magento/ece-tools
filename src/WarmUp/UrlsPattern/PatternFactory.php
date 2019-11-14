<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\WarmUp\UrlsPattern;

/**
 * Creates instances of PatternInterface
 */
class PatternFactory
{
    /**
     * Mapping between entity types and represented classes.
     *
     * @var array
     */
    private static $classMap = [
        UrlsPattern::ENTITY_STORE_PAGE => StorePage::class,
        UrlsPattern::ENTITY_PRODUCT => Product::class,
        UrlsPattern::ENTITY_CATEGORY => CategoryCmsPage::class,
        UrlsPattern::ENTITY_CMS_PAGE => CategoryCmsPage::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $name
     * @return PatternInterface
     * @throws ConfigurationMismatchException
     */
    public function create(string $name): PatternInterface
    {
        if (!isset(self::$classMap[$name])) {
            throw new ConfigurationMismatchException(sprintf('Class %s is not registered', $name));
        }

        return $this->container->create(self::$classMap[$name]);
    }
}
