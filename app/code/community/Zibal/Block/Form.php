<?php

/**
 * Magento
 *
 * @category   Zibal
 * @package    Zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('zibal/form.phtml');
    }

    public function getPaymentImageSrc()
    {
        return $this->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'skin/frontend/base/default/images/zibal/zibal.png';
    }
}
