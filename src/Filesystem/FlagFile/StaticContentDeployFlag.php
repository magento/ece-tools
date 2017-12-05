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
class StaticContentDeployFlag implements FlagFileInterface
{
    const PATH = '.static_content_deploy';
    const KEY = 'scd_in_build';

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
     * @inheritdoc
     */
    public function exists(): bool
    {
        return $this->base->exists(self::PATH);
    }

    /**
     * @inheritdoc
     */
    public function set(): bool
    {
        return $this->base->set(self::PATH);
    }

    /**
     * @inheritdoc
     */
    public function delete(): bool
    {
        return $this->base->delete(self::PATH);
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return self::PATH;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return self::KEY;
    }
}
