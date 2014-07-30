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
class Oggetto_Filter_Model_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }

        $filterArray = explode(',', $filter);
        $text = $this->_getOptionText($filter);

        if (!is_array($text)) {
            $text = array($text);
        }

        $textOk = true;

        foreach ($filterArray as $index => $filterItem) {
            $textOk *= (strlen($text[$index]));
        }

        if ($filter && $textOk) {
            $this->_getResource()->applyFilterToCollection($this, $filterArray);
            foreach ($filterArray as $index => $filterItem) {
                $this->getLayer()->getState()->addFilter($this->_createItem($text[$index], $filterItem));
            }
        }
        return $this;
    }

    /**
     * Get filter value for reset current filter state
     *
     * @param string $paramName   Param name
     * @param string $filterValue Filter value
     *
     * @return mixed
     */
    public function getResetValue($filterValue, $paramName)
    {
        $params = explode(',', Mage::app()->getRequest()->getParam($paramName));
        $currentKey = array_search($filterValue, $params);
        unset($params[$currentKey]);
        return implode(',', $params);
    }
}
