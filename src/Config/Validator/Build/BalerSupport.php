<?php

declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Build;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Magento\Shared\ReaderInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Verify that the build configuration is ready for baler.
 */
class BalerSupport implements ValidatorInterface
{
    /**
     * @var ExecutableFinder
     */
    private $finder;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var ReaderInterface
     */
    private $configReader;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param ExecutableFinder $finder
     * @param ReaderInterface $configReader
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        ExecutableFinder $finder,
        ReaderInterface $configReader
    ) {
        $this->resultFactory = $resultFactory;
        $this->finder = $finder;
        $this->configReader = $configReader;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate(): Validator\ResultInterface
    {
        $errors = [];

        if ($this->finder->find('baler') === null) {
            $errors[] = 'Path to baler executable could not be found.'
                . ' The Node package may not be installed or may not be linked.';
        }

        $magentoConfig = $this->configReader->read();

        if (!isset($magentoConfig['modules']['Magento_Baler']) || !$magentoConfig['modules']['Magento_Baler']) {
            $errors[] = 'The Magento_Baler module is not installed or is disabled.';
        }

        $jsConfig = $magentoConfig['system']['default']['dev']['js'] ?? [];

        if (!isset($jsConfig['enable_baler_js_bundling']) || !$jsConfig['enable_baler_js_bundling']) {
            $errors[] = 'The Magento config dev/js/enable_baler_js_bundling must be enabled in app/etc/config.php.';
        }

        foreach (['minify_files', 'enable_js_bundling', 'merge_files'] as $configKey) {
            if (!isset($jsConfig[$configKey]) || $jsConfig[$configKey]) {
                $errors[] = sprintf('The Magento config dev/js/%s must be disabled in app/etc/config.php.', $configKey);
            }
        }

        return $errors === []
            ? $this->resultFactory->success()
            : $this->resultFactory->error(
                'Baler JS bundling cannot be used because of the following issues:',
                implode(PHP_EOL, $errors),
                Error::WARN_BALER_CANNOT_BE_USED
            );
    }
}
