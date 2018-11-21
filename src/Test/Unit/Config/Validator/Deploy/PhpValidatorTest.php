<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config\Validator\Deploy;

use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\Constraint;
use JsonSchema\Constraints\ConstraintInterface;
use Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion;
use PHPUnit\Framework\TestCase;
use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\MultiConstraint;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\Manager as PackageManager;
use Magento\MagentoCloud\Config\Validator\Deploy\PhpVersion\ConstraintFactory;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\ResultFactory;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PhpValidatorTest extends TestCase
{
    /**
     * @var PhpVersion
     */
    private $validator;

    /**
     * @var Validator\ResultFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var PackageManager|MockObject
     */
    private $packageManagerMock;

    /**
     * @var VersionParser|MockObject
     */
    private $versionParserMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var ConstraintFactory|MockObject
     */
    private $constraintFactoryMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var Link|MockObject
     */
    private $linkMock;

    /**
     * @var Constraint|MockObject
     */
    private $currentPhpConstraint;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->currentPhpConstraint = $this->createMock(Constraint::class);
        $this->packageManagerMock = $this->createMock(PackageManager::class);
        $this->versionParserMock = $this->createMock(VersionParser::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->constraintFactoryMock = $this->createMock(ConstraintFactory::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->linkMock = $this->createMock(Link::class);
        $this->resultFactoryMock = $this->createConfiguredMock(ResultFactory::class, [
            'success' => $this->createMock(Success::class),
            'error' => $this->createMock(Error::class)
        ]);
        $packageMock = $this->getMockForAbstractClass(PackageInterface::class);
        $this->constraintFactoryMock->expects($this->once())
            ->method('getCurrentPhpConstraint')
            ->willReturn($this->currentPhpConstraint);
        $this->packageManagerMock->expects($this->once())
            ->method('get')
            ->with('magento/magento2-base')
            ->willReturn($packageMock);
        $packageMock->expects($this->once())
            ->method('getRequires')
            ->willReturn(['php' => $this->linkMock]);

        $this->validator = new PhpVersion(
            $this->resultFactoryMock,
            $this->packageManagerMock,
            $this->versionParserMock,
            $this->magentoVersionMock,
            $this->constraintFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Php package of composer has many constraints
     */
    public function testValidatePhpPackageOfComposerHasManyConstraints()
    {
        $recommendedPhpConstraints = [
            '7.1.0' => $this->createConfiguredMock(Constraint::class, ['getPrettyString' => '>= 7.1.0.0-dev']),
            '7.2.0' => $this->createConfiguredMock(Constraint::class, ['getPrettyString' => '< 7.2.0.0-dev'])
        ];
        /** @var ConstraintInterface|MockObject $recommendedPhpConstraint */
        $recommendedPhpConstraint = $this->createMock(MultiConstraint::class);
        /** @var ConstraintInterface|MockObject $composerPhpConstraint */
        $composerPhpConstraint = $this->createMock(MultiConstraint::class);
        $this->linkMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn($composerPhpConstraint);

        $composerPhpConstraint->expects($this->once())
            ->method('getConstraints')
            ->willReturn([
                $this->createConfiguredMock(MultiConstraint::class, [
                    'getConstraints' => [
                        $this->createConfiguredMock(Constraint::class, [
                            'getPrettyString' => '>= 5.6.5.0-dev'
                        ]),
                        $this->createConfiguredMock(Constraint::class, [
                            'getPrettyString' => '< 5.7.0.0-dev'
                        ]),
                    ]
                ]),
                $this->createConfiguredMock(Constraint::class, ['getPrettyString' => '== 7.0.2.0']),
                $this->createConfiguredMock(Constraint::class, ['getPrettyString' => '== 7.0.4.0']),
                $this->createConfiguredMock(MultiConstraint::class, [
                    'getConstraints' => [
                        $this->createConfiguredMock(Constraint::class, [
                            'getPrettyString' => '>= 7.0.6.0-dev'
                        ]),
                        $this->createConfiguredMock(Constraint::class, [
                            'getPrettyString' => '< 7.1.0.0-dev'
                        ]),
                    ]
                ]),
                $this->createConfiguredMock(MultiConstraint::class, [
                    'getConstraints' => array_values($recommendedPhpConstraints)
                ])
            ]);

        $this->constraintFactoryMock->expects($this->once())
            ->method('multiconstraint')
            ->with($recommendedPhpConstraints)
            ->willReturn($recommendedPhpConstraint);
        $recommendedPhpConstraint->expects($this->once())
            ->method('matches')
            ->with($this->currentPhpConstraint)
            ->willReturn(true);
        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * Php package of composer has one constraint
     */
    public function testValidatePhpPackageOfComposerHasOneConstraint()
    {
        /** @var ConstraintInterface|MockObject $composerPhpConstraint */
        $composerPhpConstraint = $this->createMock(Constraint::class);
        $this->linkMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn($composerPhpConstraint);
        $composerPhpConstraint->expects($this->once())
            ->method('matches')
            ->with($this->currentPhpConstraint)
            ->willReturn(true);
        $this->assertInstanceOf(Success::class, $this->validator->validate());
    }

    /**
     * Current and recommended php constraints do not match
     */
    public function testValidateCurrentAndRecommendedPhpConstraintsDoNotMatch()
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('getVersion')
            ->willReturn('2.x.x.x');
        /** @var ConstraintInterface|MockObject $composerPhpConstraint */
        $composerPhpConstraint = $this->createMock(Constraint::class);
        $composerPhpConstraint->expects($this->once())
            ->method('getPrettyString')
            ->willReturn('== 7.1.0.0');
        $this->currentPhpConstraint->expects($this->once())
            ->method('getPrettyString')
            ->willReturn('== 7.0.2.0');
        $this->linkMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn($composerPhpConstraint);
        $composerPhpConstraint->expects($this->once())
            ->method('matches')
            ->with($this->currentPhpConstraint)
            ->willReturn(false);
        $this->resultFactoryMock->expects($this->once())
            ->method('error')
            ->with(
                'For Magento 2.x.x.x recommended PHP version satisfying the constraint == 7.1.0.0. '
                . 'Currently installed PHP version == 7.0.2.0',
                "Change the version of PHP to the version that satisfies the restriction conditions.\n"
                . 'Change the PHP version in the .magento.app.yaml file or create a support ticket'
            );
        $this->assertInstanceOf(Error::class, $this->validator->validate());
    }

    /**
     * With exception
     */
    public function testValidateWithException()
    {
        /** @var ConstraintInterface|MockObject $composerPhpConstraint */
        $composerPhpConstraint = $this->createMock(Constraint::class);
        $this->linkMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn($composerPhpConstraint);
        $composerPhpConstraint->expects($this->once())
            ->method('matches')
            ->with($this->currentPhpConstraint)
            ->willThrowException(new \Exception('Some message'));
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Can\'t validate version of PHP: Some message');
        $this->validator->validate();
    }
}
