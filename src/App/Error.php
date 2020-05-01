<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App;

/**
 * Class for constants of all possible critical errors which can stop deployment process.
 */
class Error
{
    public const BUILD_COMPOSER_PACKAGE_NOT_FOUND = 1;
    public const BUILD_ENV_PHP_IS_NOT_WRITABLE = 2;
    public const BUILD_CONFIG_NOT_DEFINED = 3;
    public const BUILD_CONFIG_PHP_IS_NOT_WRITABLE = 3;
    public const BUILD_CANT_READ_COMPOSER_JSON = 4;
    public const BUILD_COMPOSER_MISSED_REQUIRED_AUTOLOAD = 4;
    public const BUILD_WRONG_CONFIGURATION_MAGENTO_ENV_YAML = 5;
    public const BUILD_MODULE_ENABLE_COMMAND_FAILED = 6;
    public const BUILD_PATCH_APPLYING_FAILED = 7;
    public const BUILD_FILE_LOCAL_XML_IS_NOT_WRITABLE = 8;
    public const BUILD_FAILED_COPY_SAMPLE_DATA = 10;
    public const BUILD_DI_COMPILATION_FAILED = 9;
    public const BUILD_COMPOSER_DUMP_AUTOLOAD_FAILED = 10;
    public const BUILD_SCD_FAILED = 11;
    public const BUILD_BALER_NOT_FOUND = 12;
    public const BUILD_UTILITY_NOT_FOUND = 12;
    public const BUILD_SCD_COMPRESSION_FAILED = 13;
    public const BUILD_SCD_COPYING_FAILED = 14;
    public const BUILD_WRITABLE_DIRECTORY_COPYING_FAILED = 15;
    public const BUILD_UNABLE_TO_CREATE_LOGGER = 17;

    public const DEPLOY_WRONG_CACHE_CONFIGURATION = 101;
    public const DEPLOY_ENV_PHP_IS_NOT_WRITABLE = 102;
    public const DEPLOY_CONFIG_NOT_DEFINED = 103;
    public const DEPLOY_REDIS_CACHE_CLEAN_FAILED = 103;
    public const DEPLOY_MAINTENANCE_MODE_ENABLING_FAILED = 104;
    public const DEPLOY_WRONG_CONFIGURATION_DB = 105;
    public const DEPLOY_WRONG_CONFIGURATION_SESSION = 106;
    public const DEPLOY_WRONG_CONFIGURATION_SEARCH = 107;
    public const DEPLOY_ELASTIC_SUITE_WITHOUT_ES = 107;
    public const DEPLOY_ELASTIC_SUITE_WRONG_ENGINE = 107;
    public const DEPLOY_QUERY_EXECUTION_FAILED = 109;
    public const DEPLOY_INSTALL_COMMAND_FAILED = 110;
    public const DEPLOY_SCD_FAILED = 111;
    public const DEPLOY_CONFIG_IMPORT_COMMAND_FAILED = 112;
    public const DEPLOY_UTILITY_NOT_FOUND = 112;
    public const DEPLOY_SCD_COMPRESSION_FAILED = 113;
    public const DEPLOY_SCD_UNABLE_UPDATE_VERSION = 113;
    public const DEPLOY_SCD_CLEAN_FAILED = 113;
    public const DEPLOY_VIEW_PREPROCESSED_CLEAN_FAILED = 113;
    public const DEPLOY_SPLIT_DB_COMMAND_FAILED = 114;
    public const DEPLOY_FILE_CREDENTIALS_EMAIL_NOT_WRITABLE = 115;
    public const DEPLOY_UPGRADE_COMMAND_FAILED = 116;
    public const DEPLOY_CACHE_FLUSH_COMMAND_FAILED = 117;
    public const DEPLOY_MAINTENANCE_MODE_DISABLING_FAILED = 118;

    public const PD_DEPLOY_ID_FAILED = 201;
    public const PD_ENV_PHP_IS_NOT_WRITABLE = 202;
    public const PD_CONFIG_NOT_DEFINED = 203;
    public const PD_DURING_PAGE_WARM_UP = 203;
    public const PD_DURING_TIME_TO_FIRST_BYTE = 204;
    public const PD_CACHE_FLUSH_COMMAND_FAILED = 217;

    public const GLOBAL_CONFIG_NOT_DEFINED = 243;
}
