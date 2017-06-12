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
 * Class RangePriceCore
 *
 * @since 1.0.0
 */
class RangePriceCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_carrier;
    public $delimiter1;
    public $delimiter2;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'range_price',
        'primary' => 'id_range_price',
        'fields'  => [
            'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true],
            'delimiter1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
            'delimiter2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true],
        ],
    ];

    protected $webserviceParameters = [
        'objectsNodeName' => 'price_ranges',
        'objectNodeName'  => 'price_range',
        'fields'          => [
            'id_carrier' => ['xlink_resource' => 'carriers'],
        ],
    ];

    /**
     * Override add to create delivery value for all zones
     *
     * @see     classes/ObjectModelCore::add()
     *
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (!parent::add($autodate, $nullValues) || !Validate::isLoadedObject($this)) {
            return false;
        }
        if (defined('TB_INSTALLATION_IN_PROGRESS')) {
            return true;
        }
        $carrier = new Carrier((int) $this->id_carrier);
        $priceList = [];
        foreach ($carrier->getZones() as $zone) {
            $priceList[] = [
                'id_range_price'  => (int) $this->id,
                'id_range_weight' => null,
                'id_carrier'      => (int) $this->id_carrier,
                'id_zone'         => (int) $zone['id_zone'],
                'price'           => 0,
            ];
        }
        $carrier->addDeliveryPrice($priceList);

        return true;
    }

    /**
     * Get all available price ranges
     *
     * @return array Ranges
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getRanges($idCarrier)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
            SELECT *
            FROM `'._DB_PREFIX_.'range_price`
            WHERE `id_carrier` = '.(int) $idCarrier.'
            ORDER BY `delimiter1` ASC'
        );
    }

    /**
     * @param      $idCarrier
     * @param      $delimiter1
     * @param      $delimiter2
     * @param null $idReference
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function rangeExist($idCarrier, $delimiter1, $delimiter2, $idReference = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
            SELECT count(*)
            FROM `'._DB_PREFIX_.'range_price` rp'.
            (is_null($idCarrier) && $idReference ? '
            INNER JOIN `'._DB_PREFIX_.'carrier` c on (rp.`id_carrier` = c.`id_carrier`)' : '').'
            WHERE'.
            ($idCarrier ? ' `id_carrier` = '.(int) $idCarrier : '').
            (is_null($idCarrier) && $idReference ? ' c.`id_reference` = '.(int) $idReference : '').'
            AND `delimiter1` = '.(float) $delimiter1.' AND `delimiter2` = '.(float) $delimiter2
        );
    }

    /**
     * @param      $id_carrier
     * @param      $delimiter1
     * @param      $delimiter2
     * @param null $id_rang
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isOverlapping($id_carrier, $delimiter1, $delimiter2, $id_rang = null)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            '
            SELECT count(*)
            FROM `'._DB_PREFIX_.'range_price`
            WHERE `id_carrier` = '.(int) $id_carrier.'
            AND ((`delimiter1` >= '.(float) $delimiter1.' AND `delimiter1` < '.(float) $delimiter2.')
                OR (`delimiter2` > '.(float) $delimiter1.' AND `delimiter2` < '.(float) $delimiter2.')
                OR ('.(float) $delimiter1.' > `delimiter1` AND '.(float) $delimiter1.' < `delimiter2`)
                OR ('.(float) $delimiter2.' < `delimiter1` AND '.(float) $delimiter2.' > `delimiter2`)
            )
            '.(!is_null($id_rang) ? ' AND `id_range_price` != '.(int) $id_rang : '')
        );
    }
}
