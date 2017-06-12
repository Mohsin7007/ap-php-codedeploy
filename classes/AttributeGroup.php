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
 * Class AttributeGroupCore
 *
 * @since 1.0.0
 */
class AttributeGroupCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'     => 'attribute_group',
        'primary'   => 'id_attribute_group',
        'multilang' => true,
        'fields'    => [
            'is_color_group' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'group_type'     => ['type' => self::TYPE_STRING, 'required' => true],
            'position'       => ['type' => self::TYPE_INT, 'validate' => 'isInt'],

            /* Lang fields */
            'name'           => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'public_name'    => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
        ],
    ];
    /** @var string Name */
    public $name;
    public $is_color_group;
    public $position;
    public $group_type;
    // @codingStandardsIgnoreEnd
    /** @var string Public Name */

    public $public_name;
    protected $webserviceParameters = [
        'objectsNodeName' => 'product_options',
        'objectNodeName'  => 'product_option',
        'fields'          => [],
        'associations'    => [
            'product_option_values' => [
                'resource' => 'product_option_value',
                'fields'   => [
                    'id' => [],
                ],
            ],
        ],
    ];

    /**
     * Get all attributes for a given language / group
     *
     * @param int  $idLang           Language id
     * @param bool $idAttributeGroup Attribute group id
     *
     * @return array Attributes
     */
    public static function getAttributes($idLang, $idAttributeGroup)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        return Db::getInstance()->executeS(
            '
			SELECT *
			FROM `'._DB_PREFIX_.'attribute` a
			'.Shop::addSqlAssociation('attribute', 'a').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.(int) $idLang.')
			WHERE a.`id_attribute_group` = '.(int) $idAttributeGroup.'
			ORDER BY `position` ASC
		'
        );
    }

    /**
     * Get all attributes groups for a given language
     *
     * @param int $idLang Language id
     *
     * @return array Attributes groups
     */
    public static function getAttributesGroups($idLang)
    {
        if (!Combination::isFeatureActive()) {
            return [];
        }

        return Db::getInstance()->executeS(
            '
			SELECT DISTINCT agl.`name`, ag.*, agl.*
			FROM `'._DB_PREFIX_.'attribute_group` ag
			'.Shop::addSqlAssociation('attribute_group', 'ag').'
			LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl
				ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND `id_lang` = '.(int) $idLang.')
			ORDER BY `name` ASC
		'
        );
    }

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
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }

        if ($this->position <= 0) {
            $this->position = AttributeGroup::getHigherPosition() + 1;
        }

        $return = parent::add($autodate, true);
        Hook::exec('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);

        return $return;
    }

    /**
     * getHigherPosition
     *
     * Get the higher group attribute position
     *
     * @return int $position
     */
    public static function getHigherPosition()
    {
        $sql = 'SELECT MAX(`position`)
				FROM `'._DB_PREFIX_.'attribute_group`';
        $position = DB::getInstance()->getValue($sql);

        return (is_numeric($position)) ? $position : -1;
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
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }

        $return = parent::update($nullValues);
        Hook::exec('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);

        return $return;
    }

    /**
     * Delete several objects from database
     *
     * return boolean Deletion result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSelection($selection)
    {
        /* Also delete Attributes */
        foreach ($selection as $value) {
            $obj = new AttributeGroup($value);
            if (!$obj->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!$this->hasMultishopEntries() || Shop::getContext() == Shop::CONTEXT_ALL) {
            /* Select children in order to find linked combinations */
            $attributeIds = Db::getInstance()->executeS(
                '
				SELECT `id_attribute`
				FROM `'._DB_PREFIX_.'attribute`
				WHERE `id_attribute_group` = '.(int) $this->id
            );
            if ($attributeIds === false) {
                return false;
            }
            /* Removing attributes to the found combinations */
            $toRemove = [];
            foreach ($attributeIds as $attribute) {
                $toRemove[] = (int) $attribute['id_attribute'];
            }
            if (!empty($toRemove) && Db::getInstance()->execute(
                    '
				DELETE FROM `'._DB_PREFIX_.'product_attribute_combination`
				WHERE `id_attribute`
					IN ('.implode(', ', $toRemove).')'
                ) === false
            ) {
                return false;
            }
            /* Remove combinations if they do not possess attributes anymore */
            if (!AttributeGroup::cleanDeadCombinations()) {
                return false;
            }
            /* Also delete related attributes */
            if (count($toRemove)) {
                if (!Db::getInstance()->execute(
                        '
				DELETE FROM `'._DB_PREFIX_.'attribute_lang`
				WHERE `id_attribute`	IN ('.implode(',', $toRemove).')'
                    ) ||
                    !Db::getInstance()->execute(
                        '
				DELETE FROM `'._DB_PREFIX_.'attribute_shop`
				WHERE `id_attribute`	IN ('.implode(',', $toRemove).')'
                    ) ||
                    !Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'attribute` WHERE `id_attribute_group` = '.(int) $this->id)
                ) {
                    return false;
                }
            }
            $this->cleanPositions();
        }
        $return = parent::delete();
        if ($return) {
            Hook::exec('actionAttributeGroupDelete', ['id_attribute_group' => $this->id]);
        }

        return $return;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanDeadCombinations()
    {
        $attributeCombinations = Db::getInstance()->executeS(
            '
			SELECT pac.`id_attribute`, pa.`id_product_attribute`
			FROM `'._DB_PREFIX_.'product_attribute` pa
			LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac
				ON (pa.`id_product_attribute` = pac.`id_product_attribute`)
		'
        );
        $toRemove = [];
        foreach ($attributeCombinations as $attributeCombination) {
            if ((int) $attributeCombination['id_attribute'] == 0) {
                $toRemove[] = (int) $attributeCombination['id_product_attribute'];
            }
        }
        $return = true;
        if (!empty($toRemove)) {
            foreach ($toRemove as $remove) {
                $combination = new Combination($remove);
                $return &= $combination->delete();
            }
        }

        return $return;
    }

    /**
     * Reorder group attribute position
     * Call it after deleting a group attribute.
     *
     * @return bool $return
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function cleanPositions()
    {
        $return = true;

        $sql = '
			SELECT `id_attribute_group`
			FROM `'._DB_PREFIX_.'attribute_group`
			ORDER BY `position`';
        $result = Db::getInstance()->executeS($sql);

        $i = 0;
        foreach ($result as $value) {
            $return = Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'attribute_group`
				SET `position` = '.(int) $i++.'
				WHERE `id_attribute_group` = '.(int) $value['id_attribute_group']
            );
        }

        return $return;
    }

    /**
     * @param $values
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setWsProductOptionValues($values)
    {
        $ids = [];
        foreach ($values as $value) {
            $ids[] = intval($value['id']);
        }
        Db::getInstance()->execute(
            '
			DELETE FROM `'._DB_PREFIX_.'attribute`
			WHERE `id_attribute_group` = '.(int) $this->id.'
			AND `id_attribute` NOT IN ('.implode(',', $ids).')'
        );
        $ok = true;
        foreach ($values as $value) {
            $result = Db::getInstance()->execute(
                '
				UPDATE `'._DB_PREFIX_.'attribute`
				SET `id_attribute_group` = '.(int) $this->id.'
				WHERE `id_attribute` = '.(int) $value['id']
            );
            if ($result === false) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getWsProductOptionValues()
    {
        $result = Db::getInstance()->executeS(
            '
			SELECT a.id_attribute AS id
			FROM `'._DB_PREFIX_.'attribute` a
			'.Shop::addSqlAssociation('attribute', 'a').'
			WHERE a.id_attribute_group = '.(int) $this->id
        );

        return $result;
    }

    /**
     * Move a group attribute
     *
     * @param bool $way Up (1)  or Down (0)
     * @param int  $position
     *
     * @return bool Update result
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function updatePosition($way, $position)
    {
        if (!$res = Db::getInstance()->executeS(
            '
			SELECT ag.`position`, ag.`id_attribute_group`
			FROM `'._DB_PREFIX_.'attribute_group` ag
			WHERE ag.`id_attribute_group` = '.(int) Tools::getValue('id_attribute_group', 1).'
			ORDER BY ag.`position` ASC'
        )
        ) {
            return false;
        }

        foreach ($res as $groupAttribute) {
            if ((int) $groupAttribute['id_attribute_group'] == (int) $this->id) {
                $movedGroupAttribute = $groupAttribute;
            }
        }

        if (!isset($movedGroupAttribute) || !isset($position)) {
            return false;
        }

        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        return (Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'attribute_group`
			SET `position`= `position` '.($way ? '- 1' : '+ 1').'
			WHERE `position`
			'.($way
                    ? '> '.(int) $movedGroupAttribute['position'].' AND `position` <= '.(int) $position
                    : '< '.(int) $movedGroupAttribute['position'].' AND `position` >= '.(int) $position)
            ) && Db::getInstance()->execute(
                '
			UPDATE `'._DB_PREFIX_.'attribute_group`
			SET `position` = '.(int) $position.'
			WHERE `id_attribute_group`='.(int) $movedGroupAttribute['id_attribute_group']
            )
        );
    }
}
