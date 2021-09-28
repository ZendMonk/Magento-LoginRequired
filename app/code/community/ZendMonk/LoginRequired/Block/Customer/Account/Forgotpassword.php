<?php
/**
 * Rewrite of Customer Account Forgotpassword block
 *
 * @category   ZendMonk
 * @package    ZendMonk_LoginRequired
 * @author     Carl Monk <@ZendMonk>
 */
class ZendMonk_LoginRequired_Block_Customer_Account_Forgotpassword extends Mage_Customer_Block_Account_Forgotpassword
{
	/**
	 * Check if default login page is disabled
	 *
	 * @return bool
	 */
	public function isLoginPageDisabled()
	{
		$_helper = Mage::helper('zendmonk_loginrequired');
		return ($_helper->isLoginRequired() && $_helper->isLoginPageDisabled());
	}

	/**
	 * Check if referer param is set
	 *
	 * @return bool
	 */
	public function isRefererSet()
	{
		if ($this->getRefererValue()) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieve referer value
	 *
	 * @return string
	 */
	public function getRefererValue()
	{
		if ($referer = Mage::app()->getRequest()->getParam('referer')) {
			return $referer;
		}
		return '';
	}

	/**
	 * Retrieve referer url
	 *
	 * @return string
	 */
	public function getRefererUrl()
	{
		if ($referer = $this->getRefererValue()) {
			return base64_decode($referer);
		}
		return '';
	}
}
