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

/**
 * Catalog Search Controller
 *
 * @category   Oggetto
 * @package    Oggetto_Filter
 * @subpackage controllers
 * @author     Denis Obukhov <d@oggettoweb.com>
 */
require_once Mage::getModuleDir('controllers', 'Mage_CatalogSearch') . DS . 'ResultController.php';

class Oggetto_Filter_ResultController extends Mage_CatalogSearch_ResultController
{
    /**
     * Display search result
     *
     * @return void
     */
    public function indexAction()
    {
        if (!$this->getRequest()->isAjax()) {
            parent::indexAction();
            return;
        }
        $this->loadLayout();
        $jsonData = json_encode(array(
            'filter'   => $this->getLayout()->getBlock('left_first')->toHtml(),
            'products' => $this->getLayout()->getBlock('search.result')->toHtml(),
        ));

        $this->getResponse()->appendBody($jsonData);
    }
}
