<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Magento\MagentoCloud\App\GenericException;

/**
 * Factory of remote storage adapters.
 */
class RemoteStorageFactory
{
    public const ADAPTER_AWS_S3 = 'aws-s3';

    /**
     * @param string $adapter
     * @param array $config
     * @param string $prefix
     * @return AdapterInterface
     * @throws GenericException
     */
    public function create(
        string $adapter,
        array $config,
        string $prefix = ''
    ): AdapterInterface {
        switch ($adapter) {
            case self::ADAPTER_AWS_S3:
                if (!isset($config['region'])) {
                    throw new GenericException('Missing required adapter parameter "region"');
                }

                if (!isset($config['bucket'])) {
                    throw new GenericException('Missing required adapter parameter "bucket"');
                }

                $preparedConfig = [
                    'region' => $config['region'],
                    'version' => 'latest'
                ];

                if (isset($config['key'], $config['secret'])) {
                    $config['credentials'] = [
                        'key' => $config['key'],
                        'secret' => $config['secret']
                    ];
                }

                return new AwsS3Adapter(new S3Client($preparedConfig), $config['bucket'], $prefix);
            default:
                throw new GenericException(sprintf('Adapter "%s" is not supported', $adapter));
        }
    }
}
