<?php
/**
 * Observer
 *
 * @category   ZendMonk
 * @package    ZendMonk_LoginRequired
 * @author     Carl Monk <@ZendMonk>
 */
class ZendMonk_LoginRequired_Model_Observer
{
	/**
	 * Check if customer is logged in on predispatch
	 *
	 * @param Varien_Event_Observer $observer
	 * @return ZendMonk_LoginRequired_Model_Observer
	 */
	public function preDispatch($observer)
	{
		$helper = Mage::helper('zendmonk_loginrequired');
		if ($helper->isModuleOutputEnabled() && $helper->isLoginRequired()) {

			$request = Mage::app()->getRequest();
			if (!$request->isXmlHttpRequest()) {

				$fullActionName = $request->getRequestedRouteName().'_'.
		            $request->getRequestedControllerName().'_'.
		            $request->getRequestedActionName();
				$controllerAction = $observer->getEvent()->getControllerAction();

				if ($helper->isLoginPageDisabled() && strpos($fullActionName, 'customer_account_login') === 0) {
					if ($fullActionName != 'customer_account_loginPost') {
						$controllerAction->norouteAction();
						return $this;
					}
				}

				$session = Mage::getSingleton('customer/session');
				if (!$session->isLoggedIn()) {

					$targetUrl = $helper->getTargetUrl();

					/**
					 * Evt. require URL to be secure
					 */
					if (empty($targetUrl) && Mage::app()->getStore()->isFrontUrlSecure()) {
						$targetUrl = Mage::helper('core/url')->getCurrentUrl();
						if (substr($targetUrl, 0, 5) == 'http:') {
							$targetUrl = 'https:'.substr($targetUrl, 5);
						}
					}

					if (empty($targetUrl) || Mage::helper('core/url')->getCurrentUrl() == $targetUrl) {
						return $this;
					}
					if ($helper->isWhitelistRequest()) {
						return $this;
					}

					$session->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());
		            $controllerAction->getResponse()->setRedirect($targetUrl);
		            $controllerAction->getResponse()->sendResponse();
		            exit;

		        }

			}

		}
		return $this;
	}

	/**
	 * Add Layout Update Handles before load
	 *
	 * @param Varien_Event_Observer $observer
	 * @return ZendMonk_LoginRequired_Model_Observer
	 */
	public function loadBefore($observer)
	{
		$helper = Mage::helper('zendmonk_loginrequired');
		if ($helper->isModuleOutputEnabled() && $helper->isLoginRequired()) {

			$session = Mage::getSingleton('customer/session');
			if (!$session->isLoggedIn()) {

				$request = Mage::app()->getRequest();
				$fullActionName = $request->getRequestedRouteName().'_'.
		            $request->getRequestedControllerName().'_'.
		            $request->getRequestedActionName();

		        if (strpos($fullActionName, 'customer_account_login')) {
		        	return $this;
		        }

				$update = $observer->getEvent()->getLayout()->getUpdate();
				if ($helper->isLoginPageDisabled()) {
					$update->addHandle('disable_default_login_page');
				}

				if ($helper->isWhitelistRequest()) {
					return $this;
				}

				$session->setBeforeAuthUrl(Mage::helper('core/url')->getCurrentUrl());
				$update->addHandle('request_login_via_pseudo_popup');

			}
		}
		return $this;
	}

	/**
	 * Prevent pages from being cached via VarnishCache
	 *
	 * @param Varien_Event_Observer $observer
	 * @return ZendMonk_LoginRequired_Model_Observer
	 */
	public function checkVarnishCacheEnabled($observer)
	{
		$helper = Mage::helper('zendmonk_loginrequired');
		if ($helper->isModuleOutputEnabled() && $helper->isLoginRequired() && !$helper->isWhitelistRequest()) {
			$observer->getEvent()->setNoCache(true);
		}
		return $this;
	}
}