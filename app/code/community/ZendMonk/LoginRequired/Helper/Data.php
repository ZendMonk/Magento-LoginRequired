<?php
/**
 * Data helper
 *
 * @category   ZendMonk
 * @package    ZendMonk_LoginRequired
 * @author     Carl Monk <@ZendMonk>
 */
class ZendMonk_LoginRequired_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * XML Path Constants
	 */
	const XML_PATH_CUSTOMER_LOGIN_REQUIRED				 = 'web/customer_login/required';
	const XML_PATH_CUSTOMER_LOGIN_ENABLE_ROUTES 		 = 'web/customer_login/enable_routes';
	const XML_PATH_CUSTOMER_LOGIN_ENABLE_URLS 			 = 'web/customer_login/enable_urls';
	const XML_PATH_CUSTOMER_LOGIN_REDIRECT_TO_LOGIN_PAGE = 'web/customer_login/redirect_to_login_page';
	const XML_PATH_CUSTOMER_LOGIN_POPUP_TITLE 			 = 'web/customer_login/popup_title';
	const XML_PATH_CUSTOMER_LOGIN_POPUP_INFO_TOP 		 = 'web/customer_login/popup_info_top';
	const XML_PATH_CUSTOMER_LOGIN_POPUP_INFO_BOTTOM 	 = 'web/customer_login/popup_info_bottom';
	const XML_PATH_CUSTOMER_LOGIN_DISABLE_LOGIN_PAGE 	 = 'web/customer_login/disable_login_page';
	const XML_PATH_CUSTOMER_LOGIN_REDIRECT_CUSTOM_URL 	 = 'web/customer_login/redirect_custom_url';

	/**
	 * Default Login Page Path Constant
	 */
	const TARGET_URL_LOGIN_DEFAULT = 'customer/account/login';

	/**
	 * Check is login required
	 *
	 * @return bool
	 */
	public function isLoginRequired()
	{
		return Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_LOGIN_REQUIRED);
	}

	/**
	 * Retrieve Routes Whitelist
	 *
	 * return array
	 */
	public function getRoutesWhitelist()
	{
		$routesWhitelist = explode("\n", trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_ENABLE_ROUTES)));
		$routesWhitelist[] = 'customer_account';
		return $routesWhitelist;
	}

	/**
	 * Retrieve Urls Whitelist
	 *
	 * @return array
	 */
	public function getUrlsWhitelist()
	{
		return explode("\n", trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_ENABLE_URLS)));
	}

	protected $_whitelistRequest;

	/**
	 * Check is whitelist request
	 */
	public function isWhitelistRequest()
	{
		if (!isset($this->_whitelistRequest)) {
			$whitelistRequest = false;
			$request = Mage::app()->getRequest();
			$fullActionName = $request->getRequestedRouteName().'_'.
	            $request->getRequestedControllerName().'_'.
	            $request->getRequestedActionName();
			$requestString = $request->getRequestString();
			foreach ($this->getRoutesWhitelist() as $route) {
				$route = trim($route);
	    		if (!empty($route) && strpos($fullActionName, $route) === 0) {
					$whitelistRequest = true;
					break;
	    		}
			}
			if (!$whitelistRequest) {
				$requestString = $request->getRequestString();
				foreach ($this->getUrlsWhitelist() as $url) {
					$url = trim($url);
					if (!empty($url)) {
						$url = '/'.$url.'/';
						if (strpos($requestString, $url) === 0) {
							$whitelistRequest = true;
							break;
						}
		    		}
				}
			}
			$this->_whitelistRequest = $whitelistRequest;
		}
		return $this->_whitelistRequest;
	}

	/**
	 * Check is Login Page disabled
	 *
	 * @return bool
	 */
	public function isLoginPageDisabled()
	{
		if (!Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_LOGIN_REDIRECT_TO_LOGIN_PAGE) &&
		    Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_LOGIN_DISABLE_LOGIN_PAGE)) {
		    return true;
		}
		return false;
	}

	/**
	 * Retrieve target Url
	 *
	 * @return string
	 */
	public function getTargetUrl()
	{
		$path = '';
		if (Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMER_LOGIN_REDIRECT_TO_LOGIN_PAGE)) {
			$path = self::TARGET_URL_LOGIN_DEFAULT;
		} else {
			$path = trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_REDIRECT_CUSTOM_URL));
		}
		if (!empty($path)) {
			$params = Mage::app()->getStore()->isFrontUrlSecure() ? array('_secure' => 1) : array();
			return Mage::getUrl($path, $params);
		}
		return $path;
	}

	/**
	 * Retrieve Pseudo PopUp Title
	 *
	 * @return string
	 */
	public function getPopupTitle()
	{
		$popupTitle = trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_POPUP_TITLE));
		if (empty($popupTitle)) {
			$customerHelper = Mage::helper('customer');
			$popupTitle = $customerHelper->__('Customer Log In');
		}
		return $popupTitle;
	}

	/**
	 * Retrieve Pseudo PopUp Info Top html
	 *
	 * @return string
	 */
	public function getPopupInfoTop()
	{
		$popupInfoTop = trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_POPUP_INFO_TOP));
		if (empty($popupInfoTop)) {
			$customerHelper = Mage::helper('customer');
			$popupInfoTop = $customerHelper->__('If you have an account with us, please log in.');
		}
		return $popupInfoTop;
	}

	/**
	 * Retrieve Pseudo PopUp Info Bottom html
	 *
	 * @return string
	 */
	public function getPopupInfoBottom()
	{
		return trim(Mage::getStoreConfig(self::XML_PATH_CUSTOMER_LOGIN_POPUP_INFO_BOTTOM));
	}

	/**
	 * Check for cookie script removal
	 *
	 * @return bool
	 */
	public function removeCookieScripts()
	{
		$removeCookieScripts = false;
		$isLoginRequired = $this->isLoginRequired();
		if (!$isLoginRequired) {
			$request = Mage::app()->getRequest();
			$routeName = $request->getRouteName();
			$controllerName = $request->getControllerName();
			if (($routeName == 'cms' && $controllerName == 'index') ||
				($routeName == 'catalog' && $controllerName == 'category')) {
				$removeCookieScripts = true;
			} else if ($routeName == 'catalog' && $controllerName == 'product') {
				$removeCookieScripts = true;
			}
		}
		return $removeCookieScripts;
	}
}