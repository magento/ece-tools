<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Integration;

use Magento\MagentoCloud\Command\Build;
use Magento\MagentoCloud\Command\Deploy;
use Magento\MagentoCloud\Command\PostDeploy;
use Magento\MagentoCloud\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;

/**
 * @inheritdoc
 */
class PostDeployTest extends AbstractTest
{
    /**
     * @param string $commandName
     * @param Application $application
     * @return int
     */
    private function execute($commandName, $application)
    {
        $application->getContainer()->set(
            \Psr\Log\LoggerInterface::class,
            \Magento\MagentoCloud\App\Logger::class
        );
        $commandTester = new CommandTester($application->get($commandName));
        $commandTester->execute([]);
        return $commandTester->getStatusCode();
    }

    public function testPostDeploy()
    {
        $application = $this->bootstrap->createApplication(['variables' => ['ADMIN_EMAIL' => 'admin@example.com']]);

        /** @var File $file */
        $file = $application->getContainer()->get(File::class);
        $file->copy(
            sprintf('%s/_files/.magento.env.yaml.scdondemand', __DIR__),
            sprintf('%s/.magento.env.yaml', $this->bootstrap->getSandboxDir())
        );

        $this->assertSame(0, $this->execute(Build::NAME, $application));
        $this->assertSame(0, $this->execute(Deploy::NAME, $application));
        $this->assertSame(0, $this->execute(PostDeploy::NAME, $application));

        /** @var FileList $fileList */
        $fileList = $application->getContainer()->get(FileList::class);
        $cloudLog = file_get_contents($fileList->getCloudLog());

        $this->assertContains('NOTICE: Starting post-deploy.', $cloudLog);
        $this->assertContains('INFO: Warmed up page:', $cloudLog);
        $this->assertContains('NOTICE: Post-deploy is complete.', $cloudLog);
    }

    public function testPostDeployIsNotRun()
    {
        $application = $this->bootstrap->createApplication(['variables' => []]);

        /** @var File $file */
        $file = $application->getContainer()->get(File::class);
        $file->copy(
            sprintf('%s/_files/.magento.env.yaml.scdondemand', __DIR__),
            sprintf('%s/.magento.env.yaml', $this->bootstrap->getSandboxDir())
        );

        $this->assertSame(0, $this->execute(Build::NAME, $application));
        try {
            $this->assertSame(1, $this->execute(Deploy::NAME, $application));
        } catch (\Exception $e) {
            $this->assertContains('Fix configuration with given suggestions', $e->getMessage());
        }
        $this->assertSame(0, $this->execute(PostDeploy::NAME, $application));

        /** @var FileList $fileList */
        $fileList = $application->getContainer()->get(FileList::class);
        $cloudLog = file_get_contents($fileList->getCloudLog());

        $this->assertContains('Post-deploy is skipped because deploy was failed.', $cloudLog);
        $this->assertNotContains('NOTICE: Starting post-deploy.', $cloudLog);
        $this->assertNotContains('INFO: Warmed up page:', $cloudLog);
        $this->assertNotContains('NOTICE: Post-deploy is complete.', $cloudLog);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->bootstrap->execute(sprintf(
            'cd %s && rm -rf var/log/*',
            $this->bootstrap->getSandboxDir()
        ));
    }
}
