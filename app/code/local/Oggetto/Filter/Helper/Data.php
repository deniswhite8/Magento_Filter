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
 * @package    Oggetto_Review
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
 * @subpackage Helper
 * @author     Denis Belov <dbelov@oggettoweb.com>
 */
class Oggetto_Filter_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Get separator
     *
     * @return string
     */
    public function getSeparator()
    {
        return ',';
    }
}