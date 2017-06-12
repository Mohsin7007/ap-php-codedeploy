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
 * Class SupplierCore
 *
 * @since 1.0.0
 */
class SupplierCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * Return name from id
     *
     * @param int $id_supplier Supplier ID
     *
     * @return string name
     */
    protected static $cache_name = [];
    /** @var int supplier ID */
    public $id_supplier;
    /** @var string Name */
    public $name;
    /** @var string A short description for the discount */
    public $description;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Friendly URL */
    public $link_rewrite;
    /** @var string Meta title */
    public $meta_title;
    /** @var string Meta keywords */
    public $meta_keywords;
    /** @var string Meta description */
    public $meta_description;
    /** @var bool active */
    public $active;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'supplier',
        'primary'   => 'id_supplier',
        'multilang' => true,
        'fields'    => [
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
            'active'           => ['type' => self::TYPE_BOOL],
            'date_add'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'         => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],

            /* Lang fields */
            'description'      => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml'],
            'meta_title'       => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
            'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
            'meta_keywords'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        ],
    ];

    protected $webserviceParameters = [
        'fields' => [
            'link_rewrite' => ['sqlId' => 'link_rewrite'],
        ],
    ];

    /**
     * SupplierCore constructor.
     *
     * @param null $id
     * @param null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id, $idLang);

        $this->link_rewrite = $this->getLink();
        $this->image_dir = _PS_SUPP_IMG_DIR_;
    }

    /**
     * @return string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getLink()
    {
        return Tools::link_rewrite($this->name);
    }

    /**
     * Return suppliers
     *
     * @param bool $getNbProducts
     * @param int  $idLang
     * @param bool $active
     * @param bool $p
     * @param bool $n
     * @param bool $allGroups
     *
     * @return array Suppliers
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getSuppliers($getNbProducts = false, $idLang = 0, $active = true, $p = false, $n = false, $allGroups = false)
    {
        if (!$idLang) {
            $idLang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::isFeatureActive()) {
            $allGroups = true;
        }

        $query = new DbQuery();
        $query->select('s.*, sl.`description`');
        $query->from('supplier', 's');
        $query->leftJoin('supplier_lang', 'sl', 's.`id_supplier` = sl.`id_supplier` AND sl.`id_lang` = '.(int) $idLang);
        $query->join(Shop::addSqlAssociation('supplier', 's'));
        if ($active) {
            $query->where('s.`active` = 1');
        }
        $query->orderBy(' s.`name` ASC');
        $query->limit($n, ($p - 1) * $n);
        $query->groupBy('s.id_supplier');

        $suppliers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
        if ($suppliers === false) {
            return false;
        }
        if ($getNbProducts) {
            $sqlGroups = '';
            if (!$allGroups) {
                $groups = FrontController::getCurrentCustomerGroups();
                $sqlGroups = (count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
            }

            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
					SELECT  ps.`id_supplier`, COUNT(DISTINCT ps.`id_product`) AS nb_products
					FROM `'._DB_PREFIX_.'product_supplier` ps
					JOIN `'._DB_PREFIX_.'product` p ON (ps.`id_product`= p.`id_product`)
					'.Shop::addSqlAssociation('product', 'p').'
					LEFT JOIN `'._DB_PREFIX_.'supplier` AS m ON (m.`id_supplier`= p.`id_supplier`)
					WHERE ps.id_product_attribute = 0'.
                ($active ? ' AND product_shop.`active` = 1' : '').
                ' AND product_shop.`visibility` NOT IN ("none")'.
                ($allGroups ? '' : '
					AND ps.`id_product` IN (
						SELECT cp.`id_product`
						FROM `'._DB_PREFIX_.'category_group` cg
						LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
						WHERE cg.`id_group` '.$sqlGroups.'
					)').'
					GROUP BY ps.`id_supplier`'
            );

            $counts = [];
            foreach ($results as $result) {
                $counts[(int) $result['id_supplier']] = (int) $result['nb_products'];
            }

            if (count($counts) && is_array($suppliers)) {
                foreach ($suppliers as $key => $supplier) {
                    if (isset($counts[(int) $supplier['id_supplier']])) {
                        $suppliers[$key]['nb_products'] = $counts[(int) $supplier['id_supplier']];
                    } else {
                        $suppliers[$key]['nb_products'] = 0;
                    }
                }
            }
        }

        $nbSuppliers = count($suppliers);
        $rewriteSettings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $nbSuppliers; $i++) {
            $suppliers[$i]['link_rewrite'] = ($rewriteSettings ? Tools::link_rewrite($suppliers[$i]['name']) : 0);
        }

        return $suppliers;
    }

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Indicates whether adding succeeded
     */
    public function add($autoDate = true, $nullValues = false)
    {
        if (parent::add($autoDate, $nullValues)) {
            return true;
        }

        return false;
    }

    /**
     * @param null $nullValues
     *
     * @return bool Indicates whether updating succeeded
     */
    public function update($nullValues = null)
    {
        if ('TB_PAGE_CACHE_ENABLED') {
            PageCache::invalidateEntity('supplier', $this->id);
        }

        return parent::update($nullValues);
    }

    public function delete()
    {
        if ('TB_PAGE_CACHE_ENABLED') {
            PageCache::invalidateEntity('supplier', $this->id);
        }

        return parent::delete();
    }

    /**
     * @param int $idSupplier
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getNameById($idSupplier)
    {
        if (!isset(static::$cache_name[$idSupplier])) {
            static::$cache_name[$idSupplier] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
			SELECT `name` FROM `'._DB_PREFIX_.'supplier` WHERE `id_supplier` = '.(int) $idSupplier
            );
        }

        return static::$cache_name[$idSupplier];
    }

    /**
     * @param $name
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdByName($name)
    {
        $result = Db::getInstance()->getRow(
            '
		SELECT `id_supplier`
		FROM `'._DB_PREFIX_.'supplier`
		WHERE `name` = \''.pSQL($name).'\''
        );

        if (isset($result['id_supplier'])) {
            return (int) $result['id_supplier'];
        }

        return false;
    }

    /**
     * @param      $idSupplier
     * @param      $idLang
     * @param      $p
     * @param      $n
     * @param null $orderBy
     * @param null $orderWay
     * @param bool $getTotal
     * @param bool $active
     * @param bool $active_category
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProducts(
        $idSupplier,
        $idLang,
        $p,
        $n,
        $orderBy = null,
        $orderWay = null,
        $getTotal = false,
        $active = true,
        $activeCategory = true
    ) {
        $context = Context::getContext();
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        if ($p < 1) {
            $p = 1;
        }
        if (empty($orderBy) || $orderBy == 'position') {
            $orderBy = 'name';
        }
        if (empty($orderWay)) {
            $orderWay = 'ASC';
        }

        if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay)) {
            die(Tools::displayError());
        }

        $sqlGroups = '';
        if (Group::isFeatureActive()) {
            $groups = FrontController::getCurrentCustomerGroups();
            $sqlGroups = 'WHERE cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1');
        }

        /* Return only the number of products */
        if ($getTotal) {
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                '
			SELECT COUNT(DISTINCT ps.`id_product`)
			FROM `'._DB_PREFIX_.'product_supplier` ps
			JOIN `'._DB_PREFIX_.'product` p ON (ps.`id_product`= p.`id_product`)
			'.Shop::addSqlAssociation('product', 'p').'
			WHERE ps.`id_supplier` = '.(int) $idSupplier.'
			AND ps.id_product_attribute = 0
			'.($active ? ' AND product_shop.`active` = 1' : '').'
			'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
			AND p.`id_product` IN (
				SELECT cp.`id_product`
				FROM `'._DB_PREFIX_.'category_product` cp
				'.(Group::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.`id_category` = cg.`id_category`)' : '').'
				'.($activeCategory ? ' INNER JOIN `'._DB_PREFIX_.'category` ca ON cp.`id_category` = ca.`id_category` AND ca.`active` = 1' : '').'
				'.$sqlGroups.'
			)'
            );
        }

        $nbDaysNewProduct = Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;

        if (strpos('.', $orderBy) > 0) {
            $orderBy = explode('.', $orderBy);
            $orderBy = pSQL($orderBy[0]).'.`'.pSQL($orderBy[1]).'`';
        }
        $alias = '';
        if (in_array($orderBy, ['price', 'date_add', 'date_upd'])) {
            $alias = 'product_shop.';
        } elseif ($orderBy == 'id_product') {
            $alias = 'p.';
        } elseif ($orderBy == 'manufacturer_name') {
            $orderBy = 'name';
            $alias = 'm.';
        }

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock,
					IFNULL(stock.quantity, 0) as quantity,
					pl.`description`,
					pl.`description_short`,
					pl.`link_rewrite`,
					pl.`meta_description`,
					pl.`meta_keywords`,
					pl.`meta_title`,
					pl.`name`,
					image_shop.`id_image` id_image,
					il.`legend`,
					s.`name` AS supplier_name,
					DATEDIFF(p.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00", INTERVAL '.($nbDaysNewProduct).' DAY)) > 0 AS new,
					m.`name` AS manufacturer_name'.(Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '').'
				 FROM `'._DB_PREFIX_.'product` p
				'.Shop::addSqlAssociation('product', 'p').'
				JOIN `'._DB_PREFIX_.'product_supplier` ps ON (ps.id_product = p.id_product
					AND ps.id_product_attribute = 0) '.
            (Combination::isFeatureActive() ? 'LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')' : '').'
				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = '.(int) $idLang.Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = '.(int) $idLang.')
				LEFT JOIN `'._DB_PREFIX_.'supplier` s ON s.`id_supplier` = p.`id_supplier`
				LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				'.Product::sqlStock('p', 0);

        if (Group::isFeatureActive() || $activeCategory) {
            $sql .= 'JOIN `'._DB_PREFIX_.'category_product` cp ON (p.id_product = cp.id_product)';
            if (Group::isFeatureActive()) {
                $sql .= 'JOIN `'._DB_PREFIX_.'category_group` cg ON (cp.`id_category` = cg.`id_category` AND cg.`id_group` '.(count($groups) ? 'IN ('.implode(',', $groups).')' : '= 1').')';
            }
            if ($activeCategory) {
                $sql .= 'JOIN `'._DB_PREFIX_.'category` ca ON cp.`id_category` = ca.`id_category` AND ca.`active` = 1';
            }
        }

        $sql .= '
				WHERE ps.`id_supplier` = '.(int) $idSupplier.'
					'.($active ? ' AND product_shop.`active` = 1' : '').'
					'.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
				GROUP BY ps.id_product
				ORDER BY '.$alias.pSQL($orderBy).' '.pSQL($orderWay).'
				LIMIT '.(((int) $p - 1) * (int) $n).','.(int) $n;

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        if (!$result) {
            return false;
        }

        if ($orderBy == 'price') {
            Tools::orderbyPrice($result, $orderWay);
        }

        return Product::getProductsProperties($idLang, $result);
    }

    /**
     * @param int $idSupplier
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function supplierExists($idSupplier)
    {
        $query = new DbQuery();
        $query->select('id_supplier');
        $query->from('supplier');
        $query->where('id_supplier = '.(int) $idSupplier);
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

        return ($res > 0);
    }

    /**
     * Gets product informations
     *
     * @since 1.5.0
     *
     * @param int $idSupplier
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductInformationsBySupplier($idSupplier, $idProduct, $idProductAttribute = 0)
    {
        $query = new DbQuery();
        $query->select('product_supplier_reference, product_supplier_price_te, id_currency');
        $query->from('product_supplier');
        $query->where('id_supplier = '.(int) $idSupplier);
        $query->where('id_product = '.(int) $idProduct);
        $query->where('id_product_attribute = '.(int) $idProductAttribute);
        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        if (count($res)) {
            return $res[0];
        }
    }

    /**
     * @param int $idLang
     *
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getProductsLite($idLang)
    {
        $context = Context::getContext();
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }

        $sql = '
			SELECT p.`id_product`,
				   pl.`name`
			FROM `'._DB_PREFIX_.'product` p
			'.Shop::addSqlAssociation('product', 'p').'
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (
				p.`id_product` = pl.`id_product`
				AND pl.`id_lang` = '.(int) $idLang.'
			)
			INNER JOIN `'._DB_PREFIX_.'product_supplier` ps ON (
				ps.`id_product` = p.`id_product`
				AND ps.`id_supplier` = '.(int) $this->id.'
			)
			'.($front ? ' WHERE product_shop.`visibility` IN ("both", "catalog")' : '').'
			GROUP BY p.`id_product`';

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return $res;
    }
}
