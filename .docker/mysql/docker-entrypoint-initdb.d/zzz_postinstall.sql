UPDATE core_config_data SET value = 'http://local.magento.com/' where path in ('web/unsecure/base_url','web/unsecure/base_link_url' );
UPDATE core_config_data SET value = 'https://local.magento.com/' where path in ('web/secure/base_link_url','web/secure/base_url');
UPDATE core_config_data SET value = '/' where path in ('web/cookie/cookie_path');
UPDATE core_config_data SET value = '.magento.com' where path in ('web/cookie/cookie_domain');
truncate cron_schedule;
