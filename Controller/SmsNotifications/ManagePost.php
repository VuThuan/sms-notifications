<?php
/**
 * LINK Mobility SMS Notifications
 *
 * Sends transactional SMS notifications through the LINK Mobility messaging
 * service.
 *
 * @package LinkMobility\SMSNotifications\Controller\SmsNotifications
 * @author Joseph Leedy <joseph@wagento.com>
 * @author Yair García Torres <yair.garcia@wagento.com>
 * @copyright Copyright (c) LINK Mobility (https://www.linkmobility.com/)
 * @license https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace LinkMobility\SMSNotifications\Controller\SmsNotifications;

use LinkMobility\SMSNotifications\Api\SmsSubscriptionManagementInterface;
use LinkMobility\SMSNotifications\Api\SmsSubscriptionRepositoryInterface;
use LinkMobility\SMSNotifications\Model\SmsSender\WelcomeSender as WelcomeSmsSender;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Psr\Log\LoggerInterface;

/**
 * Manage SMS Subscriptions POST Action Controller
 *
 * @package LinkMobility\SMSNotifications\Controller\SmsNotifications
 * @author Joseph Leedy <joseph@wagento.com>
 */
class ManagePost extends Action implements ActionInterface, CsrfAwareActionInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var \LinkMobility\SMSNotifications\Api\SmsSubscriptionRepositoryInterface
     */
    private $smsSubscriptionRepository;
    /**
     * @var \LinkMobility\SMSNotifications\Api\SmsSubscriptionManagementInterface
     */
    private $smsSubscriptionManagement;
    /**
     * @var \LinkMobility\SMSNotifications\Model\SmsSender\WelcomeSender
     */
    private $welcomeSmsSender;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerFactory,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SmsSubscriptionRepositoryInterface $smsSubscriptionRepository,
        SmsSubscriptionManagementInterface $smsSubscriptionManagement,
        WelcomeSmsSender $welcomeSmsSender
    ) {
        parent::__construct($context);

        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->smsSubscriptionRepository = $smsSubscriptionRepository;
        $this->smsSubscriptionManagement = $smsSubscriptionManagement;
        $this->welcomeSmsSender = $welcomeSmsSender;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerId = $this->customerSession->getCustomerId();
        $selectedSmsTypes = array_keys($this->getRequest()->getParam('sms_types', []));

        $resultRedirect->setPath('*/*/manage');

        if ($customerId === null) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while saving your text notification preferences.')
            );
            $this->logger->critical(__('Could not get ID of customer to save SMS preferences for.'));

            return $resultRedirect;
        }

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('customer_id', $customerId)->create();
        $subscribedSmsTypes = $this->smsSubscriptionRepository->getList($searchCriteria)->getItems();

        if (count($subscribedSmsTypes) > 0) {
            $this->removeSubscriptions($subscribedSmsTypes, $selectedSmsTypes, $customerId);

            $selectedSmsTypes = array_diff($selectedSmsTypes, array_column($subscribedSmsTypes, 'sms_type'));
        }

        if (count($selectedSmsTypes) > 0) {
            $this->createSubscriptions($selectedSmsTypes, $customerId);
        }

        $this->updateMobileTelephoneNumber();

        return $resultRedirect;
   }

    /**
     * {@inheritdoc}
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $resultRedirect->setPath('*/*/manage');

        return new InvalidRequestException($resultRedirect, [__('Invalid Form Key. Please refresh the page.')]);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * @param \LinkMobility\SMSNotifications\Model\SmsSubscription[] $subscribedSmsTypes
     * @param string[] $selectedSmsTypes
     * @param string|int $customerId
     */
    private function removeSubscriptions(array &$subscribedSmsTypes, array $selectedSmsTypes, $customerId): int
    {
        $messages = [
            'error' => [
                'one' => 'You could not be unsubscribed from 1 text notification.',
                'multiple' => 'You could not be unsubscribed from %1 text notifications.'
            ],
            'success' => [
                'one' => 'You have been unsubscribed from 1 text notification.',
                'multiple' => 'You have been unsubscribed from %1 text notifications.'
            ]
        ];

        return $this->smsSubscriptionManagement->removeSubscriptions(
            $subscribedSmsTypes,
            $selectedSmsTypes,
            (int)$customerId,
            $messages
        );
    }

    /**
     * @param string[] $selectedSmsTypes
     * @param string|int $customerId
     */
    private function createSubscriptions(array $selectedSmsTypes, $customerId): int
    {
        $messages = [
            'error' => [
                'one' => 'You could not be subscribed to 1 text notification.',
                'multiple' => 'You could not be subscribed to %1 text notifications.'
            ],
            'success' => [
                'one' => 'You have been subscribed to 1 text notification.',
                'multiple' => 'You have been subscribed to %1 text notifications.'
            ]
        ];

        return $this->smsSubscriptionManagement->createSubscriptions($selectedSmsTypes, (int)$customerId, $messages);
    }

    private function updateMobileTelephoneNumber(): void
    {
        $newMobileTelephonePrefix = $this->getRequest()->getParam('sms_mobile_phone_prefix', '');
        $newMobileTelephoneNumber = $this->getRequest()->getParam('sms_mobile_phone_number', '');
        $customer = $this->customerSession->getCustomerDataObject();
        $mobilePhonePrefixAttribute = $customer->getCustomAttribute('sms_mobile_phone_prefix');
        $mobilePhoneNumberAttribute = $customer->getCustomAttribute('sms_mobile_phone_number');
        $haveChanges = false;

        if (!empty($newMobileTelephonePrefix) && empty($newMobileTelephoneNumber)) {
            return;
        }

        if ($mobilePhonePrefixAttribute !== null) {
            $existingMobileTelephonePrefix = $mobilePhonePrefixAttribute->getValue() ?? '';
        } else {
            $existingMobileTelephonePrefix = '';
        }

        if ($mobilePhoneNumberAttribute !== null) {
            $existingMobileTelephoneNumber = $mobilePhoneNumberAttribute->getValue() ?? '';
        } else {
            $existingMobileTelephoneNumber = '';
        }

        if ($existingMobileTelephonePrefix !== $newMobileTelephonePrefix) {
            $customer->setCustomAttribute('sms_mobile_phone_prefix', $newMobileTelephonePrefix);

            $haveChanges = true;
        }

        if ($existingMobileTelephoneNumber !== $newMobileTelephoneNumber) {
            $customer->setCustomAttribute('sms_mobile_phone_number', $newMobileTelephoneNumber);

            $haveChanges = true;
        }

        if (!$haveChanges) {
            return;
        }

        try {
            $this->customerRepository->save($customer);
        } catch (InputException | InputMismatchException | LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Your mobile telephone number could not be updated.'));
            $this->logger->critical(
                __('Could not save mobile telephone number. Error: %1', $e->getMessage()),
                [
                    'customer_id' => $customer->getId(),
                    'mobile_phone_prefix' => $newMobileTelephonePrefix,
                    'mobile_phone_number' => $newMobileTelephoneNumber
                ]
            );

            return;
        }

        $this->messageManager->addSuccessMessage(__('Your mobile telephone number has been updated.'));

        if ($existingMobileTelephonePrefix === '' && $existingMobileTelephoneNumber === '') {
            $this->sendWelcomeMessage($customer);
        }
    }

    private function sendWelcomeMessage(CustomerInterface $customer): bool
    {
        /** @var \Magento\Customer\Model\Customer $customerModel */
        $customerModel = $this->customerFactory->create();

        $customerModel->updateData($customer);

        return $this->welcomeSmsSender->send($customerModel);
    }
}
