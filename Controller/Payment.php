<?php

namespace Cardinity\Magento\Controller;


use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;


use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Framework\DB\Transaction;

abstract class Payment extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $_configData;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Cardinity\Magento\Logger\Logger $logger,
        \Magento\Framework\View\Result\PageFactory $pageFactory,

        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        InvoiceRepositoryInterface $invoiceRepository,
        Transaction $transaction   
    )
    {
        parent::__construct(
            $context
        );

        $this->_objectManager = $context->getObjectManager();
        $this->_messageManager = $context->getMessageManager();
        $this->_logger = $logger;
        $this->_pageFactory = $pageFactory;


        $this->_invoiceRepository  = $invoiceRepository;
        $this->_transaction = $transaction;
        $this->_transactionBuilder = $transactionBuilder;

        
        $this->_configData = $this->_objectManager->get('Cardinity\Magento\Helper\Data');

        $this->scopeConfig = $scopeConfig;
    }

     /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): InvalidRequestException 
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

    


    protected function _forceRedirect($url)
    {
        $this->_redirect($url, ['_secure' => true]);
    }

    protected function _getOrderModel()
    {
        return $this->_objectManager->create('Magento\Sales\Model\Order');
    }

    protected function _getAuthModel()
    {
        return $this->_objectManager->create('Cardinity\Magento\Model\AuthModel');
    }

    protected function _getExternalModel()
    {
        return $this->_objectManager->create('Cardinity\Magento\Model\ExternalModel');
    }

    protected function _getPaymentModel()
    {
        return $this->_objectManager->create('Cardinity\Magento\Model\PaymentModel');
    }

    protected function _setMessage($message, $type)
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

    protected function _log($message, $id = null)
    {
        if ($id) {
            $this->_logger->info('Cardinity Gateway: ' . $id . ' - ' . $message);
        } else {
            $this->_logger->info('Cardinity Gateway: ' . $message);
        }
    }

    protected function _cancel()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($authModel->getOrderId());

        if ($order && $order->getId() && $order->getState() == $orderModel::STATE_PENDING_PAYMENT) {
            $order->cancel()->save();
            $this->_log('Order cancelled', $order->getRealOrderId());
            $this->_setMessage(__('Your order has been canceled.'), 'notice');
        } else {
            $this->_setMessage(__('Unexpected error occurred. Please contact support.'), 'error');
        }
    }

    protected function _success($external = false)
    {
        $this->_log('called ' . __METHOD__);

        
        $orderModel = $this->_getOrderModel();

        $paymentId= null;
        $threedSecure = 'none';

        //if internal
        if($external == false){
            $authModel = $this->_getAuthModel();
            $order = $orderModel->load($authModel->getOrderId());
            $paymentId= $authModel->getPaymentId();
            $threedSecure = $authModel->getThreeDSecureVHistory();
            
            $this->_log("in ".__METHOD__." auth model :".$authModel->getOrderId() );
        }else{
            $externalModel = $this->_getExternalModel();
            $order = $orderModel->load($externalModel->getOrderId());
            $paymentId = $externalModel->getPaymentId();

            $threedSecure = 'unknown(external)';

            $this->_log("in ".__METHOD__." external model :".$externalModel->getOrderId() );
        }
        

        $this->_log("in ".__METHOD__." order state :".$order->getState() );
        $this->_log("in ".__METHOD__." order id :".$order->getId() );
        $this->_log("in ".__METHOD__." order :".$orderModel::STATE_PENDING_PAYMENT );
        
        
        if ($order && $order->getId() && $order->getState() == $orderModel::STATE_PENDING_PAYMENT) {
            try {
                $order->setState($orderModel::STATE_PROCESSING);
                $order->setStatus($orderModel::STATE_PROCESSING);
                $order->setEmailSent(true);
                $order->save();

                $this->_log('Order marked as paid', $order->getRealOrderId());

                $this->_createInvoice($order);
                $this->_addTransactionToOrder($order, array(
                    'id' =>  $paymentId,
                    'total' =>  $order->getGrandTotal(),
                    'currency' =>  $order->getBaseCurrency(),
                    '3DSecure' => $threedSecure,
                    'status' => 'paid'
                ));

                return true;
            } catch (\Exception $exception) {
                $this->_log($exception->getMessage());
                return false;
            }
        }else{
            $this->_log('success mismatch ');
        }
        return false;
    }

    
    protected function _createInvoice($order)
    {
        $this->_log('called ' . __METHOD__);
        if (!$order->canInvoice()) {
            return false;
        }

        try {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            if ($invoice->canCapture()) {
                $invoice->capture();
            }

            $invoice->getOrder()->setIsInProcess(true);
            $invoice->pay();  
            $invoice->save();

            $order->addRelatedObject($invoice);

            // Create the transaction
            $transactionSave = $this->_transaction
            ->addObject($invoice)
            ->addObject($order);
            $transactionSave->save();

            $this->_log('invoice step4');
            
            $order->save();  

            $this->_log('invoice step5');

            // Save the invoice
            $this->_invoiceRepository->save($invoice);

            $this->_log('invoice step6');

            $this->_log('Order created', $order->getRealOrderId());
            $this->_log('Invoice created', $invoice->getId());

            return true;
        } catch (\Exception $exception) {
            $this->_log('invoice exception occured');
            return false;
        }
    }

    public function _addTransactionToOrder($order, $paymentData) {
        try {
            // Prepare payment object
            $payment = $order->getPayment();
            $payment->setMethod('cardinity'); 
            $payment->setLastTransId($paymentData['id']);
            $payment->setTransactionId($paymentData['id']);
            //$payment->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData]);

            $this->_log('transaction step1');

            // Formatted price
            $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

            $this->_log('transaction step2');
 
            // Prepare transaction
            $transaction = $this->_transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData['id'])
            ->setAdditionalInformation([
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array(                    
                    (string) $paymentData['3DSecure'],
                ),
            ])
            ->setFailSafe(true)
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $this->_log('transaction step3');

            // Add transaction to payment
            $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
            $payment->setParentTransactionId(null);

            $this->_log('transaction step4');
            // Save payment, transaction and order
            $payment->save();
            $order->save();
            $transaction->save();
            $this->_log('transaction step5');
 
            return  $transaction->getTransactionId();

        } catch (Exception $e) {
            $this->_log('transaction exception occured');
        }
    }

    
}
