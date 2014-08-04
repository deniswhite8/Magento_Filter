<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Layer category filter abstract model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Oggetto_Filter_Model_Layer_Filter_Abstract
{
    /**
     * Initialize filter items
     *
     * @param array $itemsData Items data
     * @return  Mage_Catalog_Model_Layer_Filter_Abstract
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
     * @param string $label    Label
     * @param mixed  $value    Value
     * @param int    $selected Selected
     * @param int    $count    Count
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
