<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Prestart;

use Magento\MagentoCloud\Config\Environment;
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
     * @param Environment $environment
     * @param ConnectionInterface $connection
     * @param MagentoVersion $magentoVersion
     * @param ThreadCountOptimizer $threadCountOptimizer
     */
    public function __construct(
        Environment $environment,
        ConnectionInterface $connection,
        MagentoVersion $magentoVersion,
        ThreadCountOptimizer $threadCountOptimizer
    ) {
        $this->environment = $environment;
        $this->connection = $connection;
        $this->magentoVersion = $magentoVersion;
        $this->threadCountOptimizer = $threadCountOptimizer;
    }

    /**
     * @inheritdoc
     */
    public function getThreadCount(): int
    {
        return $this->threadCountOptimizer->optimize(
            $this->environment->getStaticDeployThreadsCount(),
            $this->getStrategy()
        );
    }

    /**
     * @inheritdoc
     */
    public function getExcludedThemes(): array
    {
        $themes = preg_split("/[,]+/", $this->environment->getStaticDeployExcludeThemes());

        return array_filter(array_map('trim', $themes));
    }

    /**
     * @inheritdoc
     */
    public function getStrategy(): string
    {
        return $this->environment->getVariable(Environment::VAR_SCD_STRATEGY, '');
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
            'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' ' .
            'UNION SELECT interface_locale FROM admin_user'
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
        return $this->environment->getVerbosityLevel();
    }
}
