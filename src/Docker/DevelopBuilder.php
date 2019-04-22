<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Docker;

use Illuminate\Contracts\Config\Repository;

/**
 * @inheritDoc
 */
class DevelopBuilder extends ProductionBuilder
{
    /**
     * @inheritDoc
     */
    public function build(Repository $config): array
    {
        $compose = parent::build($config);
        $compose['volumes'] = [
            'magento' => [
                'external' => true
            ]
        ];

        return $compose;
    }

    /**
     * @inheritDoc
     */
    public function getMagentoVolumes(bool $isReadOnly): array
    {
        return [
            'magento:' . self::DIR_MAGENTO . ':nocopy'
        ];
    }
}
