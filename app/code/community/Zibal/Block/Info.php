<?php

/**
 * Magento
 *
 * @category   Zibal
 * @package    Zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_Block_Info extends Mage_Payment_Block_Info
{
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('zibal/info.phtml');
	}
	public function getMethodCode()
	{
		return $this->getInfo()->getMethodInstance()->getCode();
	}
	public function toPdf()
	{
		$this->setTemplate('zibal/pdf/info.phtml');
		return $this->toHtml();
	}
}
