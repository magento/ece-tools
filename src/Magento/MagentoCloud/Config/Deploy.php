<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

class Deploy
{
    const MAGENTO_PRODUCTION_MODE = 'production';
    const MAGENTO_DEVELOPER_MODE = 'developer';

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return isset($_ENV[$key]) ? json_decode(base64_decode($_ENV[$key])) : $default;
    }

    /**
     * Get routes information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRoutes()
    {
        return $this->get('MAGENTO_CLOUD_ROUTES');
    }

    /**
     * Get relationships information from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getRelationships()
    {
        return $this->get('MAGENTO_CLOUD_RELATIONSHIPS');
    }

    /**
     * Get relationship information from MagentoCloud environment variable by key.
     *
     * @param string $key
     * @return array
     */
    public function getRelationship($key)
    {
        $relationships = $this->getRelationships();

        return isset($relationships[$key]) ? $relationships[$key] : [];
    }

    /**
     * Get custom variables from MagentoCloud environment variable.
     *
     * @return mixed
     */
    public function getVariables()
    {
        return $this->get('MAGENTO_CLOUD_VARIABLES');
    }

    /**
     * Checks that static content symlink is on.
     *
     * If STATIC_CONTENT_SYMLINK == disabled return false
     * Returns true by default
     *
     * @return bool
     */
    public function isStaticContentSymlinkOn()
    {
        $var = $this->getVariables();

        return isset($var['STATIC_CONTENT_SYMLINK']) && $var['STATIC_CONTENT_SYMLINK'] == 'disabled'
            ? false : true;
    }

    /**
     * @return string
     */
    public function getVerbosityLevel(): string
    {
        $var = $this->getVariables();

        return isset($var['VERBOSE_COMMANDS']) && $var['VERBOSE_COMMANDS'] == 'enabled'
            ? ' -vvv ' : '';
    }

    public function getApplicationMode()
    {
        $var = $this->getVariables();
        $magentoApplicationMode = isset($var["APPLICATION_MODE"]) ? $var["APPLICATION_MODE"] : false;
        $magentoApplicationMode =
            in_array($magentoApplicationMode, array(self::MAGENTO_DEVELOPER_MODE, self::MAGENTO_PRODUCTION_MODE))
                ? $magentoApplicationMode
                : self::MAGENTO_PRODUCTION_MODE;

        return $magentoApplicationMode;
    }
}
