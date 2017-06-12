<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class CompareProductCore
 *
 * @since 1.0.0
 */
class CompareProductCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'compare',
        'primary' => 'id_compare',
        'fields'  => [
            'id_compare'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        ],
    ];
    public $id_compare;
    public $id_customer;
    public $date_add;
    // @codingStandardsIgnoreEnd
    public $date_upd;

    /**
     * Get all compare products of the customer
     *
     * @param int $id_customer
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCompareProducts($idCompare)
    {
        $results = Db::getInstance()->executeS(
            '
		SELECT DISTINCT `id_product`
		FROM `'._DB_PREFIX_.'compare` c
		LEFT JOIN `'._DB_PREFIX_.'compare_product` cp ON (cp.`id_compare` = c.`id_compare`)
		WHERE cp.`id_compare` = '.(int) ($idCompare)
        );

        $compareProducts = null;

        if ($results) {
            foreach ($results as $result) {
                $compareProducts[] = (int) $result['id_product'];
            }
        }

        return $compareProducts;
    }

    /**
     * Add a compare product for the customer
     *
     * @param int $id_customer , int $id_product
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function addCompareProduct($idCompare, $idProduct)
    {
        // Check if compare row exists
        $idCompare = Db::getInstance()->getValue(
            '
			SELECT `id_compare`
			FROM `'._DB_PREFIX_.'compare`
			WHERE `id_compare` = '.(int) $idCompare
        );

        if (!$idCompare) {
            $idCustomer = false;
            if (Context::getContext()->customer) {
                $idCustomer = Context::getContext()->customer->id;
            }
            $sql = Db::getInstance()->execute(
                '
			INSERT INTO `'._DB_PREFIX_.'compare` (`id_compare`, `id_customer`) VALUES (NULL, "'.($idCustomer ? $idCustomer : '0').'")'
            );
            if ($sql) {
                $idCompare = Db::getInstance()->getValue('SELECT MAX(`id_compare`) FROM `'._DB_PREFIX_.'compare`');
                Context::getContext()->cookie->id_compare = $idCompare;
            }
        }

        return Db::getInstance()->execute(
            '
			INSERT IGNORE INTO `'._DB_PREFIX_.'compare_product` (`id_compare`, `id_product`, `date_add`, `date_upd`)
			VALUES ('.(int) ($idCompare).', '.(int) ($idProduct).', NOW(), NOW())'
        );
    }

    /**
     * Remove a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function removeCompareProduct($idCompare, $idProduct)
    {
        return Db::getInstance()->execute(
            '
		DELETE cp FROM `'._DB_PREFIX_.'compare_product` cp, `'._DB_PREFIX_.'compare` c
		WHERE cp.`id_compare`=c.`id_compare`
		AND cp.`id_product` = '.(int) $idProduct.'
		AND c.`id_compare` = '.(int) $idCompare
        );
    }

    /**
     * Get the number of compare products of the customer
     *
     * @param int $idCompare
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNumberProducts($idCompare)
    {
        return (int) (Db::getInstance()->getValue(
            '
			SELECT count(`id_compare`)
			FROM `'._DB_PREFIX_.'compare_product`
			WHERE `id_compare` = '.(int) ($idCompare)
        ));
    }

    /**
     * Clean entries which are older than the period
     *
     * @param string $period
     *
     * @return void
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanCompareProducts($period = null)
    {
        if ($period !== null) {
            Tools::displayParameterAsDeprecated('period');
        }

        Db::getInstance()->execute(
            '
        DELETE cp, c FROM `'._DB_PREFIX_.'compare_product` cp, `'._DB_PREFIX_.'compare` c
        WHERE cp.date_upd < DATE_SUB(NOW(), INTERVAL 1 WEEK) AND c.`id_compare`=cp.`id_compare`'
        );
    }

    /**
     * Get the id_compare by id_customer
     *
     * @param int $idCustomer
     *
     * @return int $id_compare
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdCompareByIdCustomer($idCustomer)
    {
        return (int) Db::getInstance()->getValue(
            '
		SELECT `id_compare`
		FROM `'._DB_PREFIX_.'compare`
		WHERE `id_customer`= '.(int) $idCustomer
        );
    }
}