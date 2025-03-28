<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\StaticContent\Deploy;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;

/**
 * Options for static deploy command in build process
 */
class Option implements OptionInterface
{
    /**
     * @var AdminDataInterface
     */
    private $adminData;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ThreadCountOptimizer
     */
    private $threadCountOptimizer;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param AdminDataInterface $adminData
     * @param ConnectionInterface $connection
     * @param MagentoVersion $magentoVersion
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        AdminDataInterface $adminData,
        ConnectionInterface $connection,
        MagentoVersion $magentoVersion,
        ThreadCountOptimizer $threadCountOptimizer,
        DeployInterface $stageConfig
    ) {
        $this->adminData = $adminData;
        $this->connection = $connection;
        $this->magentoVersion = $magentoVersion;
        $this->threadCountOptimizer = $threadCountOptimizer;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function getThreadCount(): int
    {
        return $this->threadCountOptimizer->optimize(
            $this->stageConfig->get(DeployInterface::VAR_SCD_THREADS),
            $this->stageConfig->get(DeployInterface::VAR_SCD_STRATEGY)
        );
    }

    /**
     * @inheritdoc
     */
    public function getStrategy(): string
    {
        return $this->stageConfig->get(DeployInterface::VAR_SCD_STRATEGY);
    }

    /**
     * @inheritdoc
     */
    public function isForce(): bool
    {
        return $this->magentoVersion->isGreaterOrEqual('2.2');
    }

    /**
     * Gets locales from DB which are set to stores and admin users.
     * Adds additional default 'en_US' locale to result, if it does't exist yet in defined list.
     *
     * @return array List of locales. Returns empty array in case when no locales are defined in DB
     * ```php
     * [
     *     'en_US',
     *     'fr_FR'
     * ]
     * ```
     */
    public function getLocales(): array
    {
        $output = $this->connection->select(
            sprintf(
                "SELECT `value` FROM `%s` WHERE `path`='general/locale/code' " .
                "UNION SELECT `interface_locale` FROM `%s`",
                $this->connection->getTableName('core_config_data'),
                $this->connection->getTableName('admin_user')
            )
        );

        $locales = array_column($output, 'value');

        if (!in_array($this->adminData->getLocale(), $locales)) {
            $locales[] = $this->adminData->getLocale();
        }

        return $locales;
    }

    /**
     * @inheritdoc
     */
    public function getVerbosityLevel(): string
    {
        return $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
    }

    /**
     * @inheritdoc
     */
    public function getMaxExecutionTime()
    {
        return $this->stageConfig->get(DeployInterface::VAR_SCD_MAX_EXEC_TIME);
    }

    /**
     * @inheritdoc
     */
    public function hasNoParent(): bool
    {
        return $this->stageConfig->get(BuildInterface::VAR_SCD_NO_PARENT);
    }
}
