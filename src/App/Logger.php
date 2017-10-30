<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * @inheritdoc
 */
class Logger extends \Monolog\Logger
{
    /**
     * Path to the deployment log.
     */
    const DEPLOY_LOG_PATH = 'var/log/cloud.log';

    /**
     * Path to the build phase log.
     */
    const BACKUP_BUILD_PHASE_LOG_PATH = 'init/var/log/cloud.log';

    /**
     * Path to the log dir
     */
    const LOG_DIR = 'var/log';

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n");
        $formatter->allowInlineLineBreaks();
        $formatter->ignoreEmptyContextAndExtra();

        parent::__construct('default', [
            (new StreamHandler($directoryList->getMagentoRoot() . '/' . self::DEPLOY_LOG_PATH))
                ->setFormatter($formatter),
            (new StreamHandler('php://stdout'))
                ->setFormatter($formatter),
        ]);
    }
}
