<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\WarmUp\UrlsPattern;

use Magento\MagentoCloud\WarmUp\UrlsPattern;

/**
 * Processes cms-page and category pattern types.
 */
class CategoryCmsPage implements PatternInterface
{
    /**
     * @var ConfigShowUrlCommand
     */
    private $configShowUrlCommand;

    /**
     * @var CommandArgumentBuilder
     */
    private $commandArgumentBuilder;

    /**
     * @param ConfigShowUrlCommand $configShowUrlCommand
     * @param CommandArgumentBuilder $commandArgumentBuilder
     */
    public function __construct(
        ConfigShowUrlCommand $configShowUrlCommand,
        CommandArgumentBuilder $commandArgumentBuilder
    ) {
        $this->configShowUrlCommand = $configShowUrlCommand;
        $this->commandArgumentBuilder = $commandArgumentBuilder;
    }

    /**
     * @param string $pattern can accept the next value types:
     * - "*" - all pages
     * - "{regular expression}" - pages will be filtered by this pattern
     * - "/path/to/page" - exact page match
     *
     * {@inheritDoc}
     */
    public function getUrls(string $entity, string $pattern, string $storeIds): array
    {
        $arguments = $this->commandArgumentBuilder->generate($entity, $storeIds);
        $urls = $this->configShowUrlCommand->execute($arguments);

        if ($pattern === UrlsPattern::PATTERN_ALL) {
            return $urls;
        }

        $urls = array_filter($urls, function ($url) use ($pattern) {
            $urlPath = parse_url($url, PHP_URL_PATH);

            return @preg_match($pattern, '') !== false ?
                preg_match($pattern, $urlPath) :
                trim($pattern, '/') === trim($urlPath, '/');
        });

        return $urls;
    }
}
