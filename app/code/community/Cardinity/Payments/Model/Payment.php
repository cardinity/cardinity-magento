<?php

require __DIR__ . '/../vendor/autoload.php';

use Cardinity\Client;
use Cardinity\Method\MethodInterface;
use Cardinity\Method\Payment;
use Cardinity\Exception;

class Cardinity_Payments_Model_Payment extends Mage_Payment_Model_Method_Cc
{

    protected $_code = 'cardinity';
    protected $_isSetToExternal = false;

    protected $_canUseCheckout = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded  = true;



    /**
     * @var $_client Cardinity\Client SDK client
     */
    private $_client;

    private $_storeId;

    /**
     * Creates Cardinity SDK client
     */
    public function __construct()
    {
        $this->_client = Client::create([
            'consumerKey' => $this->getConfigData('cardinity_key'),
            'consumerSecret' => $this->getConfigData('cardinity_secret'),
        ]);

        $this->_storeId = Mage::app()->getStore()->getStoreId();

        $external = $this->getConfigData('external_enabled', $this->_storeId);

        if($external == 1){
            $this->_isSetToExternal = true;

            $this->_formBlockType = 'payment/form';
            $this->_infoBlockType = 'payment/info';
        }


    }

    /**
     * Validate
     * override if external
     */
    public function validate(){
        //$external = $this->getConfigData('external_enabled');

        if($this->_isSetToExternal){
            return $this;
        }else{
            parent::validate();
        }
    }

    /**
     *
     * <payment_action>Sale</payment_action>
     * Initialize payment method. Called when purchase is complete.
     * Order is created after this method is called.
     *
     * @param string $paymentAction
     * @param Varien_Object $stateObject
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $this->_log('called ' . __METHOD__);

        parent::initialize($paymentAction, $stateObject);

        if($paymentAction != 'sale'){
            $this->_log('Wrong payment action. Only sale is allowed.', Zend_Log::ERR);
            Mage::throwException(Mage::helper('payment')->__('Internal error occurred. Please contact support.'));
        }

        // Set the default state of a new order.
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
        $this->_log('order status changed to pending payment');


        //determine if external is to be used
        $external = $this->getConfigData('external_enabled');
        $this->_log("External status ". $external);

        if($this->_isSetToExternal){
            $executeFunction = "_makeExternalPayment";
        }else{
            $executeFunction = "_makePayment";
        }



        // Make initial payment attempt
        try{
            $this->$executeFunction();
        }catch (Exception $e){
            $this->_log($e);
            Mage::throwException(Mage::helper('payment')->__('Internal error occurred. Please contact support.'));
        }
        return $this;
    }

    /**
     * Return URL to redirect the customer to.
     * Called after 'place order' button is clicked.
     * Called after order is created and saved.
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $this->_log('called ' . __METHOD__);
        $authModel = Mage::getModel('cardinity/auth');
        $externalModel = Mage::getModel('cardinity/external');

        if($externalModel && $externalModel->getExternalRequired()){
            $this->_log('redirecting buyer to external page');
            $redirectUrl = Mage::getModel('core/url')->getUrl("cardinity/payment/external", array('_secure'=>true));
        }
        else if ($authModel && $authModel->getSuccess()) {
            $this->_log('redirecting buyer to success page');
            $redirectUrl = Mage::getModel('core/url')->getUrl("cardinity/payment/success", array('_secure'=>true));
        }
        else if ($authModel && $authModel->getThreeDSecureNeeded()) {
            $this->_log('redirecting buyer to auth page');
            $redirectUrl = Mage::getModel('core/url')->getUrl("cardinity/payment/auth", array('_secure'=>true));
        }
        else {
            $this->_log('redirecting buyer to failure page');
            $redirectUrl = Mage::getModel('core/url')->getUrl("cardinity/payment/failure", array('_secure'=>true));
        }

        return $redirectUrl;
    }

    protected function _makeExternalPayment(){

        $this->_log('called ' . __METHOD__);



        // Process payment
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $amount = $order->getBaseTotalDue();

        // Validate minimum sale amount
        if ($amount < 0.5) {
            Mage::throwException(Mage::helper('payment')->__('Invalid order amount. Minimum amount: 0.50!'));
        }


        $amount = $order->getTotalDue();
        if ($amount < $this->_minAmount) {
            throw new PaymentException(new Phrase(__('Invalid order amount. Minimum amount: 0.50!')));
        }



        $amount =  number_format(floatval($amount), 2);

        $cancel_url =  Mage::getUrl('cardinity/payment/callbackext', array('_secure' => true));  // $this->_storeManager->getStore()->getUrl('cardinity/payment/callbackexternal', ['_secure' => true]);
        $return_url = Mage::getUrl('cardinity/payment/callbackext', array('_secure' => true));// $this->_storeManager->getStore()->getUrl('cardinity/payment/callbackexternal', ['_secure' => true]);


        $country = $order->getBillingAddress()->getData('country_id');
        $currency =  Mage::app()->getStore()->getBaseCurrencyCode();
        $description = $order->getId();
        $order_id = $order->getRealOrderId();

        $project_id = $this->getConfigData('cardinity_project_id', $this->_storeId);
        $project_secret = $this->getConfigData('cardinity_project_secret', $this->_storeId);

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



        $externalModel = Mage::getModel('cardinity/external');
        $externalModel->cleanup();


        $externalModel->setOrderId($order->getId());
        $externalModel->setRealOrderId($order->getRealOrderId());


        $externalModel->setAmount($amount);
        $externalModel->setCancelUrl($cancel_url);
        $externalModel->setCountry($country);
        $externalModel->setCurrency($currency);
        $externalModel->setDescription($description);
        $externalModel->setProjectId($project_id);
        $externalModel->setReturnUrl($return_url);
        $externalModel->setSignature($signature);
        $externalModel->setExternalRequired(1);

        $externalModel->setSecret($project_secret);

        $this->_log("External Model Prepd");


    }


    protected function _makePayment(){

        $this->_log('called ' . __METHOD__);

        // Process payment
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $amount = $order->getBaseTotalDue();

        // Validate minimum sale amount
        if ($amount < 0.5) {
            Mage::throwException(Mage::helper('payment')->__('Invalid order amount. Minimum amount: 0.50!'));
        }

        $holder = sprintf(
            '%s %s',
            $order->getBillingAddress()->getData('firstname'),
            $order->getBillingAddress()->getData('lastname')
        );

        $method = new Payment\Create([
            'amount' => floatval($amount),
            'currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
            'settle' => true,
            'order_id' => $order->getRealOrderId(),
            'country' => $order->getBillingAddress()->getData('country_id'),
            'payment_method' => Payment\Create::CARD,
            'payment_instrument' => [
                'pan' => $payment->cc_number,
                'exp_year' => (int) $payment->cc_exp_year,
                'exp_month' => (int) $payment->cc_exp_month,
                'cvc' => $payment->cc_cid,
                'holder' => $holder
            ],
        ]);

        try {
            $result = $this->_call($method);

            // Approved or pending pending
            $authModel = Mage::getModel('cardinity/auth');

            $authModel->cleanup();
            $authModel->setOrderId($order->getId());
            $authModel->setRealOrderId($order->getRealOrderId());

            if ($result) {

                $authModel->setPaymentId($result->getId());

                if ($result->isApproved()) {

                    $authModel->setSuccess(true);
                } elseif ($result->isPending()) {

                    $authData = $result->getAuthorizationInformation();
                    $authModel->setThreeDSecureNeeded(true);
                    $authModel->setUrl($authData->getUrl());
                    $authModel->setData($authData->getData());
                }
            }
            // Declined payment or error
            else {
                $authModel->setFailure(true);
            }
        }
        catch (\Exception $e){
            $this->_log($e->getMessage(), Zend_Log::ERR);
            $error = $this->_getHelper()->__('Unexpected error occurred. Please contact support.');
            $this->_error($error, true);
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
        $this->_log('called ' . __METHOD__);

        $method = new Payment\Finalize($paymentId, $pares);

        try {
            $result = $this->_call($method);
        }
        catch (\Exception $e){
            $this->_log($e->getMessage(), Zend_Log::ERR);
            $error = $this->_getHelper()->__('Unexpected error occurred. Please contact support.');
            $this->_error($error, true);
        }

        return $result && $result->isApproved();
    }

    private function _call(MethodInterface $method, $throwException = false)
    {
        try {
            return $this->_client->call($method);
        } catch (Exception\Declined $e) {
            $payment = $e->getResult();
            $this->_log('Payment ' .  $payment->getId() . ' declined. Reason: ' . $payment->getError());
            $error = $this->_getHelper()->__('Payment declined: ') . $payment->getError();
            $this->_error($error, $throwException);
        } catch (Exception\Request $e) {
            $this->_log($e->getMessage(), Zend_Log::ERR);
            $error = $this->_getHelper()->__('Request failed. Please contact support.');
            $this->_error($error, $throwException);
        } catch (Exception\Runtime $e) {
            $this->_log($e->getMessage(), Zend_Log::ERR);
            $error = $this->_getHelper()->__('Internal error occurred. Please contact support.');
            $this->_error($error, $throwException);
        }
    }

    private function _error($error, $throwException)
    {
        if ($throwException) {
            Mage::throwException($error);
        } else {
            Mage::getSingleton('core/session')->addError($error);
        }
    }

    private function _log($message, $level = Zend_Log::DEBUG) {
        Mage::log('Cardinity Gateway: ' . $message, $level);
    }



}
