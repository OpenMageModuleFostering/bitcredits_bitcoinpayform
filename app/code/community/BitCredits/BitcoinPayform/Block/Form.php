<?php

class BitCredits_BitcoinPayform_Block_Form extends Mage_Payment_Block_Form {

	protected function _construct() {
		parent::_construct();
		$this->setTemplate('bitcredits_bitcoinpayform/form.phtml');
	}
}