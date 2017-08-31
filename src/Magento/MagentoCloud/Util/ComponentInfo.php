<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

class ComponentInfo
{
    /**
     * @var ComponentVersion
     */
    private $componentVersion;

    /**
     * @param ComponentVersion $componentVersion
     */
    public function __construct(
        ComponentVersion $componentVersion
    ) {
        $this->componentVersion = $componentVersion;
    }

    /**
     * Returns info about versions of given components
     *
     * @param array $components The array of components
     * ```php
     *    [
     *       'component-name',
     *       [
     *           'vendor' => 'some-vendor'
     *           'name' => 'some-name'
     *       ],
     *       [
     *           'vendor' => 'some-another-vendor'
     *           'name' => 'some-name'
     *       ]
     *    ]
     * ```
     * @return string
     */
    public function get(array $components = ['ece-tools', 'magento2-base']) : string
    {
        $versions = [];
        foreach ($components as $component) {
            $version = false;
            if (is_array($component)
                && isset($component['name'], $component['vendor'])
            ) {
                $version = $this->componentVersion->get($component['name'], $component['vendor']);
            } elseif (is_string($component)) {
                $version = $this->componentVersion->get($component);
            }

            if ($version) {
                $versions[] = sprintf(
                    '%s version: %s',
                    $component,
                    $version
                );
            }
        }

        return '(' . implode(',', $versions) . ')';
    }
}
