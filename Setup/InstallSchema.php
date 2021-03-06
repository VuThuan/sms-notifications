<?php
/**
 * Wagento SMS Notifications powered by LINK Mobility
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package Wagento\SMSNotifications\Setup
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) Wagento (https://wagento.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace Wagento\SMSNotifications\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Database Schema Installer
 *
 * @package Wagento\SMSNotifications\Setup
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @author Joseph Leedy <joseph@wagento.com>
 *
 * @codeCoverageIgnore
 * @phpcs:disable Magento2.PHP.FinalImplementation.FoundFinal -- There is no valid use case for extending an installer.
 */
final class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $smsSubscriptionTable = $setup->getConnection()
            ->newTable($setup->getTable('sms_subscription'))
            ->addColumn(
                'sms_subscription_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'SMS Subscription ID'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Customer ID'
            )->addColumn(
                'sms_type',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'SMS Type Code (e.g. "order_placed")'
            )->addForeignKey(
                $setup->getFkName('sms_subscription', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $setup->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE
            )->setComment('SMS Notification Subscriptions');
        $countryPhonePrefixTable = $setup->getConnection()
            ->newTable($setup->getTable('directory_telephone_prefix'))
            ->addColumn(
                'country_code',
                Table::TYPE_TEXT,
                2,
                ['nullable' => false, 'primary' => true, 'default' => false],
                'Country Code, in ISO-2 Format'
            )->addColumn(
                'country_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => false],
                'Country Name'
            )->addColumn(
                'prefix',
                Table::TYPE_SMALLINT,
                3,
                ['nullable' => false, 'unsigned' => true],
                'Numeric Telephone Prefix'
            )->addIndex(
                $setup->getIdxName(
                    'directory_telephone_prefix',
                    ['country_code'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['country_code'],
                ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
            )->setComment('Telephone Prefix Directory for SMS Notifications');

        $setup->getConnection()->createTable($smsSubscriptionTable);
        $setup->getConnection()->createTable($countryPhonePrefixTable);
    }
}
