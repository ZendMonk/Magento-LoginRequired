<?php
/**
 * Rewrite of Customer Account Controller
 *
 * @category   ZendMonk
 * @package    ZendMonk_LoginRequired
 * @author     Carl Monk <@ZendMonk>
 */
require_once(Mage::getModuleDir('controllers','Mage_Customer').DS.'AccountController.php');
class ZendMonk_LoginRequired_AccountController extends Mage_Customer_AccountController
{
	/**
     * Forgot customer password action
     */
    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                    $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $customer->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    $this->_getSession()->addError($exception->getMessage());
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            $this->_getSession()
                ->addSuccess(Mage::helper('customer')->__('If there is an account associated with %s you will receive an email with a link to reset your password.', Mage::helper('customer')->htmlEscape($email)));

            /**
             * Dont redirect to default login page if referer is set (=login page disabled)
             */
            $helper = Mage::helper('zendmonk_loginrequired');
			if ($helper->isModuleOutputEnabled() && $helper->isLoginRequired() && $helper->isLoginPageDisabled()) {
				$referer = (string) $this->getRequest()->getPost('referer');
				if ($referer) {
					$refererUrl = base64_decode($referer);
					$this->_redirectUrl($refererUrl);
				} else {
					$this->_redirectUrl(Mage::getUrl());
				}
			} else {
            	$this->_redirect('*/*/');
			}

            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/forgotpassword');
            return;
        }
    }

    /**
     * Reset forgotten password
     *
     * Used to handle data recieved from reset forgotten password form
     *
     */
    public function resetPasswordPostAction()
    {
        $resetPasswordLinkToken = (string) $this->getRequest()->getQuery('token');
        $customerId = (int) $this->getRequest()->getQuery('id');
        $password = (string) $this->getRequest()->getPost('password');
        $passwordConfirmation = (string) $this->getRequest()->getPost('confirmation');

        try {
            $this->_validateResetPasswordLinkToken($customerId, $resetPasswordLinkToken);
        } catch (Exception $exception) {
            $this->_getSession()->addError(Mage::helper('customer')->__('Your password reset link has expired.'));
            $this->_redirect('*/*/');
            return;
        }

        $errorMessages = array();
        if (iconv_strlen($password) <= 0) {
            array_push($errorMessages, Mage::helper('customer')->__('New password field cannot be empty.'));
        }
        /** @var $customer Mage_Customer_Model_Customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $customer->setPassword($password);
        $customer->setConfirmation($passwordConfirmation);
        $validationErrorMessages = $customer->validate();
        if (is_array($validationErrorMessages)) {
            $errorMessages = array_merge($errorMessages, $validationErrorMessages);
        }

        if (!empty($errorMessages)) {
            $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
            foreach ($errorMessages as $errorMessage) {
                $this->_getSession()->addError($errorMessage);
            }
            $this->_redirect('*/*/resetpassword', array(
                'id' => $customerId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }

        try {
            // Empty current reset password token i.e. invalidate it
            $customer->setRpToken(null);
            $customer->setRpTokenCreatedAt(null);
            $customer->setConfirmation(null);
            $customer->save();
            $this->_getSession()->addSuccess(Mage::helper('customer')->__('Your password has been updated.'));

            /**
             * Dont redirect to default login page if is disabled
             */
            $helper = Mage::helper('zendmonk_loginrequired');
			if ($helper->isModuleOutputEnabled() && $helper->isLoginRequired() && $helper->isLoginPageDisabled()) {
				$this->_redirectUrl(Mage::getUrl());
			} else {
            	$this->_redirect('*/*/login');
			}

        } catch (Exception $exception) {
            $this->_getSession()->addException($exception, $this->__('Cannot save a new password.'));
            $this->_redirect('*/*/resetpassword', array(
                'id' => $customerId,
                'token' => $resetPasswordLinkToken
            ));
            return;
        }
    }
}