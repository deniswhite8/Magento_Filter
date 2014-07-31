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
                    $index = 1;
                    //do {
                        $range = pow(10, (strlen(floor($maxPrice)) - $index));
                        $items = $this->getRangeItemCounts($range);
                        $index++;
                    //}
                    //while($range > self::MIN_RANGE_POWER && count($items) < 2);
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
            $collection->getSelect()->setPart(Zend_Db_Select::WHERE, null);
            $maxPrice = $collection->getMaxPrice();
            $collection->getSelect()->setPart(Zend_Db_Select::WHERE, $wherePart);
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
        $result = '';
        $appliedInterval = $this->getInterval();
        if ($appliedInterval) {
            $result = ',' . $appliedInterval[0] . '-' . $appliedInterval[1];
            $priorIntervals = $this->getResetValue();
            if ($priorIntervals) {
                $result .= ',' . $priorIntervals;
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
        } elseif ($this->getInterval()) {
            //return array();
        }

        $range      = $this->getPriceRange();
        $dbRanges   = $this->getRangeItemCounts($range);
        $data       = array();

        if (!empty($dbRanges)) {
//            $lastIndex = array_keys($dbRanges);
//            $lastIndex = $lastIndex[count($lastIndex) - 1];

            $lastIndex = floor((round(($this->getMaxPriceInt()) * 1, 2)) / $range) + 1;

            foreach ($dbRanges as $index => $count) {
                $fromPrice = ($index == 1) ? '' : (($index - 1) * $range);
                $toPrice = ($index == $lastIndex) ? '' : ($index * $range);

                $data[] = array(
                    'label' => $this->_renderRangeLabel($fromPrice, $toPrice),
                    'value' => $fromPrice . '-' . $toPrice,
                    'count' => $count,
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
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter) {
            return $this;
        }

        //validate filter
        $filterParams = explode(',', $filter);
        $filter = $this->_validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }

        list($from, $to) = $filter;
        $this->setInterval(array($from, $to));

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
        $priorIntervals = $this->getPriorIntervals();
        $value = array();
        if ($priorIntervals) {
            foreach ($priorIntervals as $priorInterval) {
                if ($priorInterval !== $filterValue) {
                    $value[] = implode('-', $priorInterval);
                }
            }
            return implode(',', $value);
        }
        return parent::getResetValue();
    }
}
