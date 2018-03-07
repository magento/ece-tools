<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Stage;

use Magento\MagentoCloud\Config\StageConfigInterface;

/**
 * Provides access to configuration of deploy stage.
 */
interface DeployInterface extends StageConfigInterface
{
    const VAR_QUEUE_CONFIGURATION = 'QUEUE_CONFIGURATION';
    const VAR_SEARCH_CONFIGURATION = 'SEARCH_CONFIGURATION';
    const VAR_CACHE_CONFIGURATION = 'CACHE_CONFIGURATION';
    const VAR_SESSION_CONFIGURATION = 'SESSION_CONFIGURATION';
    const VAR_CRON_CONSUMERS_RUNNER = 'CRON_CONSUMERS_RUNNER';
    const VAR_CLEAN_STATIC_FILES = 'CLEAN_STATIC_FILES';
    const VAR_STATIC_CONTENT_SYMLINK = 'STATIC_CONTENT_SYMLINK';
    const VAR_UPDATE_URLS = 'UPDATE_URLS';
    const VAR_STATIC_CONTENT_EXCLUDE_THEMES = 'STATIC_CONTENT_EXCLUDE_THEMES';

    /**
     * The variable responsible to set mysql slave connection when it has true value
     */
    const VAR_MYSQL_READ_DISTRIBUTION = 'MYSQL_READ_DISTRIBUTION';

    /**
     * @deprecated 2.1 specific variable.
     */
    const VAR_GENERATED_CODE_SYMLINK = 'GENERATED_CODE_SYMLINK';
}
