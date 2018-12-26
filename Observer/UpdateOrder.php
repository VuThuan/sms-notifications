<?php
namespace Linkmobility\Notifications\Observer;


use Linkmobility\Notifications\Api\ConfigInterface;
use Linkmobility\Notifications\Logger\Logger;
use Linkmobility\Notifications\Model\Api\Sms\Send;

class UpdateOrder  implements \Magento\Framework\Event\ObserverInterface
{

    protected $logger;
    protected $sender;

    /**
     * @var \Linkmobility\Notifications\Api\ConfigInterface
     */
    private $config;

    public function __construct(
        Logger $logger,
        ConfigInterface $config,
        Send $sender
    ) {
        $this->logger = $logger;
        $this->sender = $sender;
        $this->config = $config;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        if ($order->getStatus() !== "canceled") {
            $address = ($order->getShippingAddress() ? : $order->getBillingAddress());
            $telephone = ($address ? $address->getTelephone() : null);
            $this->sender
                ->setSource(
                    $this->config->getSourceNumber()
                )
                ->setDestination($telephone)
                ->setUserData(
                    $this->config->getOrderUpdatedTpl()
                );
            $this->logger->info('Linkmobility: preparing request');
            try {
                $response = $this->sender->execute();
                $this->logger->info('Linkmobility: response received');
                $this->logger->info(print_r($response, true));
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
        }

        return $this;
    }
}