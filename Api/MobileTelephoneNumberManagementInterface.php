<?php
/**
 * Wagento SMS Notifications powered by LINK Mobility
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package Wagento\SMSNotifications\Api
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) Wagento (https://wagento.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace Wagento\SMSNotifications\Api;

use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Mobile Telephone Number Management Service Interface
 *
 * @package Wagento\SMSNotifications\Api
 * @author Joseph Leedy <joseph@wagento.com>
 * @api
 */
interface MobileTelephoneNumberManagementInterface
{
    public function updateNumber(string $newPrefix, string $newNumber, CustomerInterface $customer): ?bool;
}
