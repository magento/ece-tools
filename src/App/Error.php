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
    public const DEFAULT_ERROR = 1;

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
    public const BUILD_WRONG_BRAINTREE_VARIABLE = 26;

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
    public const DEPLOY_ES_CANNOT_CONNECT = 132;
    public const DEPLOY_WRONG_BRAINTREE_VARIABLE = 133;
    public const DEPLOY_ES_SERVICE_NOT_INSTALLED = 134;
    public const DEPLOY_WRONG_SEARCH_ENGINE = 135;

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

    /**
     * Build
     */
    public const WARN_CONFIG_PHP_NOT_EXISTS = 1001;
    public const WARN_UNSUPPORTED_BUILDS_OPTION_INI = 1002;
    public const WARN_MISSED_MODULE_SECTION = 1003;
    public const WARN_CONFIGURATION_VERSION_MISMATCH = 1004;
    public const WARN_SCD_OPTIONS_IGNORANCE = 1005;
    public const WARN_CONFIGURATION_STATE_NOT_IDEAL = 1006;
    public const WARN_BALER_CANNOT_BE_USED = 1007;

    /**
     * Deploy
     */
    public const WARN_REDIS_SERVICE_NOT_AVAILABLE = 2001;
    public const WARN_WRONG_SPLIT_DB_CONFIG = 2002;
    public const WARN_DIR_NESTING_LEVEL_NOT_CONFIGURED = 2003;
    public const WARN_NOT_CORRECT_LOCAL_XML_FILE = 2004;
    public const WARN_ADMIN_DATA_IGNORED = 2005;
    public const WARN_ADMIN_EMAIL_NOT_SET = 2006;
    public const WARN_UPDATE_PHP_VERSION = 2007;
    public const WARN_SOLR_DEPRECATED = 2008;
    public const WARN_SOLR_NOT_SUPPORTED = 2009;
    public const WARN_ES_INSTALLED_BUT_NOT_USED = 2010;
    public const WARN_ES_VERSION_MISMATCH = 2011;
    public const WARN_CONFIG_NOT_COMPATIBLE = 2012;
    public const WARN_DEPLOY_SCD_OPTIONS_IGNORANCE = 2013;
    public const WARN_DEPRECATED_VARIABLES = 2014;
    public const WARN_ENVIRONMENT_CONFIG_NOT_VALID = 2015;
    public const WARN_CONFIG_WRONG_JSON_FORMAT = 2016;
    public const WARN_SERVICE_VERSION_NOT_COMPATIBLE = 2017;
    public const WARN_SERVICE_PASSED_EOL = 2018;
    public const WARN_DEPRECATED_MYSQL_SEARCH_ENGINE = 2019;
    public const WARN_ENV_PHP_MISSED = 2020;
    public const WARN_SPLIT_DB_CUSTOM_CONNECTION_USED = 2021;
    public const WARN_DB_CONFIG_NOT_COMPATIBLE_WITH_SLAVE = 2022;
    public const WARN_SPLIT_DB_ENABLING_SKIPPED = 2023;
    public const WARN_NOT_ENOUGH_DATA_SPLIT_DB_VAR = 2024;
    public const WARN_SLAVE_CONNECTION_NOT_SET = 2025;
    public const WARN_COPY_MOUNTED_DIRS_FAILED = 2026;
    public const WARN_NOT_SUPPORTED_MAGE_MODE = 2027;

    /**
     * Post-deploy
     */
    public const WARN_DEBUG_LOG_ENABLED = 3001;
    public const WARN_CANNOT_FETCH_STORE_URLS = 3002;
    public const WARN_CANNOT_FETCH_STORE_URL = 3003;
    public const WARN_CREATE_CONFIG_BACKUP_FAILED = 3004;

    /**
     * General
     */
    public const WARN_CANNOT_GET_PROC_COUNT = 4001;
}
