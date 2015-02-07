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
 * Helper data
 *
 * @category   Oggetto
 * @package    Oggetto_Filter
 * @subpackage Model
 * @author     Denis Belov <dbelov@oggettoweb.com>
 */
class Oggetto_Filter_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{

    protected $_wherePartStash;
    protected $_groupPartStash;

    /**
     * Clear statistics data
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function clearStatisticsData()
    {
        $this->_pricesCount =
        $this->_maxPrice =
        $this->_minPrice =
        $this->_priceStandardDeviation = null;

        return $this;
    }


    /**
     * Clear price filters, and save it in stash
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function clearPriceFilters()
    {
        $select = $this->getSelect();
        $this->_wherePartStash = $select->getPart(Zend_Db_Select::WHERE);
        $this->_groupPartStash = $select->getPart(Zend_Db_Select::GROUP);
        $select->reset(Zend_Db_Select::WHERE);
        $select->reset(Zend_Db_Select::GROUP);

        $this->clearStatisticsData();

        return $this;
    }

    /**
     * Retrieve price filters from stash
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function retrievePriceFilters()
    {
        $select = $this->getSelect();
        $select->setPart(Zend_Db_Select::WHERE, $this->_wherePartStash);
        $select->setPart(Zend_Db_Select::GROUP, $this->_groupPartStash);

        $this->clearStatisticsData();

        return $this;
    }

    /**
     * Add tax class id attribute to select and join price rules data if needed
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _beforeLoad()
    {
        if (isset($this->_productLimitationFilters['category_id']) &&
            is_array($this->_productLimitationFilters['category_id'])
        ) {
            $this->getSelect()->group('e.entity_id');
        }

        return parent::_beforeLoad();
    }


    /**
     * Add category filter to product collection
     *
     * @param Mage_Catalog_Model_Category $category Category
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function pushCategoryFilter(Mage_Catalog_Model_Category $category)
    {
        if (is_array($this->_productLimitationFilters['category_id'])) {
            $this->_productLimitationFilters['category_id'][] = $category->getId();
        } else {
            $this->_productLimitationFilters['category_id'] = array($category->getId());
        }

        if ($category->getIsAnchor()) {
            unset($this->_productLimitationFilters['category_is_anchor']);
        } else {
            $this->_productLimitationFilters['category_is_anchor'] = 1;
        }

        if ($this->getStoreId() == Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
            $this->_applyZeroStoreProductLimitations();
        } else {
            $this->_applyProductLimitations();
        }

        return $this;
    }

    /**
     * Apply limitation filters to collection
     * Method allows using one time category product index table (or product website table)
     * for different combinations of store_id/category_id/visibility filter states
     * Method supports multiple changes in one collection object for this parameters
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _applyProductLimitations()
    {
        Mage::dispatchEvent('catalog_product_collection_apply_limitations_before', array(
            'collection'  => $this,
            'category_id' => isset($this->_productLimitationFilters['category_id']) ?
                    $this->_productLimitationFilters['category_id'] : null,
        ));
        $this->_prepareProductLimitationFilters();
        $this->_productLimitationJoinWebsite();
        $this->_productLimitationJoinPrice();
        $filters = $this->_productLimitationFilters;

        if (!isset($filters['category_id']) && !isset($filters['visibility'])) {
            return $this;
        }

        $conditions = array(
            'cat_index.product_id=e.entity_id',
            $this->getConnection()->quoteInto('cat_index.store_id=?', $filters['store_id'])
        );
        if (isset($filters['visibility']) && !isset($filters['store_table'])) {
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.visibility IN(?)', $filters['visibility']);
        }

        if (!$this->getFlag('disable_root_category_filter')) {
            if (is_array($filters['category_id'])) {
                $categoryIdConditions = array();
                foreach ($filters['category_id'] as $categoryId) {
                    $categoryIdConditions[] = $this->getConnection()->quoteInto('cat_index.category_id = ?',
                        $categoryId);
                }
                $conditions[] = '(' . join(' OR ', $categoryIdConditions) . ')';
            } else {
                $conditions[] = $this->getConnection()->quoteInto('cat_index.category_id = ?', $filters['category_id']);
            }
        }

        if (isset($filters['category_is_anchor'])) {
            $conditions[] = $this->getConnection()
                ->quoteInto('cat_index.is_parent=?', $filters['category_is_anchor']);
        }

        $joinCond = join(' AND ', $conditions);
        $fromPart = $this->getSelect()->getPart(Zend_Db_Select::FROM);
        if (isset($fromPart['cat_index'])) {
            $fromPart['cat_index']['joinCondition'] = $joinCond;
            $this->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
        } else {
            $this->getSelect()->join(
                array('cat_index' => $this->getTable('catalog/category_product_index')),
                $joinCond,
                array('cat_index_position' => 'position')
            );
        }

        $this->_productLimitationJoinStore();

        Mage::dispatchEvent('catalog_product_collection_apply_limitations_after', array(
            'collection' => $this
        ));

        return $this;
    }


    /**
     * Adding product count to categories collection
     *
     * @param Mage_Eav_Model_Entity_Collection_Abstract $categoryCollection Category collection
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addCountToAllCategories($categoryCollection)
    {
        $isAnchor    = array();
        $isNotAnchor = array();
        foreach ($categoryCollection as $category) {
            if ($category->getIsAnchor()) {
                $isAnchor[]    = $category->getId();
            } else {
                $isNotAnchor[] = $category->getId();
            }
        }
        $productCounts = array();
        if ($isAnchor || $isNotAnchor) {
            $select = $this->getProductCountSelect();

            Mage::dispatchEvent(
                'catalog_product_collection_before_add_count_to_categories',
                array('collection' => $this)
            );

            $stmt = clone $select;
            $stmt->limit(); //reset limits
            $fromPart = $stmt->getPart(Zend_Db_Select::FROM);
            $catIndexJoinConditional = explode(' AND ', $fromPart['cat_index']['joinCondition']);
            $catIndexJoinConditional = array_slice($catIndexJoinConditional, 0, 3);
            $fromPart['cat_index']['joinCondition'] = implode(' AND ', $catIndexJoinConditional);
            $stmt->setPart(Zend_Db_Select::FROM, $fromPart);

            if ($isAnchor) {
                $stmt->where('count_table.category_id IN (?)', $isAnchor);
            }
            if ($isNotAnchor) {
                $stmt->where('count_table.category_id IN (?)', $isNotAnchor);
                $stmt->where('count_table.is_parent = 1');
            }

            $productCounts += $this->getConnection()->fetchPairs($stmt);
            $stmt = null;
            $select = null;
            $this->unsProductCountSelect();
        }

        foreach ($categoryCollection as $category) {
            $_count = 0;
            if (isset($productCounts[$category->getId()])) {
                $_count = $productCounts[$category->getId()];
            }
            $category->setProductCount($_count);
        }

        return $this;
    }
}
