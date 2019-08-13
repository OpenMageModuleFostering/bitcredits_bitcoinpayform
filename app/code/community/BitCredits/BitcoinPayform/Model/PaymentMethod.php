<?php
 
/**
* Our test CC module adapter
*/
class BitCredits_BitcoinPayform_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    */
    protected $_code = 'bitcredits_bitcoinpayform';

    protected $_formBlockType = 'bitcredits_bitcoinpayform/form';
 
    /**
     * Here are examples of flags that will determine functionality availability
     * of this module to be used by frontend and backend.
     *
     * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
     *
     * It is possible to have a custom dynamic logic by overloading
     * public function can* for each flag respectively
     */
     
    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;
 
    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;
 
    /**
     * Can capture funds online?
     */
    protected $_canCapture              = false;
 
    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;
 
    /**
     * Can refund online?
     */
    protected $_canRefund               = false;
 
    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = false;
 
    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = false;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;
 
    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;
 
    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;
 
    public function authorize(Varien_Object $payment, $amount) {

        $key = Mage::getStoreConfig('payment/bitcredits_bitcoinpayform/api_key');//'IDIOTSGUIDEISNICEANDSWEET';
        $endpoint = Mage::getStoreConfig('payment/bitcredits_bitcoinpayform/api_endpoint');//'http://api.bitcredits.local:6543';

        if(!isset($_COOKIE['bitc'])){
            Mage::throwException(Mage::helper('sales')->__('Could not place order.'));
        }

        $order = $payment->getOrder();
        $quoteData = $order->getQuote()->getData();

        $method = '/v1/transactions';
        $data = array(
            'api_key' => $key,
            'src_token' => $_COOKIE['bitc'],
            'dst_account' => '/magento/orders/'.$order->getRealOrderId(),
            'dst_account_create' => true,
            'amount' => $amount,
            'data' => array(
                'email' => $quoteData['customer_email'],
                'firstname' => $quoteData['customer_firstname'],
                'lastname' => $quoteData['customer_lastname'],
                'order_id' => $order->getRealOrderId()
            )
        );
        
        $ch = curl_init();
        $data_string = json_encode($data);
        curl_setopt($ch, CURLOPT_URL, $endpoint . $method);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));
        $result = curl_exec($ch);
        $res = json_decode($result, true);

        if(
            $res == null
         || !isset($res['status'])
        ){
            Mage::throwException(Mage::helper('sales')->__('Transaction not completed.'));
        }elseif($res['status'] == 'error'){
            if(isset($res['message'])){
                Mage::throwException(Mage::helper('sales')->__('Error while processing payment: ').$res['message']);
            }else{
                Mage::throwException(Mage::helper('sales')->__('Transaction not completed. No error message was provided.'));
            }
        }

        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();

        if (!count($order->getInvoiceCollection())) {
            $invoice = $order->prepareInvoice()
            ->setTransactionId(1)
            ->addComment('Invoiced automatically by BitCredits/BitcoinPayForm/Model/PaymentMethod.php')
            ->register()
            ->pay();

            $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

            $transactionSave->save();
            try {
                $order->sendNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        } else {
            //
        }

        return true;
    }
}
?>