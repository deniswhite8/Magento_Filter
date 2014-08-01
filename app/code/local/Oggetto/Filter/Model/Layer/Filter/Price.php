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
 * Layer price filter
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */

/**
 * @method Mage_Catalog_Model_Layer_Filter_Price setInterval(array)
 * @method array getInterval()
 */
class Oggetto_Filter_Model_Layer_Filter_Price extends Mage_Catalog_Model_Layer_Filter_Price
{
    /**
     * Filter values
     * @var array
     */
    protected $_filterValues;


    /**
     * Get price range for building filter steps
     *
     * @return int
     */
    public function getPriceRange()
    {
        $range = $this->getData('price_range');
        if (!$range) {
            $currentCategory = Mage::registry('current_category_filter');
            if ($currentCategory) {
                $range = $currentCategory->getFilterPriceRange();
            } else {
                $range = $this->getLayer()->getCurrentCategory()->getFilterPriceRange();
            }

            $maxPrice = $this->getMaxPriceInt();
            if (!$range) {
                $calculation = Mage::app()->getStore()->getConfig(self::XML_PATH_RANGE_CALCULATION);
                if ($calculation == self::RANGE_CALCULATION_AUTO) {
                    $range = pow(10, (strlen(floor($maxPrice)) - 1));
                } else {
                    $range = (float)Mage::app()->getStore()->getConfig(self::XML_PATH_RANGE_STEP);
                }
            }

            $this->setData('price_range', $range);
        }

        return $range;
    }

    /**
     * Get maximum price from layer products set
     *
     * @return float
     */
    public function getMaxPriceInt()
    {
        $maxPrice = $this->getData('max_price_int');
        if (is_null($maxPrice)) {
            $collection = $this->getLayer()->getProductCollection();
            $wherePart = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
            $groupPart = $collection->getSelect()->getPart(Zend_Db_Select::GROUP);
            $collection->getSelect()->reset(Zend_Db_Select::WHERE);
            $collection->getSelect()->reset(Zend_Db_Select::GROUP);
            $maxPrice = $collection->getMaxPrice();
            $collection->getSelect()->setPart(Zend_Db_Select::WHERE, $wherePart);
            $collection->getSelect()->setPart(Zend_Db_Select::GROUP, $groupPart);
            $maxPrice = floor($maxPrice);
            $this->setData('max_price_int', $maxPrice);
        }

        return $maxPrice;
    }

    /**
     * Get additional request param data
     *
     * @return string
     */
    protected function _getAdditionalRequestData()
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $result = '';
        $appliedInterval = $this->getInterval();
        if ($appliedInterval) {
            $result = $separator . $appliedInterval[0] . '-' . $appliedInterval[1];
            $priorIntervals = $this->getResetValue();
            if ($priorIntervals) {
                $result .= $separator . $priorIntervals;
            }
        }

        return $result;
    }

    /**
     * Get data for build price filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        if (Mage::app()->getStore()->getConfig(self::XML_PATH_RANGE_CALCULATION) == self::RANGE_CALCULATION_IMPROVED) {
            return $this->_getCalculatedItemsData();
        }

        $range      = $this->getPriceRange();
        $dbRanges   = $this->getRangeItemCounts($range);
        $data       = array();

        if (!empty($dbRanges)) {
            $lastIndex = floor((round(($this->getMaxPriceInt()) * 1, 2)) / $range) + 1;

            foreach ($dbRanges as $index => $count) {
                $fromPrice = ($index == 1) ? '' : (($index - 1) * $range);
                $toPrice = ($index == $lastIndex) ? '' : ($index * $range);

                $data[] = array(
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'value' => $fromPrice . '-' . $toPrice,
                    'count' => $count,
                    'selected' => in_array(array($fromPrice, $toPrice), $this->_filterValues)
                );
            }
        }

        return $data;
    }

    /**
     * Apply price range filter
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param $filterBlock
     *
     * @return Mage_Catalog_Model_Layer_Filter_Price
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        //validate filter
        $filterParams = explode($separator, $filter);
//        $filter = $this->_validateFilter($filterParams[0]);
//        if (!$filter) {
//            return $this;
//        }
//
//        list($from, $to) = $filter;
//        $this->setInterval(array($from, $to));

        $priorFilters = array();
        foreach ($filterParams as $filterParam) {
            $filter = $this->_validateFilter($filterParam);
            if (!$filter) {
                break;
            }

            list($from, $to) = $filter;
            $this->setInterval(array($from, $to));
            $priorFilters[] = $filter;

            $this->getLayer()->getState()->addFilter($this->_createItem(
                $this->_renderRangeLabel(empty($from) ? 0 : $from, $to),
                $filter
            ));
        }

        $this->_filterValues = $priorFilters;

        $this->setPriorIntervals($priorFilters);
        $this->_applyPriceRange();

        return $this;
    }



    /**
     * Get filter value for reset current filter state
     *
     * @return null|string
     */
    public function getResetValue($filterValue)
    {
        $separator = Mage::helper('oggetto_filter/data')->getSeparator();

        $priorIntervals = $this->getPriorIntervals();
        $value = array();
        if ($priorIntervals) {
            foreach ($priorIntervals as $priorInterval) {
                if ($priorInterval !== $filterValue) {
                    $value[] = implode('-', $priorInterval);
                }
            }
            return implode($separator, $value);
        }
        return parent::getResetValue();
    }


    /**
     * Initialize filter items
     *
     * @return  Mage_Catalog_Model_Layer_Filter_Abstract
     */
    protected function _initItems()
    {
        $data = $this->_getItemsData();
        $items = array();
        foreach ($data as $itemData) {
            $items[] = $this->_createItem(
                $itemData['label'],
                $itemData['value'],
                $itemData['selected'],
                $itemData['count']
            );
        }
        $this->_items = $items;
        return $this;
    }

    /**
     * Create filter item object
     *
     * @param   string $label
     * @param   mixed $value
     * @param   int $count
     * @return  Mage_Catalog_Model_Layer_Filter_Item
     */
    protected function _createItem($label, $value, $selected = 0, $count=0)
    {
        return Mage::getModel('catalog/layer_filter_item')
            ->setFilter($this)
            ->setLabel($label)
            ->setValue($value)
            ->setSelected($selected)
            ->setCount($count);
    }
}
