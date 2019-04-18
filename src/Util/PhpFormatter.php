<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Formats PHP array into exportable string.
 */
class PhpFormatter
{
    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(MagentoVersion $magentoVersion)
    {
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * 4 space indentation for array formatting.
     */
    const INDENT = '    ';

    /**
     * Format deployment configuration.
     *
     * @param array $data
     * @return string
     * @throws UndefinedPackageException
     */
    public function format(array $data): string
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2.5')) {
            return "<?php\nreturn " . var_export($data, true) . ";\n";
        }

        return "<?php\nreturn " . $this->varExportShort($data) . ";\n";
    }

    /**
     * If variable to export is an array, format with the php >= 5.4 short array syntax. Otherwise use
     * default var_export functionality.
     *
     * @param mixed $var
     * @param int $depth
     * @return string
     */
    public function varExportShort($var, int $depth = 1): string
    {
        if (!is_array($var)) {
            return var_export($var, true);
        }

        $indexed = array_keys($var) === range(0, count($var) - 1);
        $expanded = [];
        foreach ($var as $key => $value) {
            $expanded[] = str_repeat(self::INDENT, $depth)
                . ($indexed ? '' : $this->varExportShort($key) . ' => ')
                . $this->varExportShort($value, $depth + 1);
        }

        return sprintf("[\n%s\n%s]", implode(",\n", $expanded), str_repeat(self::INDENT, $depth - 1));
    }
}
