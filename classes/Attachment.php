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
 * Class AttachmentCore
 *
 * @since 1.0.0
 */
class AttachmentCore extends ObjectModel
{
    public $file;
    public $file_name;
    public $file_size;
    public $name;
    public $mime;
    public $description;

    /** @var int position */
    public $position;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'attachment',
        'primary'   => 'id_attachment',
        'multilang' => true,
        'fields'    => [
            'file'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 40],
            'mime'        => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 128],
            'file_name'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128],
            'file_size'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],

            /* Lang fields */
            'name'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml'],
        ],
    ];

    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false)
    {
        $this->file_size = filesize(_PS_DOWNLOAD_DIR_.$this->file);

        return parent::add($autodate, $nullValues);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function update($nullValues = false)
    {
        $this->file_size = filesize(_PS_DOWNLOAD_DIR_.$this->file);

        return parent::update($nullValues);
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        @unlink(_PS_DOWNLOAD_DIR_.$this->file);

        $products = Db::getInstance()->executeS(
            '
		SELECT id_product
		FROM '._DB_PREFIX_.'product_attachment
		WHERE id_attachment = '.(int) $this->id
        );

        Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'product_attachment WHERE id_attachment = '.(int) $this->id);

        foreach ($products as $product) {
            Product::updateCacheAttachment((int) $product['id_product']);
        }

        return parent::delete();
    }

    /**
     * @param array $attachments
     *
     * @return bool|int
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSelection($attachments)
    {
        $return = 1;
        foreach ($attachments as $id_attachment) {
            $attachment = new Attachment((int) $id_attachment);
            $return &= $attachment->delete();
        }

        return $return;
    }

    /**
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getAttachments($idLang, $idProduct, $include = true)
    {
        return Db::getInstance()->executeS(
            '
			SELECT *
			FROM '._DB_PREFIX_.'attachment a
			LEFT JOIN '._DB_PREFIX_.'attachment_lang al
				ON (a.id_attachment = al.id_attachment AND al.id_lang = '.(int) $idLang.')
			WHERE a.id_attachment '.($include ? 'IN' : 'NOT IN').' (
				SELECT pa.id_attachment
				FROM '._DB_PREFIX_.'product_attachment pa
				WHERE id_product = '.(int) $idProduct.'
			)'
        );
    }

    /**
     * Unassociate $id_product from the current object
     *
     * @param $idProduct int
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function deleteProductAttachments($idProduct)
    {
        $res = Db::getInstance()->execute(
            '
		DELETE FROM '._DB_PREFIX_.'product_attachment
		WHERE id_product = '.(int) $idProduct
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * associate $id_product to the current object.
     *
     * @param int $idProduct id of the product to associate
     *
     * @return bool true if succed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function attachProduct($idProduct)
    {
        $res = Db::getInstance()->execute(
            '
			INSERT INTO '._DB_PREFIX_.'product_attachment
				(id_attachment, id_product) VALUES
				('.(int) $this->id.', '.(int) $idProduct.')'
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * Associate an array of id_attachment $array to the product $id_product
     * and remove eventual previous association
     *
     * @param $idProduct
     * @param $array
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function attachToProduct($idProduct, $array)
    {
        $result1 = Attachment::deleteProductAttachments($idProduct);

        if (is_array($array)) {
            $ids = [];
            foreach ($array as $idAttachment) {
                if ((int) $idAttachment > 0) {
                    $ids[] = ['id_product' => (int) $idProduct, 'id_attachment' => (int) $idAttachment];
                }
            }

            if (!empty($ids)) {
                $result2 = Db::getInstance()->insert('product_attachment', $ids);
            }
        }

        Product::updateCacheAttachment((int) $idProduct);
        if (is_array($array)) {
            return ($result1 && (!isset($result2) || $result2));
        }

        return $result1;
    }

    /**
     * @param $idLang
     * @param $list
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getProductAttached($idLang, $list)
    {
        $idsAttachements = [];
        if (is_array($list)) {
            foreach ($list as $attachement) {
                $idsAttachements[] = $attachement['id_attachment'];
            }

            $sql = 'SELECT * FROM `'._DB_PREFIX_.'product_attachment` pa
					LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (pa.`id_product` = pl.`id_product`'.Shop::addSqlRestrictionOnLang('pl').')
					WHERE `id_attachment` IN ('.implode(',', array_map('intval', $idsAttachements)).')
						AND pl.`id_lang` = '.(int) $idLang;
            $tmp = Db::getInstance()->executeS($sql);
            $productAttachements = [];
            foreach ($tmp as $t) {
                $productAttachements[$t['id_attachment']][] = $t['name'];
            }

            return $productAttachements;
        } else {
            return false;
        }
    }
}
