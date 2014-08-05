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
 * Helper data
 *
 * @category   Oggetto
 * @package    Oggetto_Filter
 * @subpackage Model
 * @author     Denis Belov <dbelov@oggettoweb.com>
 */
class Oggetto_Filter_Model_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
    /**
     * Filter values
     * @var array
     */
    protected $_filterValues;

    /**
     * Initialize filter items
     *
     * @return  Oggetto_Filter_Model_Layer_Filter_Category
     */
    protected function _initItems()
    {
        $this->_items = Mage::getSingleton('oggetto_filter/layer_filter_data')->_initItems($this,
            $this->_getItemsData());
        return $this;
    }

    /**
     * Create filter item object
     *
     * @param string $label    Label
     * @param mixed  $value    Value
     * @param int    $selected Selected
     * @param int    $count    Count
     *
     * @return Mage_Catalog_Model_Layer_Filter_Item
     */
    protected function _createItem($label, $value, $selected = 0, $count=0)
    {
        return Mage::getSingleton('oggetto_filter/layer_filter_data')->_createItem($this, $label, $value,
            $selected, $count);
    }




    /**
     * Apply attribute option filter to product collection
     *
     * @param Zend_Controller_Request_Abstract $request     Request
     * @param Varien_Object                    $filterBlock Filter block
     *
     * @return Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }

        $this->_filterValues = $filterArray = explode($separator, $filter);
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
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        $key = $this->getLayer()->getStateKey().'_'.$this->_requestVar;
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $options = $attribute->getFrontend()->getSelectOptions();
            $optionsCount = $this->_getResource()->getCount($this);
            $data = array();
            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }
                if (Mage::helper('core/string')->strlen($option['value'])) {
                    $selected = in_array($option['value'], $this->_filterValues);
                    // Check filter type
                    if ($this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
                        if (!empty($optionsCount[$option['value']]) || $selected) {
                            $data[] = array(
                                'label' => $option['label'],
                                'value' => $option['value'],
                                'selected' => $selected,
                                'count' => $optionsCount[$option['value']],
                            );
                        }
                    } else {
                        $data[] = array(
                            'label' => $option['label'],
                            'value' => $option['value'],
                            'selected' => $selected,
                            'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                        );
                    }
                }
            }

            $tags = array(
                Mage_Eav_Model_Entity_Attribute::CACHE_TAG.':'.$attribute->getId()
            );

            $tags = $this->getLayer()->getStateTags($tags);
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }


    /**
     * Get filter value for reset current filter state
     *
     * @param string $filterValue Filter value
     * @return mixed
     */
    public function getResetValue($filterValue)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $params = $this->_filterValues;
        $currentKey = array_search($filterValue, $params);
        unset($params[$currentKey]);
        return implode($separator, $params);
    }
}
