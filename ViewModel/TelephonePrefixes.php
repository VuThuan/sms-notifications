<?php
/**
 * LINK Mobility SMS Notifications
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package Linkmobility\Notifications\ViewModel
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) LINK Mobility (https://www.linkmobility.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace Linkmobility\Notifications\ViewModel;

use Linkmobility\Notifications\Model\Source\TelephonePrefix as TelephonePrefixSource;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Telephone Prefixes View Model
 *
 * @package Linkmobility\Notifications\ViewModel
 * @author Joseph Leedy <joseph@wagento.com>
 */
class TelephonePrefixes implements ArgumentInterface
{
    /**
     * @var \Linkmobility\Notifications\Model\Source\TelephonePrefix
     */
    private $telephonePrefixSource;

    public function __construct(TelephonePrefixSource $telephonePrefixSource)
    {
        $this->telephonePrefixSource = $telephonePrefixSource;
    }

    public function getOptions(): array
    {
        return $this->telephonePrefixSource->toOptionArray();
    }
}
