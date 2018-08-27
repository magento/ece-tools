<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\DB\ConnectionInterface;

/**
 * @inheritdoc
 */
class AdminCredentials implements ValidatorInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param State $state
     * @param ConnectionInterface $connection
     * @param Environment $environment
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        State $state,
        ConnectionInterface $connection,
        Environment $environment
    ) {
        $this->resultFactory = $resultFactory;
        $this->state = $state;
        $this->connection = $connection;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->state->isInstalled()) {
            return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
        }

        $adminEmail = $this->environment->getAdminEmail();
        $adminUsername = $this->environment->getAdminUsername();

        $isEmailUsed = $adminEmail && $this->isEmailUsed($adminEmail);
        $isUsernameUsed = $adminUsername && $this->isUsernameUsed($adminUsername);

        if (!$isEmailUsed && !$isUsernameUsed) {
            return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
        }

        $storedData = $this->getStoredData();

        if ($isEmailUsed && $adminEmail !== $storedData['email']) {
            return $this->resultFactory->create(
                Validator\Result\Error::ERROR,
                [
                    'error' => 'The same email is already used by different admin',
                    'suggestion' => 'Use different email address',
                ]
            );
        }

        if ($isUsernameUsed && $adminUsername !== $storedData['username']) {
            return $this->resultFactory->create(
                Validator\Result\Error::ERROR,
                [
                    'error' => 'The same username is already used by different admin',
                    'suggestion' => 'Use different username',
                ]
            );
        }

        return $this->resultFactory->create(Validator\Result\Success::SUCCESS);
    }

    /**
     * @param string $email
     * @return bool
     */
    private function isEmailUsed(string $email): bool
    {
        return $this->connection->count('SELECT 1 FROM `admin_user` WHERE `email` = ?', [$email]) > 0;
    }

    /**
     * @param string $username
     * @return bool
     */
    private function isUsernameUsed(string $username): bool
    {
        return $this->connection->count('SELECT 1 FROM `admin_user` WHERE `username` = ?', [$username]) > 0;
    }

    /**
     * Retrieves admin data of the first user as the only, which must be created by
     * this scripts.
     *
     * @return array
     */
    private function getStoredData(): array
    {
        return $this->connection->selectOne(
            'SELECT `email`, `username` FROM `admin_user` ORDER BY `user_id` ASC LIMIT 1'
        );
    }
}
