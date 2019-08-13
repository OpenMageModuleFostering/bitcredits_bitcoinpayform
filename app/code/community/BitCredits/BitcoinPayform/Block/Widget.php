<?php

class BitCredits_BitcoinPayform_Block_Widget extends Mage_Checkout_Block_Onepage_Payment {

	protected function _construct() {
		$this->setTemplate('bitcredits_bitcoinpayform/widget.phtml');
		parent::_construct();
	}

}