<?php

/**
 * Magento
 *
 * @category   zibal
 * @package    zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $module = 'zibal';
        $payment = $this->getOrder()->getPayment()->getMethodInstance();
        $res = $payment->getUrl();

        if ($res->result && isset($res->result) && $res->result == 100) {
            $url = 'https://gateway.zibal.ir/start/' . $res->trackId;
			$url .= ( $payment->getConfigData('ssl_enabled'))?"/direct":"";

            $html = '<html><body> <script type="text/javascript"> window.location = "' . $url . '" </script> </body></html>';
        }
        else {
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl('checkout/onepage/failure', array('_secure' => true)) . '" </script> </body></html>';
        }
        return $html;
    }
}
