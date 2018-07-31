<?php

namespace Cardinity\Magento\Model;

use Cardinity\Client;
use Cardinity\Exception;
use Cardinity\Method\MethodInterface;
use Cardinity\Method\Payment;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Phrase;

class PaymentModel extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'cardinity';

    protected $_code = self::CODE;

    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded = true;

    protected $_minAmount = 0.5;
    protected $_supportedCurrencyCodes = ['EUR', 'USD', 'GBP'];

    /**
     * @var $_client Cardinity\Client SDK client
     */
    private $_client;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );

        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_storeManager = $storeManager;

        /**
         * Creates Cardinity SDK client
         */
        $this->_client = Client::create([
            'consumerKey' => $this->getConfigData('cardinity_key'),
            'consumerSecret' => $this->getConfigData('cardinity_secret'),
        ]);
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return $this
     */
    public function initialize($paymentAction, $stateObject)
    {
        parent::initialize($paymentAction, $stateObject);

        if ($paymentAction != 'sale') {
            $this->_log('Wrong payment action. Only sale is allowed.');
            throw new PaymentException(new Phrase(__('Request failed. Please contact support.')));
        }

        $orderModel = $this->_getOrderModel();
        $stateObject->setState($orderModel::STATE_PENDING_PAYMENT);
        $stateObject->setStatus($orderModel::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);

        try {
            $this->_makePayment();
        } catch (Exception $e) {
            $this->_log($e->getMessage());
            throw new PaymentException(new Phrase(__('Internal error occurred. Please contact support.')));
        }

        return $this;
    }

    protected function _makePayment()
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->save();

        $amount = $order->getTotalDue();
        if ($amount < $this->_minAmount) {
            throw new PaymentException(new Phrase(__('Invalid order amount. Minimum amount: 0.50!')));
        }

        $holder = mb_substr(sprintf(
            '%s %s',
            $order->getBillingAddress()->getData('firstname'),
            $order->getBillingAddress()->getData('lastname')
        ), 0, 32);

        $method = new Payment\Create([
            'amount' => floatval($amount),
            'currency' => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
            'settle' => true,
            'order_id' => $order->getRealOrderId(),
            'country' => $order->getBillingAddress()->getData('country_id'),
            'payment_method' => Payment\Create::CARD,
            'payment_instrument' => [
                'pan' => $payment->_data['cc_number'],
                'exp_year' => (int)$payment->_data['cc_exp_year'],
                'exp_month' => (int)$payment->_data['cc_exp_month'],
                'cvc' => $payment->_data['cc_cid'],
                'holder' => $holder
            ],
        ]);

        $result = $this->_call($method);

        $authModel = $this->_getAuthModel();
        $authModel->cleanup();

        if ($result) {
            $authModel->setOrderId($order->getId());
            $authModel->setRealOrderId($order->getRealOrderId());
            $authModel->setPaymentId($result->getId());
            if ($result->isApproved()) {
                $authModel->setSuccess(true);
            } elseif ($result->isPending()) {
                $authData = $result->getAuthorizationInformation();
                $authModel->setThreeDSecureNeeded(true);
                $authModel->setUrl($authData->getUrl());
                $authModel->setData($authData->getData());
            }
        } else {
            $authModel->setFailure(true);
        }
    }

    /**
     * Finalize payment after 3-D security verification
     *
     * @param string $paymentId payment id received from Cardinity
     * @param string $pares payer authentication response received from ACS
     * @return boolean
     */
    public function finalize($paymentId, $pares)
    {
        $method = new Payment\Finalize($paymentId, $pares);

        try {
            $result = $this->_call($method);
        } catch (ValidatorException $e) {
            return false;
        } catch (\Exception $e) {
            $this->_log($e->getMessage());
            $this->_setMessage(__('Unexpected error occurred. Please contact support.'), 'error');
        }

        return isset($result) && $result && $result->isApproved();
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    private function _call(MethodInterface $method)
    {
        try {
            return $this->_client->call($method);
        } catch (Exception\Declined $e) {
            $payment = $e->getResult();
            $this->_log('Payment ' . $payment->getId() . ' declined. Reason: ' . $payment->getError());
            throw new PaymentException(new Phrase(__('Payment declined: ') . $payment->getError()));
        } catch (Exception\Request $e) {
            if ($e->getErrors()) {
                foreach ($e->getErrors() as $key => $error) {
                    $this->_log($error['message']);
                    throw new PaymentException(new Phrase(__('Validation error: ') . $error['message']));
                }
            } else {
                $this->_log($e->getMessage());
                throw new PaymentException(new Phrase(__('Request failed. Please contact support.')));
            }
        } catch (Exception\Runtime $e) {
            $this->_log($e->getMessage());
            throw new PaymentException(new Phrase(__('Internal error occurred. Please contact support.')));
        }
    }

    private function _setMessage($message, $type)
    {
        switch ($type) {
            case 'notice':
                $this->_messageManager->addNoticeMessage($message);
                break;
            case 'success':
                $this->_messageManager->addSuccessMessage($message);
                break;
            case 'warning':
                $this->_messageManager->addWarningMessage($message);
                break;
            case 'error':
                $this->_messageManager->addErrorMessage($message);
                break;
        }
    }

    private function _log($message)
    {
        $this->_logger->info('Cardinity Gateway: ' . $message);
    }

    private function _getAuthModel()
    {
        return $this->_objectManager->create('Cardinity\Magento\Model\AuthModel');
    }

    private function _getOrderModel()
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order');
    }
}
