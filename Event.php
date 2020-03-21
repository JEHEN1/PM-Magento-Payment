<?php
/**
 * Perfect Money notification handler
 */
class PM_PerfectMoney_Model_Event
{
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Event request data setter
     * @param array $data
     * @return PM_PerfectMoney_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;

		$this->_order = Mage::getModel('sales/order')->loadByIncrementId($data['transaction_id']);

        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Process payment confiramation from status_url
     *
     * @return String
     */
    public function processStatusEvent()
    {
        try {
            $params = $this->_validateEventData();
            $msg = '';
            if ($params['verified'] == 1) {
                    $msg = Mage::helper('perfectmoney')->__('The Payment has been received by Perfect Money, batch id: ' .$params['batch_id']);
                    $this->_processSale($msg);
            }
            return $msg;
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return;
    }

    /**
     * Process payment cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
			return Mage::helper('perfectmoney')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
        return $this->_order->getQuoteId();
    }

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($msg)
    {
		$this->_createInvoice();
        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
		$this->_order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
        // save transaction ID
        $this->_order->getPayment()->setLastTransId($params['transaction_id']);
        // send new order email
        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);
     	$this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }


    protected function _validateEventData($fullCheck = true)
    {
        
        if($fullCheck){
            
			$params['verified'] = 0;
                        
            $params['batch_id']=(int)$_POST['PAYMENT_BATCH_NUM'];
                
			$string=
				  $_POST['PAYMENT_ID'].':'.$_POST['PAYEE_ACCOUNT'].':'.
				  $_POST['PAYMENT_AMOUNT'].':'.$_POST['PAYMENT_UNITS'].':'.
				  $_POST['PAYMENT_BATCH_NUM'].':'.
				  $_POST['PAYER_ACCOUNT'].':'.strtoupper(md5(Mage::getStoreConfig('payment/perfectmoney/pm_passphrase'))).':'.
				  $_POST['TIMESTAMPGMT'];

			$hash=strtoupper(md5($string));

			if($hash==$_POST['V2_HASH']){ // processing payment if only hash is valid

				if($_POST['PAYMENT_AMOUNT']==$this->_order->getGrandTotal() && $_POST['PAYEE_ACCOUNT']==Mage::getStoreConfig('payment/perfectmoney/pm_account') && $_POST['PAYMENT_UNITS']==strtoupper($this->_order->getOrderCurrencyCode())){

					$params['verified'] = 1;

				}

			}               

		    return $params;
        
		}
	}

}