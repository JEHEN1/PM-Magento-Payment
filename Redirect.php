<?php

class PM_PerfectMoney_Block_Redirect extends Mage_Core_Block_Template
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('perfectmoney/redirect.phtml');
    }
}
