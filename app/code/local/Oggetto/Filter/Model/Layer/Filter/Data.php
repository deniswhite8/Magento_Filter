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
 * the Oggetto Filter module to newer versions in the future.
 * If you wish to customize the Oggetto Filter module for your needs
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
 * Item data
 *
 * @category   Oggetto
 * @package    Oggetto_Filter
 * @subpackage Model
 * @author     Denis Belov <dbelov@oggettoweb.com>
 */
class Oggetto_Filter_Model_Layer_Filter_Data
{
    /**
     * Initialize filter items
     *
     * @param Mage_Catalog_Model_Layer_Filter_Abstract $filter    Filter
     * @param array                                    $itemsData Items data
     *
     * @return array
     */
    public function _initItems($filter, $itemsData)
    {
        $items = array();
        foreach ($itemsData as $itemData) {
            $items[] = $this->_createItem(
                $filter,
                $itemData['label'],
                $itemData['value'],
                $itemData['selected'],
                $itemData['count']
            );
        }
        return $items;
    }

    /**
     * Create filter item object
     *
     * @param Mage_Catalog_Model_Layer_Filter_Abstract $filter   Filter
     * @param string                                   $label    Label
     * @param mixed                                    $value    Value
     * @param int                                      $selected Selected
     * @param int                                      $count    Count
     *
     * @return Mage_Catalog_Model_Layer_Filter_Item
     */
    public function _createItem($filter, $label, $value, $selected = 0, $count=0)
    {
        return Mage::getModel('catalog/layer_filter_item')
            ->setFilter($filter)
            ->setLabel($label)
            ->setValue($value)
            ->setSelected($selected)
            ->setCount($count);
    }
}
