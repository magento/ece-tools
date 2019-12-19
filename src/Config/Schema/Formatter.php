<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema;

use Symfony\Component\Yaml\Dumper;

/**
 * @inheritDoc
 */
class Formatter implements FormatterInterface
{
    private const EMPTY_LINE = "\n\n";

    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @param Dumper $dumper
     */
    public function __construct(Dumper $dumper)
    {
        $this->dumper = $dumper;
    }

    /**
     * @inheritDoc
     */
    public function format(array $data): string
    {
        $text = '';

        foreach ($data as $key => $item) {
            $text .= '# ' . $key . self::EMPTY_LINE;
            $text .= $item['description'];
            $text .= self::EMPTY_LINE;

            $text .= '## Magento version' . self::EMPTY_LINE;
            $text .= '`' . $item['magento_version'] . '`' . self::EMPTY_LINE;

            if (!empty($item['stages'])) {
                $text .= '## Stages' . self::EMPTY_LINE;
                $text .= implode(', ', $item['stages']);
                $text .= self::EMPTY_LINE;
            }

            if (!empty($item['examples'])) {
                $text .= '## Examples' . self::EMPTY_LINE;

                foreach ($item['examples'] as $example) {
                    $text .= $this->wrapCode($this->dumper->dump($example, 4, 2), 'yaml');
                }
            }
        }

        return $text;
    }

    /**
     * @param string $code
     * @param string|null $lang
     * @return string
     */
    private function wrapCode(string $code, string $lang = null): string
    {
        return '```' . ($lang ?: '') . "\n" . $code . "\n" . '```' . self::EMPTY_LINE;
    }
}
