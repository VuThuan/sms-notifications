<?php
/**
 * LINK Mobility SMS Notifications
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package Linkmobility\Notifications\Test\Integration\Model
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) LINK Mobility (https://www.linkmobility.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */
declare(strict_types=1);

namespace Linkmobility\Notifications\Test\Integration\Model;

use Linkmobility\Notifications\Gateway\ApiClientInterface;
use Linkmobility\Notifications\Gateway\Entity\SuccessResult;
use Linkmobility\Notifications\Model\MessageService;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Message Service Test
 *
 * @package Linkmobility\Notifications\Test\Integration\Model
 * @author Joseph Leedy <joseph@wagento.com>
 */
class MessageServiceTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;
    /**
     * @var \Linkmobility\Notifications\Model\MessageService
     */
    private $messageService;

    /**
     * @magentoConfigFixture default/sms_notifications/api/source_number +15555551234
     * @magentoConfigFixture default/general/store_information/name Example Store
     */
    public function testSendMessage()
    {
        $this->messageService->setOrder($this->getOrderMock());

        $message = 'Order #{{order_id}} has been placed at {{store_name}} by {{customer_name}}. View order: {{order_url}}';

        $this->assertTrue($this->messageService->sendMessage($message, '+15555555678', 'order'));
    }

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->messageService = $this->objectManager->create(
            MessageService::class,
            [
                'logger' => new \Psr\Log\Test\TestLogger(),
                'apiClient' => $this->getApiClientMock(),
            ]
        );
    }

    private function getApiClientMock(): MockObject
    {
        $apiClientMock = $this->getMockBuilder(ApiClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = new SuccessResult([
            'messageId' => 'ABC123',
            'resultCode' => 0,
            'description' => 'OK'
        ]);

        $apiClientMock->method('getResult')->willReturn($result);

        return $apiClientMock;
    }

    private function getOrderMock(): MockObject
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderMock->method('getId')->willReturn('1');
        $orderMock->method('getEntityId')->willReturn('1');
        $orderMock->method('getIncrementId')->willReturn('ORD1000001');
        $orderMock->method('getCustomerFirstname')->willReturn('John');
        $orderMock->method('getCustomerLastname')->willReturn('Smith');
        $orderMock->method('getStoreId')->willReturn('1');
        $orderMock->method('getStoreName')->willReturn("Main Website\nMain Website Store\n");
        $orderMock->method('getProtectCode')->willReturn('ABCDEF123456');

        return $orderMock;
    }
}
