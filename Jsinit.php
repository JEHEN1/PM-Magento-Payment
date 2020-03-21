<?php
class PM_PerfectMoney_Block_Jsinit extends Mage_Adminhtml_Block_Template
{
    /**
     * Include JS in head if section is perfectmoney
     */
    protected function _prepareLayout()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'perfectmoney') {
            $this->getLayout()
                ->getBlock('head');
        }
        parent::_prepareLayout();
    }

    /**
     * Print init JS script into body
     * @return string
     */
    protected function _toHtml()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'perfectmoney') {
            return parent::_toHtml();
        } else {
            return '';
        }
    }
}
