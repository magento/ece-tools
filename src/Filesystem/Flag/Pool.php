<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem\Flag;

/**
 * The pool of available flags.
 */
class Pool
{
    /**
     * @var array
     */
    private static $flags = [
        Manager::FLAG_REGENERATE => 'var/.regenerate',
        Manager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD => '.static_content_deploy',
        Manager::FLAG_DEPLOY_HOOK_IS_FAILED => 'var/.deploy_is_failed',
        Manager::FLAG_IGNORE_SPLIT_DB => 'var/.ignore_split_db',
        Manager::FLAG_ENV_FILE_ABSENCE => 'var/.env_file_absence',
    ];

    /**
     * Gets flag path by key, returns null if flag not exists.
     *
     * @param string $key
     * @return string|null
     */
    public function get(string $key): ?string
    {
        return self::$flags[$key] ?? null;
    }
}
