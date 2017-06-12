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
 * Class CustomerThreadCore
 *
 * @since 1.0.0
 */
class CustomerThreadCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    public $id_contact;
    public $id_customer;
    public $id_order;
    public $id_product;
    public $status;
    public $email;
    public $token;
    public $date_add;
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'customer_thread',
        'primary' => 'id_customer_thread',
        'fields'  => [
            'id_lang'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_contact'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_shop'     => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_order'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_product'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'email'       => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'size' => 254],
            'token'       => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'status'      => ['type' => self::TYPE_STRING],
            'date_add'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
    protected $webserviceParameters = [
        'fields'       => [
            'id_lang'     => [
                'xlink_resource' => 'languages',
            ],
            'id_shop'     => [
                'xlink_resource' => 'shops',
            ],
            'id_customer' => [
                'xlink_resource' => 'customers',
            ],
            'id_order'    => [
                'xlink_resource' => 'orders',
            ],
            'id_product'  => [
                'xlink_resource' => 'products',
            ],
        ],
        'associations' => [
            'customer_messages' => [
                'resource' => 'customer_message',
                'id'       => ['required' => true],
            ],
        ],
    ];

    /**
     * @param      $idCustomer
     * @param null $read
     * @param null $idOrder
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getCustomerMessages($idCustomer, $read = null, $idOrder = null)
    {
        $sql = 'SELECT *
			FROM '._DB_PREFIX_.'customer_thread ct
			LEFT JOIN '._DB_PREFIX_.'customer_message cm
				ON ct.id_customer_thread = cm.id_customer_thread
			WHERE id_customer = '.(int) $idCustomer;

        if ($read !== null) {
            $sql .= ' AND cm.`read` = '.(int) $read;
        }
        if ($idOrder !== null) {
            $sql .= ' AND ct.`id_order` = '.(int) $idOrder;
        }

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param string $email
     * @param int    $idOrder
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdCustomerThreadByEmailAndIdOrder($email, $idOrder)
    {
        return Db::getInstance()->getValue(
            '
			SELECT cm.id_customer_thread
			FROM '._DB_PREFIX_.'customer_thread cm
			WHERE cm.email = \''.pSQL($email).'\'
				AND cm.id_shop = '.(int) Context::getContext()->shop->id.'
				AND cm.id_order = '.(int) $idOrder
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getContacts()
    {
        return Db::getInstance()->executeS(
            '
			SELECT cl.*, COUNT(*) AS total, (
				SELECT id_customer_thread
				FROM '._DB_PREFIX_.'customer_thread ct2
				WHERE status = "open" AND ct.id_contact = ct2.id_contact
				'.Shop::addSqlRestriction().'
				ORDER BY date_upd ASC
				LIMIT 1
			) AS id_customer_thread
			FROM '._DB_PREFIX_.'customer_thread ct
			LEFT JOIN '._DB_PREFIX_.'contact_lang cl
				ON (cl.id_contact = ct.id_contact AND cl.id_lang = '.(int) Context::getContext()->language->id.')
			WHERE ct.status = "open"
				AND ct.id_contact IS NOT NULL
				AND cl.id_contact IS NOT NULL
				'.Shop::addSqlRestriction().'
			GROUP BY ct.id_contact HAVING COUNT(*) > 0
		'
        );
    }

    /**
     * @param string|null $where
     *
     * @return int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getTotalCustomerThreads($where = null)
    {
        if (is_null($where)) {
            return (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(*)
				FROM '._DB_PREFIX_.'customer_thread
				WHERE 1 '.Shop::addSqlRestriction()
            );
        } else {
            return (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(*)
				FROM '._DB_PREFIX_.'customer_thread
				WHERE '.$where.Shop::addSqlRestriction()
            );
        }
    }

    /**
     * @param int $idCustomerThread
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getMessageCustomerThreads($idCustomerThread)
    {
        return Db::getInstance()->executeS(
            '
			SELECT ct.*, cm.*, cl.name subject, CONCAT(e.firstname, \' \', e.lastname) employee_name,
				CONCAT(c.firstname, \' \', c.lastname) customer_name, c.firstname
			FROM '._DB_PREFIX_.'customer_thread ct
			LEFT JOIN '._DB_PREFIX_.'customer_message cm
				ON (ct.id_customer_thread = cm.id_customer_thread)
			LEFT JOIN '._DB_PREFIX_.'contact_lang cl
				ON (cl.id_contact = ct.id_contact AND cl.id_lang = '.(int) Context::getContext()->language->id.')
			LEFT JOIN '._DB_PREFIX_.'employee e
				ON e.id_employee = cm.id_employee
			LEFT JOIN '._DB_PREFIX_.'customer c
				ON (IFNULL(ct.id_customer, ct.email) = IFNULL(c.id_customer, c.email))
			WHERE ct.id_customer_thread = '.(int) $idCustomerThread.'
			ORDER BY cm.date_add ASC
		'
        );
    }

    /**
     * @param int $idCustomerThread
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNextThread($idCustomerThread)
    {
        $context = Context::getContext();

        return Db::getInstance()->getValue(
            '
			SELECT id_customer_thread
			FROM '._DB_PREFIX_.'customer_thread ct
			WHERE ct.status = "open"
			AND ct.date_upd = (
				SELECT date_add FROM '._DB_PREFIX_.'customer_message
				WHERE (id_employee IS NULL OR id_employee = 0)
					AND id_customer_thread = '.(int) $idCustomerThread.'
				ORDER BY date_add DESC LIMIT 1
			)
			'.($context->cookie->{'customer_threadFilter_cl!id_contact'} ?
                'AND ct.id_contact = '.(int) $context->cookie->{'customer_threadFilter_cl!id_contact'} : '').'
			'.($context->cookie->{'customer_threadFilter_l!id_lang'} ?
                'AND ct.id_lang = '.(int) $context->cookie->{'customer_threadFilter_l!id_lang'} : '').
            ' ORDER BY ct.date_upd ASC
		'
        );
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsCustomerMessages()
    {
        return Db::getInstance()->executeS(
            '
		SELECT `id_customer_message` id
		FROM `'._DB_PREFIX_.'customer_message`
		WHERE `id_customer_thread` = '.(int) $this->id
        );
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!Validate::isUnsignedId($this->id)) {
            return false;
        }

        $return = true;
        $result = Db::getInstance()->executeS(
            '
			SELECT `id_customer_message`
			FROM `'._DB_PREFIX_.'customer_message`
			WHERE `id_customer_thread` = '.(int) $this->id
        );

        if (count($result)) {
            foreach ($result as $res) {
                $message = new CustomerMessage((int) $res['id_customer_message']);
                if (!Validate::isLoadedObject($message)) {
                    $return = false;
                } else {
                    $return &= $message->delete();
                }
            }
        }
        $return &= parent::delete();

        return $return;
    }
}
