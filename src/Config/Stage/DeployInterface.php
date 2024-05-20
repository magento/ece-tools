<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Provides access to configuration of deploy stage.
 *
 * @api
 */
interface DeployInterface extends StageConfigInterface
{
    public const VAR_QUEUE_CONFIGURATION = 'QUEUE_CONFIGURATION';
    public const VAR_SEARCH_CONFIGURATION = 'SEARCH_CONFIGURATION';
    public const VAR_ELASTICSUITE_CONFIGURATION = 'ELASTICSUITE_CONFIGURATION';
    public const VAR_CACHE_REDIS_BACKEND = 'REDIS_BACKEND';
    public const VAR_CACHE_CONFIGURATION = 'CACHE_CONFIGURATION';
    public const VAR_SESSION_CONFIGURATION = 'SESSION_CONFIGURATION';
    public const VAR_DATABASE_CONFIGURATION = 'DATABASE_CONFIGURATION';
    public const VAR_RESOURCE_CONFIGURATION = 'RESOURCE_CONFIGURATION';
    public const VAR_CRON_CONSUMERS_RUNNER = 'CRON_CONSUMERS_RUNNER';
    public const VAR_CONSUMERS_WAIT_FOR_MAX_MESSAGES = 'CONSUMERS_WAIT_FOR_MAX_MESSAGES';
    public const VAR_CLEAN_STATIC_FILES = 'CLEAN_STATIC_FILES';
    public const VAR_UPDATE_URLS = 'UPDATE_URLS';
    public const VAR_FORCE_UPDATE_URLS = 'FORCE_UPDATE_URLS';
    public const VAR_REMOTE_STORAGE = 'REMOTE_STORAGE';

    /**
     * The variable responsible to set lock provider for Magento 2.2.5 and higher.
     */
    public const VAR_LOCK_PROVIDER = 'LOCK_PROVIDER';

    /**
     * The variable responsible to set Redis slave connection when it has true value.
     */
    public const VAR_REDIS_USE_SLAVE_CONNECTION = 'REDIS_USE_SLAVE_CONNECTION';

    /**
     * The variable responsible to set mysql slave connection when it has true value.
     */
    public const VAR_MYSQL_USE_SLAVE_CONNECTION = 'MYSQL_USE_SLAVE_CONNECTION';

    /**
     * The variable responsible to use split database.
     *
     * @deprecated started from Magento 2.4.2 and will be removed in 2.5.0
     */
    const VAR_SPLIT_DB = 'SPLIT_DB';

    /**
     * The value of the variable SPLIT_DB
     *
     * @deprecated started from Magento 2.4.2 and will be removed in 2.5.0
     */
    const SPLIT_DB_VALUE_QUOTE = 'quote';

    /**
     * The value of the variable SPLIT_DB
     *
     * @deprecated started from Magento 2.4.2 and will be removed in 2.5.0
     */
    const SPLIT_DB_VALUE_SALES = 'sales';

    /**
     * Values for variable SPLIT_DB
     *
     * @deprecated started from Magento 2.4.2 and will be removed in 2.5.0
     */
    const SPLIT_DB_VALUES = [
        self::SPLIT_DB_VALUE_QUOTE,
        self::SPLIT_DB_VALUE_SALES
    ];

    /**
     * @deprecated 2.1 specific variable.
     */
    public const VAR_GENERATED_CODE_SYMLINK = 'GENERATED_CODE_SYMLINK';

    /**
     * The variable responsible for enabling google analytics in environments other than prod.
     */
    public const VAR_ENABLE_GOOGLE_ANALYTICS = 'ENABLE_GOOGLE_ANALYTICS';

    /**
     * The variable responsible for enabling LUA cache in environments starting from Magento 2.4.7.
     */
    public const VAR_USE_LUA = 'USE_LUA';

    /**
     * The variable responsible for LUA KEY in environments starting from Magento 2.4.7.
     */
    public const VAR_LUA_KEY = 'LUA_KEY';
}
