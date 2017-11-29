<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\FlagFile;

use Magento\MagentoCloud\Filesystem\FlagFileInterface;

/**
 * @inheritdoc
 */
class StaticContentDeployPendingFlag implements FlagFileInterface
{
    const PATH = 'var/.static_content_deploy_pending';
    const KEY = 'scd_pending';

    /**
     * @var Base
     */
    private $base;

    /**
     * @param Base $base
     */
    public function __construct(
        Base $base
    ) {
        $this->base = $base;
    }

    /**
     * Default exists
     */
    public function exists()
    {
        return $this->base->exists(self::PATH);
    }

    /**
     * Default set
     */
    public function set()
    {
        return $this->base->set(self::PATH);
    }

    /**
     * Default clear
     */
    public function delete()
    {
        return $this->base->delete(self::PATH);
    }

    /**
     * Return our path
     *
     * @return string
     */
    public function getPath()
    {
        return self::PATH;
    }

    /**
     * Return our key
     *
     * @return string
     */
    public function getKey()
    {
        return self::KEY;
    }
}
