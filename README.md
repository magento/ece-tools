# Magento Commerce (Cloud) Deployment Tools
[![Build Status](https://travis-ci.com/magento/ece-tools.svg?token=NW7M5gDP5YaRMZyCvYpY&branch=develop)](https://travis-ci.com/magento/ece-tools)

## Welcome
ECE-Tools is a set of scripts and tools designed to manage and deploy Cloud projects. The Cloud tools package is compatible with Magento version 2.1.4 and later to provide a rich set of features you can use to manage your Magento Commerce project.

## Contributing to ECE-Tools Code Base
You can submit issues and pull requests to extend functionality or fix potential bugs. Improvements to ECE-Tools can include any development experience improvements, optimizations for deployment process, etc. If you find a bug or have a new suggestion, let us know by creating a Github issue.

*Please note:* this repository is not an official support channel. To get project-specific help, please create support ticket through [Support Portal](https://support.magento.com). Support-related issues will be closed with the request to open a support ticket.

## Magento Cloud Module for Core
The ece-tools  package uses extended core functionality that is provided by the [Magento Cloud Components](https://github.com/magento/magento-cloud-components) module. Starting with ece-tools `2002.0.20`, this module is required to support some advanced features, such as cache warm-up using regex. The [Magento Cloud Components](https://github.com/magento/magento-cloud-components) module is installed automatically when you upgrade the ece-tools package to `2002.0.20` or later.

## Magento Cloud Docker for Local Development and CICD
The ece-tools package uses images that are generated from code in the [Magento Cloud Docker](https://github.com/magento/magento-cloud-docker) repository. Magento maintains a list of images based on the list of service versions available for Magento Commerce. These images are used for building your Docker environment using the ece-tools package.

## Useful Resources
- [Release Notes](https://github.com/magento/ece-tools/releases)
- [Cloud DevDocs](https://devdocs.magento.com/guides/v2.2/cloud/bk-cloud.html)
- [Cloud Knowledge Base and Support](https://support.magento.com)
- [Cloud Slack Channel](https://magentocommeng.slack.com) (join #cloud and #cloud-docker)

## License
Each Magento source file included in this distribution is licensed under OSL-3.0 license.

Please see [LICENSE.txt](https://github.com/magento/ece-tools/blob/develop/LICENSE.txt) for the full text of the [Open Software License v. 3.0 (OSL-3.0)](http://opensource.org/licenses/osl-3.0.php).
