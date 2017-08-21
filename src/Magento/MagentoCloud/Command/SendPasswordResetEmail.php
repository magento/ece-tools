<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Bootstrap;

/**
 * CLI command for deploy sending the password reset email. We use this to help the admin user set their password.
 */
class SendPasswordResetEmail extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bootstrap = Bootstrap::create(BP, []);
        $objectManager = $bootstrap->getObjectManager();
        $state = $objectManager->get('Magento\Framework\App\State');
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $collection = $objectManager->get('Magento\User\Model\ResourceModel\User\Collection');
        /** @var $collection \Magento\User\Model\ResourceModel\User\Collection */
        $collection->addFieldToFilter('user_id', 1);  // Note: the user_id is hard-coded to 1.  TODO: ??
        $collection->load(false);
        if ($collection->getSize() > 0) {
            $userfactory = $objectManager->get('\Magento\User\Model\UserFactory');
            /** @var $userfactory \Magento\User\Model\UserFactory */
            foreach ($collection as $item) {
                /** @var \Magento\User\Model\User $user */
                $user = $userfactory->create()->load($item->getId());
                if ($user->getId()) {
                    $newPassResetToken = $objectManager->get(
                        'Magento\User\Helper\Data'
                    )->generateResetPasswordLinkToken();
                    $user->changeResetPasswordLinkToken($newPassResetToken);
                    $user->save();
                    $temp = $_SERVER['SCRIPT_FILENAME'];
                    $_SERVER['SCRIPT_FILENAME'] = "";  // TODO: FIXME: This is a workaround to fix the URL in the email.
                    $user->sendPasswordResetConfirmationEmail();
                    $_SERVER['SCRIPT_FILENAME'] = $temp;
                }
                break;
            }
        } else {
            throw new \RuntimeException("Couldn't find admin user!");
        }
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('magento-cloud:send-password-reset-email')
            ->setDescription('Sends the password reset email to the admin user.');
        parent::configure();
    }
}
