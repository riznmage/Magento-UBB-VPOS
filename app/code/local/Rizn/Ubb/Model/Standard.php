<?php
class Rizn_Ubb_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'ubb';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('ubb/payment/redirect', array('_secure' => true));
	}
}
?>