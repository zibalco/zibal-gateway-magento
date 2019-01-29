<?php

/**
 * Magento
 *
 * @category   zibal
 * @package    zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_Model_zibal extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'zibal';
    protected $_formBlockType = 'zibal/form';
    protected $_infoBlockType = 'zibal/info';
    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_order;

    public function getOrder()
    {
        if (!$this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
        }
        return $this->_order;
    }

    public function validate()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->setCustomerNoteNotify(false);
        parent::validate();
    }

    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getBaseUrl().'zibal/redirect/redirect/';
     //   return Mage::getUrl('zibal/redirect/redirect', array('_secure' => true));
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
        return $this;
    }

    public function getPaymentMethodType()
    {
        return $this->_paymentMethod;
    }

    public function getUrl()
    {
        require_once Mage::getBaseDir() . DS . 'lib' . DS . 'Zend' . DS . 'Log.php';

        $result = [];

        if (extension_loaded('curl')) {

            $orderId = $this->getOrder()->getRealOrderId();

            Mage::getSingleton('core/session')->setOrderId(Mage::helper('core')->encrypt($this->getOrder()->getRealOrderId()));

            $apiKey = Mage::helper('core')->decrypt($this->getConfigData('terminal_Id'));
            //$amount = intval($this->getOrder()->getGrandTotal());
            $callback = Mage::getBaseUrl().'zibal/redirect/success/';

            $mobile = $this->getOrder()->getShippingAddress()->getTelephone();


	 if ($this->getConfigData('use_store_currency')) {
            $amount      = number_format($this->getOrder()->getGrandTotal(),0,'.','');
        } else {
            $amount      = number_format($this->getOrder()->getBaseGrandTotal(),0,'.','');
        }

            $params = array(

                'merchant' => $apiKey,
                'amount' => $amount,
                'callbackUrl' => urlencode($callback),
                'mobile' => $mobile,
                'orderId' => $orderId
            );

            $result = self::postToZibal('request', $params);

            if ($result && isset($result->result) && $result->result == 100) {

                $pgwpay_url ='https://gateway.zibal.ir/start/' . $result->trackId;
            }
            else {

                $message = Mage::Helper('zibal')->getMessage(101);
                $message = isset($result->errorMessage) ? $result->errorMessage : $message;

                $this->getOrder();
                $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $message, true);
                $this->_order->save();
                Mage::getSingleton('checkout/session')->setErrorMessage($message);
            }
        }
        else {

            $message = Mage::Helper('zibal')->getMessage(100);

            $this->getOrder();
            $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $message, true);
            $this->_order->save();
            Mage::getSingleton('checkout/session')->setErrorMessage($message);
        }

        return $result;
    }

    public function getFormFields()
    {
        $orderId = $this->getOrder()->getRealOrderId();
        $params = array('x_invoice_num' => $orderId);
        return $params;
    }


    /**
     * connects to zibal's rest api
     * @param $path
     * @param $parameters
     * @return stdClass
     */
    private function postToZibal($path, $parameters)
    {
        $url = 'https://gateway.zibal.ir/'.$path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        curl_close($ch);
        return json_decode($response);
    }

}
