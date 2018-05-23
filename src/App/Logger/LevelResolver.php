<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\Config\StageConfigInterface;
use Monolog\Logger;

/**
 * Resolves string level to int level from Logger.
 */
class LevelResolver
{
    /**
     * @var StageConfigInterface
     */
    private $stageConfig;

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

    public function __construct(StageConfigInterface $stageConfig)
    {
        $this->stageConfig = $stageConfig;
    }

    /**
     * @param string $level
     * @return int
     */
    public function resolve(string $level): int
    {
        $levelOverride = $this->stageConfig->get(StageConfigInterface::VAR_MIN_LOGGING_LEVEL);

        if ($levelOverride) {
            return $this->mapLevels[strtolower($levelOverride)] ?? Logger::NOTICE;
        }

        return $this->mapLevels[strtolower($level)] ?? Logger::NOTICE;
    }
}
