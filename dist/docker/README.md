# Local docker environment configuration

## Services

To propagate service configuration, add it to `MAGENTO_CLOUD_RELATIONSHIPS` section.

### Elasticsearch

```php
'elasticsearch' => [
    [
        'host' => 'elasticsearch',
        'port' => '9200',
    ],
],
```

### RabbitMQ

```php
'rabbitmq' => [
    [
        'host' => 'rabbitmq',
        'port' => '5672',
        'username' => 'guest',
        'password' => 'guest',
    ]
],
```
