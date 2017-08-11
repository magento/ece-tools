<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Utils;

use Magento\MagentoCloud\Filesystem\Driver\File;

class ComponentInfo
{
    /**
     * We only want to look up each component version once since it shouldn't change
     *
     * @var array
     */
    private $componentVersions = [];

    /**
     * @var File
     */
    private $file;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function get()
    {
        $components = ['ece-tools', 'magento2-base'];
        $message = '';

        $first = true;
        foreach ($components as $component) {
            if ($this->hasVersionOfComponent($component)) {
                if ($first) {
                    $first = false;
                    $message .= " (";
                } else {
                    $message .= ", ";
                }
                $message .= $component . " version: " . $this->versionOfComponent($component);
            }
        }
        if (!$first) {
            $message .= ")";
        }

        return $message;
    }

    private function getVersionOfComponent($component)
    {
        $composerJsonPath = MAGENTO_ROOT . "/vendor/magento/" . $component . "/composer.json";
        $version = null;
        try {
            if ($this->file->isExists($composerJsonPath)) {
                $jsonData = json_decode(file_get_contents($composerJsonPath), true);
                if (array_key_exists("version", $jsonData)) {
                    $version = $jsonData["version"];
                }
            }
        } catch (\Exception $e) {
            // If we get an exception (or error), we don't worry because we just won't use the version.
            // Note: We could use Throwable to catch them both, but that only works in PHP >= 7
        } catch (\Error $e) {  // Note: this only works PHP >= 7
        }
        $this->componentVersions[$component] = $version;
    }

    private function versionOfComponent($component)
    {
        if (!array_key_exists($component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }

        return $this->componentVersions[$component];
    }

    private function hasVersionOfComponent($component)
    {
        if (!array_key_exists($component, $this->componentVersions)) {
            $this->getVersionOfComponent($component);
        }

        return !is_null($this->componentVersions[$component]);
    }
}
