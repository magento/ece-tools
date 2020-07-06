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
    public const BUILD_ENV_PHP_IS_NOT_WRITABLE = 2;
    public const BUILD_CONFIG_NOT_DEFINED = 3;
    public const BUILD_CONFIG_PARSE_FAILED = 4;
    public const BUILD_CONFIG_UNABLE_TO_READ = 5;
    public const BUILD_CONFIG_UNABLE_TO_READ_SCHEMA_YAML = 6;
    public const BUILD_CONFIG_PHP_IS_NOT_WRITABLE = 7;
    public const BUILD_CANT_READ_COMPOSER_JSON = 8;
    public const BUILD_COMPOSER_MISSED_REQUIRED_AUTOLOAD = 9;
    public const BUILD_WRONG_CONFIGURATION_MAGENTO_ENV_YAML = 10;
    public const BUILD_MODULE_ENABLE_COMMAND_FAILED = 11;
    public const BUILD_PATCH_APPLYING_FAILED = 12;
    public const BUILD_FILE_LOCAL_XML_IS_NOT_WRITABLE = 13;
    public const BUILD_FAILED_COPY_SAMPLE_DATA = 14;
    public const BUILD_DI_COMPILATION_FAILED = 15;
    public const BUILD_COMPOSER_DUMP_AUTOLOAD_FAILED = 16;
    public const BUILD_BALER_NOT_FOUND = 17;
    public const BUILD_UTILITY_NOT_FOUND = 18;
    public const BUILD_SCD_FAILED = 19;
    public const BUILD_SCD_COMPRESSION_FAILED = 20;
    public const BUILD_SCD_COPYING_FAILED = 21;
    public const BUILD_WRITABLE_DIRECTORY_COPYING_FAILED = 22;
    public const BUILD_UNABLE_TO_CREATE_LOGGER = 23;
    public const BUILD_CLEAN_INIT_PUB_STATIC_FAILED = 24;
    public const BUILD_COMPOSER_PACKAGE_NOT_FOUND = 25;

    public const DEPLOY_WRONG_CACHE_CONFIGURATION = 101;
    public const DEPLOY_ENV_PHP_IS_NOT_WRITABLE = 102;
    public const DEPLOY_CONFIG_NOT_DEFINED = 103;
    public const DEPLOY_CONFIG_PARSE_FAILED = 104;
    public const DEPLOY_CONFIG_UNABLE_TO_READ = 105;
    public const DEPLOY_CONFIG_UNABLE_TO_READ_SCHEMA_YAML = 106;
    public const DEPLOY_REDIS_CACHE_CLEAN_FAILED = 107;
    public const DEPLOY_MAINTENANCE_MODE_ENABLING_FAILED = 108;
    public const DEPLOY_WRONG_CONFIGURATION_DB = 109;
    public const DEPLOY_WRONG_CONFIGURATION_SESSION = 110;
    public const DEPLOY_WRONG_CONFIGURATION_SEARCH = 111;
    public const DEPLOY_WRONG_CONFIGURATION_RESOURCE = 112;
    public const DEPLOY_ELASTIC_SUITE_WITHOUT_ES = 113;
    public const DEPLOY_ELASTIC_SUITE_WRONG_ENGINE = 114;
    public const DEPLOY_QUERY_EXECUTION_FAILED = 115;
    public const DEPLOY_INSTALL_COMMAND_FAILED = 116;
    public const DEPLOY_CONFIG_IMPORT_COMMAND_FAILED = 117;
    public const DEPLOY_UTILITY_NOT_FOUND = 118;
    public const DEPLOY_SCD_FAILED = 119;
    public const DEPLOY_SCD_COMPRESSION_FAILED = 120;
    public const DEPLOY_SCD_CANNOT_UPDATE_VERSION = 121;
    public const DEPLOY_SCD_CLEAN_FAILED = 122;
    public const DEPLOY_SPLIT_DB_COMMAND_FAILED = 123;
    public const DEPLOY_VIEW_PREPROCESSED_CLEAN_FAILED = 124;
    public const DEPLOY_FILE_CREDENTIALS_EMAIL_NOT_WRITABLE = 125;
    public const DEPLOY_UPGRADE_COMMAND_FAILED = 126;
    public const DEPLOY_CACHE_FLUSH_COMMAND_FAILED = 127;
    public const DEPLOY_MAINTENANCE_MODE_DISABLING_FAILED = 128;
    public const DEPLOY_UNABLE_TO_READ_RESET_PASSWORD_TMPL = 129;
    public const DEPLOY_CACHE_ENABLE_FAILED = 130;
    public const DEPLOY_CRYPT_KEY_IS_ABSENT = 131;

    public const PD_DEPLOY_IS_FAILED = 201;
    public const PD_ENV_PHP_IS_NOT_WRITABLE = 202;
    public const PD_CONFIG_NOT_DEFINED = 203;
    public const PD_CONFIG_PARSE_FAILED = 204;
    public const PD_CONFIG_UNABLE_TO_READ = 205;
    public const PD_CONFIG_UNABLE_TO_READ_SCHEMA_YAML = 206;
    public const PD_DURING_PAGE_WARM_UP = 207;
    public const PD_DURING_TIME_TO_FIRST_BYTE = 208;
    public const PD_CACHE_FLUSH_COMMAND_FAILED = 227;

    public const GLOBAL_CONFIG_NOT_DEFINED = 243;
    public const GLOBAL_CONFIG_PARSE_FAILED = 244;
    public const GLOBAL_CONFIG_UNABLE_TO_READ = 245;
    public const GLOBAL_CONFIG_UNABLE_TO_READ_SCHEMA_YAML = 246;
}
