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
 * Class AverageTaxOfProductsTaxCalculator
 *
 * @since   1.0.0
 * @version 1.0.0 Initial version
 */
class AverageTaxOfProductsTaxCalculator
{
    protected $id_order;
    protected $configuration;
    protected $db;

    public $computation_method = 'average_tax_of_products';

    /**
     * AverageTaxOfProductsTaxCalculator constructor.
     *
     * @param Core_Foundation_Database_DatabaseInterface $db
     * @param Core_Business_ConfigurationInterface       $configuration
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct(Core_Foundation_Database_DatabaseInterface $db, Core_Business_ConfigurationInterface $configuration)
    {
        $this->db = $db;
        $this->configuration = $configuration;
    }

    /**
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getProductTaxes()
    {
        $prefix = $this->configuration->get('_DB_PREFIX_');

        $sql = 'SELECT t.id_tax, t.rate, od.total_price_tax_excl FROM '.$prefix.'orders o
                INNER JOIN '.$prefix.'order_detail od ON od.id_order = o.id_order
                INNER JOIN '.$prefix.'order_detail_tax odt ON odt.id_order_detail = od.id_order_detail
                INNER JOIN '.$prefix.'tax t ON t.id_tax = odt.id_tax
                WHERE o.id_order = '.(int) $this->id_order;

        return $this->db->select($sql);
    }

    /**
     * @param $idOrder
     *
     * @return $this
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setIdOrder($idOrder)
    {
        $this->id_order = $idOrder;

        return $this;
    }

    /**
     * @param      $priceBeforeTax
     * @param null $priceAfterTax
     * @param int  $roundPrecision
     * @param null $roundMode
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getTaxesAmount($priceBeforeTax, $priceAfterTax = null, $roundPrecision = 2, $roundMode = null)
    {
        $amounts = [];
        $totalBase = 0;

        foreach ($this->getProductTaxes() as $row) {
            if (!array_key_exists($row['id_tax'], $amounts)) {
                $amounts[$row['id_tax']] = [
                    'rate' => $row['rate'],
                    'base' => 0,
                ];
            }

            $amounts[$row['id_tax']]['base'] += $row['total_price_tax_excl'];
            $totalBase += $row['total_price_tax_excl'];
        }

        $actualTax = 0;
        foreach ($amounts as &$data) {
            $data = Tools::ps_round(
                $priceBeforeTax * ($data['base'] / $totalBase) * $data['rate'] / 100,
                $roundPrecision,
                $roundMode
            );
            $actualTax += $data;
        }
        unset($data);

        if ($priceAfterTax) {
            Tools::spreadAmount(
                $priceAfterTax - $priceBeforeTax - $actualTax,
                $roundPrecision,
                $amounts,
                'id_tax'
            );
        }

        return $amounts;
    }
}
