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
 *  @author    Thirty Bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017 Thirty Bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class OrderPaymentCore
 *
 * @since 1.0.0
 */
class OrderPaymentCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $order_reference;
    public $id_currency;
    public $amount;
    public $payment_method;
    public $conversion_rate;
    public $transaction_id;
    public $card_number;
    public $card_brand;
    public $card_expiration;
    public $card_holder;
    public $date_add;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'order_payment',
        'primary' => 'id_order_payment',
        'fields'  => [
            'order_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 9                       ],
            'id_currency'     => ['type' => self::TYPE_INT,    'validate' => 'isUnsignedId',                     'required' => true],
            'amount'          => ['type' => self::TYPE_FLOAT,  'validate' => 'isNegativePrice',                  'required' => true],
            'payment_method'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'                                       ],
            'conversion_rate' => ['type' => self::TYPE_FLOAT,  'validate' => 'isFloat'                                             ],
            'transaction_id'  => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_number'     => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_brand'      => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_expiration' => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'card_holder'     => ['type' => self::TYPE_STRING, 'validate' => 'isAnything',       'size' => 254                     ],
            'date_add'        => ['type' => self::TYPE_DATE,   'validate' => 'isDate'                                              ],
        ],
    ];

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        if (parent::add($autodate, $nullValues)) {
            Hook::exec('actionPaymentCCAdd', ['paymentCC' => $this]);
            return true;
        }
        return false;
    }

    /**
     * Get the detailed payment of an order
     *
     * @param int $idOrder
     *
*@return array
     *
     * @deprecated 2.0.0
     */
    public static function getByOrderId($idOrder)
    {
        Tools::displayAsDeprecated();
        $order = new Order($idOrder);

        return OrderPayment::getByOrderReference($order->reference);
    }

    /**
     * Get the detailed payment of an order
     *
     * @param int $orderReference
     *
     * @return array
     *
     * @since 1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getByOrderReference($orderReference)
    {
        return ObjectModel::hydrateCollection('OrderPayment',
            Db::getInstance()->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'order_payment`
			WHERE `order_reference` = \''.pSQL($orderReference).'\'')
        );
    }

    /**
     * Get Order Payments By Invoice ID
     *
     * @param int $idInvoice Invoice ID
     *
     * @return PrestaShopCollection Collection of OrderPayment
     */
    public static function getByInvoiceId($idInvoice)
    {
        $payments = Db::getInstance()->executeS('SELECT id_order_payment FROM `'._DB_PREFIX_.'order_invoice_payment` WHERE id_order_invoice = '.(int) $idInvoice);
        if (!$payments) {
            return [];
        }

        $paymentList = [];
        foreach ($payments as $payment) {
            $paymentList[] = $payment['id_order_payment'];
        }

        $payments = new PrestaShopCollection('OrderPayment');
        $payments->where('id_order_payment', 'IN', $paymentList);
        return $payments;
    }

    /**
     * Return order invoice object linked to the payment
     *
     * @param int $idOrder Order Id
     *
     * @since 1.5.0.13
     */
    public function getOrderInvoice($idOrder)
    {
        $res = Db::getInstance()->getValue('
		SELECT id_order_invoice
		FROM `'._DB_PREFIX_.'order_invoice_payment`
		WHERE id_order_payment = '.(int) $this->id.'
		AND id_order = '.(int) $idOrder);

        if (!$res) {
            return false;
        }

        return new OrderInvoice((int) $res);
    }
}
