<?php

namespace Cardinity\Payment\Model;

use Cardinity\Client;
use Cardinity\Exception;
use Cardinity\Method\MethodInterface;
use Cardinity\Method\Payment;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Phrase;

use Magento\Framework\OAuth;

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

        
        

        $external = $this->getConfigData('external_enabled');
        $this->_log("External status ". $external);

        if($external == 1){
            $executeFunction = "_makeExternalPayment";
        }else{
            $executeFunction = "_makePayment";
        }
        
        try {            
            $this->$executeFunction();
            //$this->_makePayment();
        } catch (Exception $e) {
            $this->_log($e->getMessage());
            throw new PaymentException(new Phrase(__('Internal error occurred. Please contact support.')));
        }

        return $this;
    }

    /**
     * Issue payment request
     *
     * @return void
     */
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
            'threeds2_data' =>  [
                "notification_url" => $this->_storeManager->getStore()->getUrl('cardinity/payment/callbackv2', ['_secure' => true]), 
                "browser_info" => [
                    "accept_header" => "text/html",
                    "browser_language" => "en-US",
                    "screen_width" => 1920,
                    "screen_height" => 1040,
                    'challenge_window_size' => 'full-screen',
                    "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0",
                    "color_depth" =>  24,
                    "time_zone" =>  -60
                ],
            ],
        ]);

        $result = $this->_call($method);

        $this->_log(print_r($result, true));

        $authModel = $this->_getAuthModel();
        $authModel->cleanup();

        if ($result) {
            $authModel->setOrderId($order->getId());
            $authModel->setRealOrderId($order->getRealOrderId());
            $authModel->setPaymentId($result->getId());
            if ($result->isApproved()) {
                $authModel->setSuccess(true);
            } elseif ($result->isPending()) {
                
                if($result->isThreedsV2() && !$result->isThreedsV1()){

                    //3d Secure v2
                    $authData = $result->getThreeds2data();
                    $authModel->setThreeDSecureV2Needed(true);
                    $authModel->setUrl($authData->getAcsUrl());
                    $authModel->setData($authData->getCreq());

                }else{            

                    //3d Secure v1
                    $authData = $result->getAuthorizationInformation();
                    $authModel->setThreeDSecureNeeded(true);
                    $authModel->setUrl($authData->getUrl());
                    $authModel->setData($authData->getData());        
                }
            }
        } else {
            $authModel->setFailure(true);
        }
    }    

    /**
     * Issue External payment request
     *
     * @return void
     */
    protected function _makeExternalPayment()
    {

        $orderModel = $this->_getOrderModel();
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();

        $order->setState($orderModel::STATE_PENDING_PAYMENT);
        $order->setStatus($orderModel::STATE_PENDING_PAYMENT);

        $order->save();

        $amount = $order->getTotalDue();
        if ($amount < $this->_minAmount) {
            throw new PaymentException(new Phrase(__('Invalid order amount. Minimum amount: 0.50!')));
        }


       
        $amount =  number_format(floatval($amount), 2); 
        $cancel_url = $this->_storeManager->getStore()->getUrl('cardinity/payment/callbackexternal', ['_secure' => true]);
        $country = $order->getBillingAddress()->getData('country_id');
        $currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        $description = $order->getId();
        $order_id = $order->getRealOrderId();
        $return_url = $this->_storeManager->getStore()->getUrl('cardinity/payment/callbackexternal', ['_secure' => true]);

        $project_id = $this->getConfigData('cardinity_project_id');
        $project_secret = $this->getConfigData('cardinity_project_secret');

        $attributes = [
            "amount" => $amount,
            "currency" => $currency,
            "country" => $country,
            "order_id" => $order_id,
            "description" => $description,
            "project_id" => $project_id,
            "cancel_url" => $cancel_url,
            "return_url" => $return_url,
        ];

        ksort($attributes);

        $message = '';
        foreach($attributes as $key => $value) {
            $message .= $key.$value;
        }

        $signature = hash_hmac('sha256', $message, $project_secret);
        
        $this->_log("Preparing external payement");

        

        $externalModel = $this->_getExternalModel();
        $externalModel->cleanup();


        $externalModel->setOrderId($order->getId());
        $externalModel->setRealOrderId($order->getRealOrderId());
        //$externalModel->setPaymentId($result->getId());
        
 
        $externalModel->setAmount($amount);
        $externalModel->setCancelUrl($cancel_url);
        $externalModel->setCountry($country);
        $externalModel->setCurrency($currency);
        $externalModel->setDescription($description);
        $externalModel->setProjectId($project_id);
        $externalModel->setReturnUrl($return_url);
        $externalModel->setSignature($signature);

        //TODO: avoid putting secret on session
        $externalModel->setSecret($project_secret);

        $this->_log("External Model Prepd");



        $store = $this->_storeManager->getStore();
        $this->getResponse()->setRedirect('/' . $store->getCode() . '/cardinity/payment/external');

        //$this->_forceRedirect('cardinity/payment/external');

        

        //$script = 'document.forms["checkout"].submit()';
        /*$script = '';
        
        echo "
        <html>
            <head>
            <title>Request Example | Hosted Payment Page</title>
            </head>
            <body onload='$script'>
            <form name='checkout' method='POST' action='https://checkout.cardinity.com'>
                <button type=submit>Click Here</button>
                <input type='hidden' name='amount' value='$amount' />
                <input type='hidden' name='cancel_url' value='$cancel_url' />
                <input type='hidden' name='country' value='$country' />
                <input type='hidden' name='currency' value='$currency' />
                <input type='hidden' name='description' value='$description' />
                <input type='hidden' name='order_id' value='$order_id' />
                <input type='hidden' name='project_id' value='$project_id' />
                <input type='hidden' name='return_url' value='$return_url' />
                <input type='hidden' name='signature' value='$signature' />
            </form>
            </body>
        </html>";
    
        exit();*/
        

    }


    /**
     * Finalize payment after 3-D security verification
     *
     * @param string $paymentId payment id received from Cardinity
     * @param string $pares payer authentication response received from ACS
     * @return boolean
     */
    public function finalize($paymentId, $data, $isV2 = false)
    {
        $method = new Payment\Finalize($paymentId, $data, $isV2);

        try {
            $result = $this->_call($method);
        } catch (ValidatorException $e) {
            return false;
        } catch (\Exception $e) {
            $this->_log($e->getMessage());
            $this->_setMessage(__('Exception occurred. ').$e->getMessage(), 'error');
            return false;
        }

        return $result;
        //return isset($result) && $result && $result->isApproved();
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
            $this->_log("####CardinityError");
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
        return $this->_objectManager->create('Cardinity\Payment\Model\AuthModel');
    }


    private function _getExternalModel()
    {
        return $this->_objectManager->create('Cardinity\Payment\Model\ExternalModel');
    }

    

    private function _getOrderModel()
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order');
    }
}
