<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Deploy;

use Magento\MagentoCloud\Config\Environment;
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
     * @var Environment
     */
    private $environment;

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
     * @param Environment $environment
     * @param ConnectionInterface $connection
     * @param MagentoVersion $magentoVersion
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $connection,
        MagentoVersion $magentoVersion,
        ThreadCountOptimizer $threadCountOptimizer,
        DeployInterface $stageConfig
    ) {
        $this->environment = $environment;
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
    public function getExcludedThemes(): array
    {
        $themes = preg_split("/[,]+/", $this->stageConfig->get(DeployInterface::VAR_SCD_EXCLUDE_THEMES));

        return array_filter(array_map('trim', $themes));
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

        if (!in_array($this->environment->getAdminLocale(), $locales)) {
            $locales[] = $this->environment->getAdminLocale();
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
}
