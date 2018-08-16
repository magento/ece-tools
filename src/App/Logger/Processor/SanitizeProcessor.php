<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Processor;

/**
 * Uses for sanitize sensitive data.
 */
class SanitizeProcessor
{
    /**
     * Array of replacements that will be applied to log messages.
     *
     * @var array
     */
    private $replacements = [
        '/-password=\'.*?\'(\s|$)/i' => '-password=\'******\'$1',
        '/mysqldump (.* )-p\'[^\']+\'/i'  => 'mysqldump $1-p\'******\'',
    ];

    /**
     * Finds and replace sensitive data in record message.
     *
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        foreach ($this->replacements as $pattern => $replacement) {
            $record['message'] = preg_replace($pattern, $replacement, $record['message']);
        }

        return $record;
    }
}
