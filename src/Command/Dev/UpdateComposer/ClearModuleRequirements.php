<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Dev\UpdateComposer;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

/**
 * Generates script for clearing module requirements that run after composer install.
 *
 * This requires for avoiding requirement conflicts for not released magento version.
 */
class ClearModuleRequirements
{
    const SCRIPT_PATH = 'clear_module_requirements.php';

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        DirectoryList $directoryList,
        File $file
    ) {
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * Generates script for clearing module requirements that run after composer install.
     *
     * @param array $repos
     * @return void
     */
    public function generate(array $repos)
    {
        $rootDirectory = $this->directoryList->getMagentoRoot();
        $clearModulesFilePath = $rootDirectory . '/' . self::SCRIPT_PATH;
        $reposArrayToString = '[\'' . implode('\', \'', $repos) . '\']';

        $clearModulesCode = <<<CODE
<?php
foreach ({$reposArrayToString} as \$repoName) {
    foreach (glob(__DIR__ .'/' . \$repoName . '/app/code/Magento/*') as \$moduleDir) {
        if (!file_exists(\$moduleDir . '/composer.json')) {
            continue;
        }

        \$composerJson = json_decode(file_get_contents(\$moduleDir . '/composer.json'), true);

        foreach (\$composerJson['require'] as \$requireName => \$requireVersion) {
            if (strpos(\$requireName, 'magento/') !== false) {
                unset(\$composerJson['require'][\$requireName]);
            }
        }

        file_put_contents(
            \$moduleDir . '/composer.json',
            json_encode(\$composerJson, JSON_PRETTY_PRINT)
        );
    }
}
CODE;
        $this->file->filePutContents($clearModulesFilePath, $clearModulesCode);

        $gitIgnore = $this->file->fileGetContents($rootDirectory . '/.gitignore');
        if (strpos($gitIgnore, self::SCRIPT_PATH) === false) {
            $this->file->filePutContents(
                $rootDirectory . '/.gitignore',
                '!/' . self::SCRIPT_PATH . PHP_EOL,
                FILE_APPEND
            );
        }
    }
}
