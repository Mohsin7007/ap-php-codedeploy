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
 * Class OrderSlipControllerCore
 *
 * @since 1.0.0
 */
class OrderSlipControllerCore extends FrontController
{
    // @codingStandardsIgnoreStart
    /** @var bool $auth */
    public $auth = true;
    /** @var string $php_self */
    public $php_self = 'order-slip';
    /** @var string $authRedirection */
    public $authRedirection = 'order-slip';
    /** @var bool $ssl */
    public $ssl = true;
    // @codingStandardsIgnoreEnd

    /**
     * Set media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addCSS([_THEME_CSS_DIR_.'history.css', _THEME_CSS_DIR_.'addresses.css']);
        $this->addJqueryPlugin(['scrollTo', 'footable', 'footable-sort']);
        $this->addJS(
            [
                _THEME_JS_DIR_.'history.js',
                _THEME_JS_DIR_.'tools.js',
            ] // retro compat themes 1.5
        );
    }

    /**
     * Assign template vars related to page content
     *
     * @see FrontController::initContent()
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign('ordersSlip', OrderSlip::getOrdersSlip((int) $this->context->cookie->id_customer));
        $this->setTemplate(_PS_THEME_DIR_.'order-slip.tpl');
    }
}
