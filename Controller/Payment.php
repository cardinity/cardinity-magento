<?php

namespace Cardinity\Payment\Controller;


use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

abstract class Payment extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Cardinity\Payment\Logger\Logger $logger,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        parent::__construct(
            $context
        );

        $this->_objectManager = $context->getObjectManager();
        $this->_messageManager = $context->getMessageManager();
        $this->_logger = $logger;
        $this->_pageFactory = $pageFactory;
    }

     /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
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
        return $this->_objectManager->create('Cardinity\Payment\Model\AuthModel');
    }

    protected function _getExternalModel()
    {
        return $this->_objectManager->create('Cardinity\Payment\Model\ExternalModel');
    }

    protected function _getPaymentModel()
    {
        return $this->_objectManager->create('Cardinity\Payment\Model\PaymentModel');
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

    protected function _success()
    {
        $this->_log('called ' . __METHOD__);

        $authModel = $this->_getAuthModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($authModel->getOrderId());

        $this->_log("in success auth model :".$authModel->getOrderId() );
        $this->_log("in success order state :".$order->getState() );
        $this->_log("in success order id :".$order->getId() );
        $this->_log("in success order :".$orderModel::STATE_PENDING_PAYMENT );

        if ($order && $order->getId() && $order->getState() == $orderModel::STATE_PENDING_PAYMENT) {
            try {
                $order->setState($orderModel::STATE_PROCESSING);
                $order->setStatus($orderModel::STATE_PROCESSING);
                $order->setEmailSent(true);
                $order->save();

                $this->_log('Order marked as paid', $order->getRealOrderId());

                $this->_createInvoice($order);

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

    protected function _successExternal()
    {
        $this->_log('called ' . __METHOD__);

        $externalModel = $this->_getExternalModel();
        $orderModel = $this->_getOrderModel();

        $order = $orderModel->load($externalModel->getOrderId());

        $this->_log("in success auth model :".$externalModel->getOrderId() );
        $this->_log("in success order state :".$order->getState() );
        $this->_log("in success order id :".$order->getId() );
        $this->_log("in success order :".$orderModel::STATE_PENDING_PAYMENT );

        if ($order && $order->getId() 
        //&& $order->getState() == $orderModel::STATE_PENDING_PAYMENT
        ) {
            try {
                $order->setState($orderModel::STATE_PROCESSING);
                $order->setStatus($orderModel::STATE_PROCESSING);
                $order->setEmailSent(true);
                $order->save();

                $this->_log('Order marked as paid', $order->getRealOrderId());

                $this->_createInvoice($order);

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
        if (!$order->canInvoice()) {
            return false;
        }

        try {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            if ($invoice->canCapture()) {
                $invoice->capture();
            }
            $invoice->save();
            $order->addRelatedObject($invoice);

            $this->_log('Invoice created', $order->getRealOrderId());

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
