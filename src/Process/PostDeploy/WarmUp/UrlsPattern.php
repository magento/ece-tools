<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\PostDeploy\WarmUp;

use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Fetch urls from config:show:urls command and filtering the by given pattern
 */
class UrlsPattern
{
    const ENTITY_CATEGORY = 'category';
    const ENTITY_CMS_PAGE = 'cms-page';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
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

            list($entity, $pattern, $storeId) = explode(':', $warmUpPattern);

            $command = sprintf('config:show:urls --entity-type="%s"', $entity);
            if ($storeId && $storeId !== '*') {
                $command .= sprintf(' --store-id="%s"', $storeId);
            }

            $process = $this->shell->execute($command);

            $urls = json_decode($process->getOutput());

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error(sprintf(
                    'Can\'t parse result from command %s: %s',
                    $command,
                    json_last_error_msg()
                ));
                return [];
            }

            if ($pattern === '*') {
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
            '/^(%s|%s):.{1,}:(\w+|\*)/',
            self::ENTITY_CATEGORY,
            self::ENTITY_CMS_PAGE
        );

        return (bool)preg_match($regex, $warmUpPattern);
    }
}
