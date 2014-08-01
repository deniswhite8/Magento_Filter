<?php
/**
 * Oggetto Filter extension for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade
 * the Oggetto Review module to newer versions in the future.
 * If you wish to customize the Oggetto Review module for your needs
 * please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Oggetto
 * @package    Oggetto_Filter
 * @copyright  Copyright (C) 2014 Oggetto Web (http://oggettoweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
?>
<?php
/**
 * Filter item model
 *
 * @category    Oggetto
 * @package     Oggetto_Filter
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Oggetto_Filter_Model_Layer_Filter_Item extends Mage_Catalog_Model_Layer_Filter_Item
{
    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $requestVarName = $this->getFilter()->getRequestVar();
        $requestValue = $this->getValue();
        $requestValueFromUrl = Mage::app()->getRequest()->getParam($requestVarName);
        if ($requestValueFromUrl) {
            $requestVarValue = $requestValueFromUrl . $separator . $requestValue;
        } else {
            $requestVarValue = $requestValue;
        }

        $query = array(
            $requestVarName => $requestVarValue,
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        return Mage::getUrl('*/*/*',
            array('_current' => true, '_query' => $query));
    }

    /**
     * Get url for remove item from filter
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        $paramName = $this->getFilter()->getRequestVar();
        $filterValue = $this->getValue();

        $query = array($paramName => $this->getFilter()->getResetValue($filterValue));
        $params['_current']     = true;
        $params['_query']       = $query;
        $params['_escape']      = true;
        return Mage::getUrl('*/*/*', $params);
    }
}
