<?php

/**
 * Magento
 *
 * @category   zibal
 * @package    zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_Block_Success extends Mage_Core_Block_Template
{
    protected function _toHtml()
    {
        require_once Mage::getBaseDir() . DS . 'lib' . DS . 'Zend' . DS . 'Log.php';

        $oderId = Mage::helper('core')->decrypt(Mage::getSingleton('core/session')->getOrderId());
        Mage::getSingleton('core/session')->unsOrderId();

        $order = new Mage_Sales_Model_Order();
        $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order->loadByIncrementId($incrementId);
        $this->_paymentInst = $order->getPayment()->getMethodInstance();

        $success = false;
        $message = Mage::Helper('zibal')->getMessage();

        if (isset($_POST['success']) && isset($_POST['trackId']) && isset($_POST['orderId'])) {

            $trackId = $_POST['trackId'];
            $orderId = $_POST['orderId'];
            $response = $_POST['message'];

            if (isset($_POST['success']) && $_POST['success'] == 1) {

                $apiKey = Mage::helper('core')->decrypt($this->_paymentInst->getConfigData('terminal_Id'));
                $params = array(
                    'merchant' => $apiKey,
                    'trackId' => $trackId
                );

                $result = self::postToZibal('verify', $params);


                if ($result && isset($result->result) && $result->result == 100) {

                    $cardNumber = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : null;

		if ($this->getConfigData('use_store_currency')) {
            		$amount      = intval($this->getOrder()->getGrandTotal());
        	} else {
            		$amount      = intval($this->getOrder()->getBaseGrandTotal());
        	}

                    if ($amount == $result->amount) {

                        $success = true;
                    }
                    else {

                        $message = Mage::Helper('zibal')->getMessage(105);
                    }
                }
                else {

                    $message = Mage::Helper('zibal')->getMessage(104);
                    $message = isset($result->message) ? $result->message : $message;
                }
            }
            else {

                if ($response) {

                    $message = $response;
                }
                else {

                    $message = Mage::Helper('zibal')->getMessage(103);
                }
            }
        }
        else {

            $message = Mage::Helper('zibal')->getMessage(102);
        }

        if ($success == true) {

            $invoice = $order->prepareInvoice();
            $invoice->register()->capture();

            Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();

            $message = sprintf($this->__("Yours order track number is %s"), $trackId);


            $order->addStatusToHistory($this->_paymentInst->getConfigData('second_order_status'), $message, true);

            $order->save();

            $order->sendNewOrderEmail();

            Mage::getSingleton('core/session')->addSuccess($message);

            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl('checkout/onepage/success', array('_secure' => true)) . '" </script> </body></html>';
            return $html;
        }
        else {

            $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

            $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $message, true);
            $order->save();

            $this->_order->sendOrderUpdateEmail(true, $message);

            Mage::getSingleton('checkout/session')->setErrorMessage($message);

            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl('checkout/onepage/failure', array('_secure' => true)) . '" </script></body></html>';
            return $html;
        }
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
