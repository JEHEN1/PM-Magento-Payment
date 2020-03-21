<?php
class PM_PerfectMoney_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('perfectmoney/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }
}