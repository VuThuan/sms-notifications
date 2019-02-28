<?php
/**
 * LINK Mobility SMS Notifications
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package LinkMobility\SMSNotifications\Block\System\Config\Form\Fieldset
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) LINK Mobility (https://www.linkmobility.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace LinkMobility\SMSNotifications\Block\System\Config\Form\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Template;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ResourceInterface as ModuleResource;
use Magento\Framework\View\Helper\Js;

/**
 * Extension Information Configuration Fieldset Block
 *
 * @package LinkMobility\SMSNotifications\Block\System\Config\Form\Fieldset
 * @author Joseph Leedy <joseph@wagento.com>
 */
class Info extends Fieldset
{
    private const TEMPLATE = 'LinkMobility_SMSNotifications::system/config/form/fieldset/info.phtml';

    /**
     * @var \Magento\Framework\Module\ResourceInterface
     */
    private $moduleResource;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        ModuleResource $moduleResource,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);

        $this->moduleResource = $moduleResource;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(AbstractElement $element)
    {
        $group = $element->getGroup() ?? [];
        $block = $this->getLayout()
            ->createBlock(Template::class, 'linkmobility_sms_notifications_config_header')
            ->setTemplate(self::TEMPLATE)
            ->setData([
                'info_text' => $element->getComment() ?? '',
                'documentation_url' => $group['help_url'] ?: '#',
                'overview_url' => $group['more_url'] ?: '#',
                'signup_url' => $group['demo_link'] ?: '#',
                'module_version' => $this->moduleResource->getDbVersion('LinkMobility_SMSNotifications') ?: ''
            ]);

        return $block->_toHtml();
    }
}