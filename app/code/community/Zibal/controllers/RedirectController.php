<?php

/**
 * Magento
 *
 * @category   Zibal
 * @package    Zibal
 * @copyright  Copyright (c) 2019 zibal.ir (https://zibal.ir/)
 */

class Zibal_RedirectController extends Mage_Core_Controller_Front_Action
{

	protected $_redirectBlockType = 'zibal/redirect';
	protected $_successBlockType = 'zibal/success';
	protected $_sendNewOrderEmail = true;
	protected $_order = NULL;
	protected $_paymentInst = NULL;
	protected $_transactionID = NULL;
	protected function _expireAjax()
	{
		if (!$this->getCheckout()->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
			exit();
		}
	}

	public function getCheckout()
	{
		return Mage::getSingleton('checkout/session');
	}

	public function redirectAction()
	{
		$session = $this->getCheckout();
		$session->setzibalQuoteId($session->getQuoteId());
		$session->setzibalRealOrderId($session->getLastRealOrderId());
		error_log('***********' . $session->getLastRealOrderId());
		$order = Mage::getModel('sales/order');
		$order->loadByIncrementId($session->getLastRealOrderId());
		$this->_order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
		$this->_paymentInst = $this->_order->getPayment()->getMethodInstance();
		$this->getResponse()->setBody($this->getLayout()->createBlock($this->_redirectBlockType)->setOrder($order)->toHtml());
		$session->unsQuoteId();
	}

	public function successAction()
	{
		$session = $this->getCheckout();
		$session->unszibalRealOrderId();
		$session->setQuoteId($session->getzibalQuoteId(true));
		$session->getQuote()->setIsActive(false)->save();
		$order = Mage::getModel('sales/order');
		$order->load($this->getCheckout()->getLastOrderId());
		$this->getResponse()->setBody($this->getLayout()->createBlock($this->_successBlockType)->setOrder($this->_order)->toHtml());
	}
}
