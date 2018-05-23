<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\Config\Environment;
use Monolog\Logger;

/**
 * Resolves string level to int level from Logger.
 */
class LevelResolver
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $mapLevels = [
        'debug' => Logger::DEBUG,
        'info' => Logger::INFO,
        'notice' => Logger::NOTICE,
        'warning' => Logger::WARNING,
        'error' => Logger::ERROR,
        'critical' => Logger::CRITICAL,
        'alert' => Logger::ALERT,
        'emergency' => Logger::EMERGENCY,
    ];

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param string $level
     * @return int
     */
    public function resolve(string $level): int
    {
        $levelOverride = $this->environment->getMinLoggingLevel();

        if ($levelOverride) {
            return $this->mapLevels[strtolower($levelOverride)] ?? Logger::NOTICE;
        }

        return $this->mapLevels[strtolower($level)] ?? Logger::NOTICE;
    }
}
