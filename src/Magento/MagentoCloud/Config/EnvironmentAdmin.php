<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Util\PasswordGenerator;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;

/**
 * Contains logic for interacting with the server environment
 */
class EnvironmentAdmin
{

   /**
    * @var LoggerInterface
    */
    private $logger;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @var Environment
     */
    private $environment;



    /**
     * @param LoggerInterface $logger
     * @param DeployConfig $deployConfig
     * @param PasswordGenerator $passwordGenerator
     * @param Environment $environment
     */
    public function __construct(LoggerInterface $logger, DeployConfig $deployConfig, PasswordGenerator $passwordGenerator, Environment $environment)
    {
        $this->logger = $logger;
        $this->deployConfig = $deployConfig;
        $this->passwordGenerator = $passwordGenerator;
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getAdminLocale(): string
    {
        return $this->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @return string
     */
    public function getAdminUsername(): string
    {
        $var = $this->getVariables();
        if (!empty($var['ADMIN_USERNAME'])) {
            return $var['ADMIN_USERNAME'];
        }
        if (!$this->deployConfig->isInstalling()) {
            return "";
        }
        // TODO: We want to have a random username , but because the username is not sent in the reset password email, the new admin has no way of knowing what it is at the moment.
        //       We may either make a custom email template to do this, or find a different way to do this.  Then, we can use random a username.
        // return "admin-" . Password::generateRandomString(6);
        return "admin";
    }

    /**
     * @return string
     */
    public function getAdminFirstname(): string
    {
        $var = $this->getVariables();
        return !empty($var["ADMIN_FIRSTNAME"]) ? $var["ADMIN_FIRSTNAME"] : ($this->deployConfig->isInstalling() ? "Changeme" : "");
    }

    /**
     * @return string
     */
    public function getAdminLastname(): string
    {
        $var = $this->getVariables();
        return !empty($var["ADMIN_LASTNAME"]) ? $var["ADMIN_LASTNAME"] : ($this->deployConfig->isInstalling() ? "Changeme" : "");
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        $var = $this->getVariables();
        /*   Note: We are going to have the onboarding process set the ADMIN_EMAIL variables to the email address specified during
         * the project creation.  This will let us do the reset password for the new installs. */
        if (!empty($var["ADMIN_EMAIL"])) {
            return $var["ADMIN_EMAIL"];
        }
        if ($this->deployConfig->isInstalling() /* && empty($var["ADMIN_PASSWORD"])*/) {
            // Note: I didn't want to throw exception here if ADMIN_PASSWORD is set... but bin/magento setup:install fails if --admin-email is blank, so it's better to die with a useful error message
            // Note: not relying on bin/magento because it might not be working at this point.
            //    $this->env->execute('touch ' . realpath(Environment::MAGENTO_ROOT . 'var') . '/.maintenance.flag');
            $this->logger->error("ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email.  Please set ADMIN_EMAIL and retry deploy.");
            throw new \RuntimeException("ADMIN_EMAIL not set during install!  We need this variable set to send the password reset email.  Please set ADMIN_EMAIL and retry deploy.");
        }
        return "";
    }

    private $adminPassword = null;  // Note: If we are generating a random password, we need to cache it so we don't return a new random one each time.

    /**
     * @return string
     */
    public function getAdminPassword(): string
    {
        if (is_null($this->adminPassword)) {
            $var = $this->getVariables();
            if (!empty($var['ADMIN_PASSWORD'])) {
                $this->adminPassword = $var['ADMIN_PASSWORD'];
            } else {
                if (!$this->deployConfig->isInstalling()) {
                    $this->adminPassword = "";
                } else {
                    $this->adminPassword = $this->passwordGenerator->generateRandomPassword();
                }
            }
        }
        return $this->adminPassword;
    }

    /**
     * @return string
     */
    public function getAdminUrl(): string
    {
        $var = $this->getVariables();
        /* Note: ADMIN_URL should be set during the onboarding process also.  They should have generated a random one for us to use. */
        //$this->adminUrl = isset($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->isInstalling ? "admin_" . Password::generateRandomString(8) : "");
        /* Note: We are defaulting to "admin" for now, but will change it to the above random admin URL at some point */
        return !empty($var["ADMIN_URL"]) ? $var["ADMIN_URL"] : ($this->deployConfig->isInstalling() ? "admin" : "");
    }

   /**
    * Get custom variables from MagentoCloud environment variable.
    *
    * @return mixed
    */
    private function getVariables()
    {
        return $this->environment->getVariables();
    }
}
