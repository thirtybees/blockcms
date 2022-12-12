<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

class BlockCMSModel extends ObjectModel
{
    /**
     * @var int
     */
    public $id_cms_block;

    /**
     * @var int
     */
    public $id_cms_category;

    /**
     * @var int
     */
    public $location;

    /**
     * @var int
     */
    public $position;

    /**
     * @var int
     */
    public $display_store;

    const LEFT_COLUMN = 0;
    const RIGHT_COLUMN = 1;
    const FOOTER = 2;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'cms_block',
        'primary' => 'id_cms_block',
        'fields' => array(
            'id_cms_block' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'id_cms_category' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'location' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'display_store' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true)
        ),
    );

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createTables()
    {
        return (
            BlockCMSModel::createCMSBlockTable() &&
            BlockCMSModel::createCMSBlockLangTable() &&
            BlockCMSModel::createCMSBlockPageTable() &&
            BlockCMSModel::createCMSBlockShopTable()
        );
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function dropTables()
    {
        $sql = 'DROP TABLE
			`' . _DB_PREFIX_ . 'cms_block`,
			`' . _DB_PREFIX_ . 'cms_block_page`,
			`' . _DB_PREFIX_ . 'cms_block_lang`,
			`' . _DB_PREFIX_ . 'cms_block_shop`';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createCMSBlockTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cms_block`(
			`id_cms_block` int(10) unsigned NOT NULL auto_increment,
			`id_cms_category` int(10) unsigned NOT NULL,
			`location` tinyint(1) unsigned NOT NULL,
			`position` int(10) unsigned NOT NULL default \'0\',
			`display_store` tinyint(1) unsigned NOT NULL default \'1\',
			PRIMARY KEY (`id_cms_block`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createCMSBlockLangTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cms_block_lang`(
			`id_cms_block` int(10) unsigned NOT NULL,
			`id_lang` int(10) unsigned NOT NULL,
			`name` varchar(40) NOT NULL default \'\',
			PRIMARY KEY (`id_cms_block`, `id_lang`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createCMSBlockPageTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cms_block_page`(
			`id_cms_block_page` int(10) unsigned NOT NULL auto_increment,
			`id_cms_block` int(10) unsigned NOT NULL,
			`id_cms` int(10) unsigned NOT NULL,
			`is_category` tinyint(1) unsigned NOT NULL,
			PRIMARY KEY (`id_cms_block_page`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function createCMSBlockShopTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cms_block_shop` (
			`id_cms_block` int(10) unsigned NOT NULL auto_increment,
			`id_shop` int(10) unsigned NOT NULL,
			PRIMARY KEY (`id_cms_block`, `id_shop`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param int $id_category
     * @param int $location
     * @param int $position
     * @param int $display_store
     *
     * @return false|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function insertCMSBlock($id_category, $location, $position, $display_store)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_block` (`id_cms_category`, `location`, `position`, `display_store`)
			VALUES(' . (int)$id_category . ', ' . (int)$location . ', ' . (int)$position . ', ' . (int)$display_store . ')';

        if (Db::getInstance()->execute($sql)) {
            return (int)Db::getInstance()->Insert_ID();
        }

        return false;
    }

    /**
     * @param int $id_cms_block
     * @param int $id_lang
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function insertCMSBlockLang($id_cms_block, $id_lang)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_block_lang` (`id_cms_block`, `id_lang`, `name`)
			VALUES(' . (int)$id_cms_block . ', ' . (int)$id_lang . ', "' . pSQL(Tools::getValue('block_name_' . $id_lang)) . '")';

        Db::getInstance()->execute($sql);

        return (int)Db::getInstance()->Insert_ID();
    }

    /**
     * @param int $id_cms_block
     * @param int $id_cms
     * @param int $is_category
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function insertCMSBlockPage($id_cms_block, $id_cms, $is_category)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_block_page` (`id_cms_block`, `id_cms`, `is_category`)
			VALUES(' . (int)$id_cms_block . ', ' . (int)$id_cms . ', ' . (int)$is_category . ')';

        Db::getInstance()->execute($sql);

        return (int)Db::getInstance()->Insert_ID();
    }

    /**
     * @param int $id_cms_block
     * @param int $id_shop
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function insertCMSBlockShop($id_cms_block, $id_shop)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'cms_block_shop` (`id_cms_block`, `id_shop`)
				VALUES(' . (int)$id_cms_block . ', ' . (int)$id_shop . ')';

        Db::getInstance()->execute($sql);

        return (int)Db::getInstance()->Insert_ID();
    }

    /**
     * @param int $id_cms_block
     * @param int $id_cms_category
     * @param int $position
     * @param int $location
     * @param int $display_store
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateCMSBlock($id_cms_block, $id_cms_category, $position, $location, $display_store)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `location` = ' . (int)$location . ',
			`id_cms_category` = ' . (int)$id_cms_category . ',
			`position` = ' . (int)$position . ',
			`display_store` = ' . (int)$display_store . '
			WHERE `id_cms_block` = ' . (int)$id_cms_block;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $position
     * @param int $new_position
     * @param int $location
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updatePositions($position, $new_position, $location)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `position` = ' . (int)$new_position . ' WHERE `position` > ' . (int)$position . '
			AND `location` = ' . (int)$location;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $id_cms_block
     * @param int $position
     * @param int $new_position
     * @param int $location
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateCMSBlockPositions($id_cms_block, $position, $new_position, $location)
    {
        $query = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `position` = ' . (int)$new_position . '
			WHERE `position` = ' . (int)$position . '
			AND `location` = ' . (int)$location;

        $sub_query = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `position` = ' . (int)$position . '
			WHERE `id_cms_block` = ' . (int)$id_cms_block;

        if (Db::getInstance()->execute($query)) {
            Db::getInstance()->execute($sub_query);
        }
    }

    /**
     * @param int $id_cms_block
     * @param int $position
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateCMSBlockPosition($id_cms_block, $position)
    {
        $query = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `position` = ' . (int)$position . '
			WHERE `id_cms_block` = ' . (int)$id_cms_block;

        Db::getInstance()->execute($query);
    }

    /**
     * @param int $id_cms_block
     * @param string $block_name
     * @param int $id_lang
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateCMSBlockLang($id_cms_block, $block_name, $id_lang)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'cms_block_lang`
			SET `name` = "' . pSQL($block_name) . '"
			WHERE `id_cms_block` = ' . (int)$id_cms_block . '
			AND `id_lang`= ' . (int)$id_lang;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $display_store
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function updateDisplayStores($display_store)
    {
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'cms_block`
			SET `display_store` = ' . (int)$display_store;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $id_cms_block
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteCMSBlock($id_cms_block)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'cms_block`
				WHERE `id_cms_block` = ' . (int)$id_cms_block;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $id_cms_block
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteCMSBlockPage($id_cms_block)
    {
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'cms_block_page`
				WHERE `id_cms_block` = ' . (int)$id_cms_block;

        Db::getInstance()->execute($sql);
    }

    /**
     * @param int $location
     *
     * @return false|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getMaxPosition($location)
    {
        $sql = 'SELECT COUNT(*)
			FROM `' . _DB_PREFIX_ . 'cms_block`
			WHERE `location` = ' . (int)$location;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    public static function getCMSTitlesFooter()
    {
        $context = Context::getContext();
        $footerCms = Configuration::get('FOOTER_CMS');

        $content = [];

        if (empty($footerCms)) {
            return $content;
        }

        $cmsCategories = explode('|', $footerCms);
        foreach ($cmsCategories as $cmsCategory) {
            $ids = explode('_', $cmsCategory);

            if ($ids[0] == 1 && isset($ids[1])) {
                $query = BlockCMSModel::getBlockName($ids[1]);
                $content[$cmsCategory]['link'] = $context->link->getCMSCategoryLink((int)$ids[1], $query['link_rewrite']);
                $content[$cmsCategory]['meta_title'] = $query['name'];
            } else {
                if ($ids[0] == 0 && isset($ids[1])) {
                    $query = BlockCMSModel::getCMSMetaTitle($ids[1]);
                    $content[$cmsCategory]['link'] = $context->link->getCMSLink((int)$ids[1], $query['link_rewrite']);
                    $content[$cmsCategory]['meta_title'] = $query['meta_title'];
                }
            }
        }

        return $content;
    }

    /**
     * @param int $id
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBlockName($id)
    {
        $sql = 'SELECT cl.`name`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms_category_lang` cl
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category` c
			ON (cl.`id_cms_category` = c.`id_cms_category`)
			WHERE cl.`id_cms_category` = ' . (int)$id . '
			AND (c.`active` = 1 OR c.`id_cms_category` = 1)
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id;

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param int $id
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBlockMetaTitle($id)
    {
        $sql = 'SELECT cl.`name`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms_category_lang` cl
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category` c
			ON (cl.`id_cms_category` = c.`id_cms_category`)
			WHERE cl.`id_cms_category` = ' . (int)$id . '
			AND (c.`active` = 1 OR c.`id_cms_category` = 1)
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id;

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param int $id
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSMetaTitle($id)
    {
        $context = Context::getContext();
        $id_shop = (int)$context->shop->id;

        $where_shop = '';
        if ($id_shop) {
            $where_shop = ' AND cl.`id_shop` = ' . (int)$id_shop;
        }

        $sql = 'SELECT cl.`meta_title`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms_lang` cl
			INNER JOIN `' . _DB_PREFIX_ . 'cms` c
			ON (cl.`id_cms` = c.`id_cms`)
			WHERE cl.`id_cms` = ' . (int)$id . '
			AND (c.`active` = 1 OR c.`id_cms` = 1)' .
            $where_shop . '
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id;

        return Db::getInstance()->getRow($sql);
    }

    /**
     * @param int $location
     * @param int|null $id_shop
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSCategoriesByLocation($location, $id_shop = null)
    {
        $context = Context::getContext();
        $id_shop = static::getShopId($id_shop);

        $where_shop = '';
        if ($id_shop) {
            $where_shop = ' AND ccl.`id_shop` = ' . (int)$id_shop;
        }

        $sql = 'SELECT bc.`id_cms_block`, bc.`id_cms_category`, bc.`display_store`, ccl.`link_rewrite`, ccl.`name` category_name, bcl.`name` block_name
			FROM `' . _DB_PREFIX_ . 'cms_block` bc
			LEFT JOIN `' . _DB_PREFIX_ . 'cms_block_shop` bcs
			ON (bcs.id_cms_block = bc.id_cms_block)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` ccl
			ON (bc.`id_cms_category` = ccl.`id_cms_category`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_block_lang` bcl
			ON (bc.`id_cms_block` = bcl.`id_cms_block`)
			WHERE bc.`location` = ' . (int)$location . '
			AND ccl.`id_lang` = ' . (int)$context->language->id . '
			AND bcl.`id_lang` = ' . (int)$context->language->id . '
			AND bcs.id_shop = ' . ($id_shop ? (int)$id_shop : (int)$context->shop->id) .
            $where_shop . '
			ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param int $id_cms_category
     * @param int|null $id_shop
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSPages($id_cms_category, $id_shop = null)
    {
        $id_shop = static::getShopId($id_shop);

        $where_shop = '';
        if ($id_shop) {
            $where_shop = ' AND cl.`id_shop` = ' . (int)$id_shop;
        }

        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms` c
			INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs
			ON (c.`id_cms` = cs.`id_cms`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
			ON (c.`id_cms` = cl.`id_cms`)
			WHERE c.`id_cms_category` = ' . (int)$id_cms_category . '
			AND cs.`id_shop` = ' . (int)$id_shop . '
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id .
            $where_shop . '
			AND c.`active` = 1
			ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param int $id_block
     * @param int|null $id_shop
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSBlockPages($id_block, $id_shop = null)
    {
        $id_shop = static::getShopId($id_shop);

        $where_shop = '';
        if ($id_shop) {
            $where_shop = ' AND cl.`id_shop` = ' . (int)$id_shop;
        }

        $sql = 'SELECT cl.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms_block_page` bcp
			INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
			ON (bcp.`id_cms` = cl.`id_cms`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms` c
			ON (bcp.`id_cms` = c.`id_cms`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs
			ON (c.`id_cms` = cs.`id_cms`)
			WHERE bcp.`id_cms_block` = ' . (int)$id_block . '
			AND cs.`id_shop` = ' . (int)$id_shop . '
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id .
            $where_shop . '
			AND bcp.`is_category` = 0
			AND c.`active` = 1
			ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param int $id_cms_block
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSBlockPagesCategories($id_cms_block)
    {
        $sql = 'SELECT bcp.`id_cms`, cl.`name`, cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'cms_block_page` bcp
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
			ON (bcp.`id_cms` = cl.`id_cms_category`)
			WHERE bcp.`id_cms_block` = ' . (int)$id_cms_block . '
			AND cl.`id_lang` = ' . (int)Context::getContext()->language->id . '
			AND bcp.`is_category` = 1';

        return Db::getInstance()->executeS($sql);
    }

    /**
     * @param bool $recursive
     * @param int $parent
     * @param int|null $id_shop
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSCategories($recursive = false, $parent = 0, $id_shop = null)
    {
        $id_shop = static::getShopId($id_shop);

        $join_shop = ' INNER JOIN `' . _DB_PREFIX_ . 'cms_category_shop` cs
			ON (bcp.`id_cms_category` = cs.`id_cms_category`)';
        $where_shop = ' AND cs.`id_shop` = ' . (int)$id_shop . ' AND cl.`id_shop` = ' . (int)$id_shop;

        if ($recursive === false) {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
					FROM `' . _DB_PREFIX_ . 'cms_category` bcp' .
                $join_shop . '
					INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
					ON (bcp.`id_cms_category` = cl.`id_cms_category`)
					WHERE cl.`id_lang` = ' . (int)Context::getContext()->language->id .
                $where_shop;
            if ($parent) {
                $sql .= ' AND bcp.`id_parent` = ' . (int)$parent;
            }

            return Db::getInstance()->executeS($sql);
        } else {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
					FROM `' . _DB_PREFIX_ . 'cms_category` bcp' .
                $join_shop . '
					INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
					ON (bcp.`id_cms_category` = cl.`id_cms_category`)
					WHERE cl.`id_lang` = ' . (int)Context::getContext()->language->id .
                $where_shop;
            if ($parent) {
                $sql .= ' AND bcp.`id_parent` = ' . (int)$parent;
            }

            $results = Db::getInstance()->executeS($sql);
            if ($results) {
                $categories = [];
                foreach ($results as $result) {
                    $sub_categories = BlockCMSModel::getCMSCategories(true, $result['id_cms_category']);
                    if ($sub_categories) {
                        $result['sub_categories'] = $sub_categories;
                    }
                    $categories[] = $result;
                }
                return $categories;
            }
            return false;
        }

    }

    /* Get a single CMS block by its ID */
    /**
     * @param int $id_cms_block
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getBlockCMS($id_cms_block)
    {
        $sql = 'SELECT cb.`id_cms_category`, cb.`location`, cb.`position`, cb.`display_store`, cbl.id_lang, cbl.name
			FROM `' . _DB_PREFIX_ . 'cms_block` cb
			LEFT JOIN `' . _DB_PREFIX_ . 'cms_block_lang` cbl
			ON (cbl.`id_cms_block` = cb.`id_cms_block`)
			WHERE cb.`id_cms_block` = ' . (int)$id_cms_block;

        $results = [];
        $cmsBlocks = Db::getInstance()->executeS($sql);
        if ($cmsBlocks) {
            foreach ($cmsBlocks as $cmsBlock) {
                $results[(int)$cmsBlock['id_lang']] = $cmsBlock;
            }
        }
        return $results;
    }

    /* Get all CMS blocks by location */
    /**
     * @param int $location
     * @param int|null $id_shop
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSBlocksByLocation($location, $id_shop = null)
    {
        $id_shop = static::getShopId($id_shop);

        $where_shop = '';
        if ($id_shop) {
            $where_shop = ' AND ccl.`id_shop` = ' . $id_shop;
        }

        $sql = 'SELECT bc.`id_cms_block`, bcl.`name` block_name, ccl.`name` category_name, bc.`position`, bc.`id_cms_category`, bc.`display_store`
			FROM `' . _DB_PREFIX_ . 'cms_block` bc
			LEFT JOIN `' . _DB_PREFIX_ . 'cms_block_shop` bcs
			ON (bcs.id_cms_block = bc.id_cms_block)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` ccl
			ON (bc.`id_cms_category` = ccl.`id_cms_category`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_block_lang` bcl
			ON (bc.`id_cms_block` = bcl.`id_cms_block`)
			WHERE ccl.`id_lang` = ' . (int)Context::getContext()->language->id . '
			AND bcl.`id_lang` = ' . (int)Context::getContext()->language->id . '
			AND bc.`location` = ' . (int)$location .
            $where_shop . '
			AND bcs.id_shop = ' . $id_shop . '
			ORDER BY bc.`position`';

        return Db::getInstance()->executeS($sql);
    }

    /* Get all CMS blocks */
    /**
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSBlocks()
    {
        $sql = 'SELECT bc.`id_cms_block`, bcl.`name` block_name, ccl.`name` category_name, bc.`position`, bc.`id_cms_category`, bc.`display_store`
			FROM `' . _DB_PREFIX_ . 'cms_block` bc
			INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` ccl
			ON (bc.`id_cms_category` = ccl.`id_cms_category`)
			INNER JOIN `' . _DB_PREFIX_ . 'cms_block_lang` bcl
			ON (bc.`id_cms_block` = bcl.`id_cms_block`)
			WHERE ccl.`id_lang` = ' . (int)Context::getContext()->language->id . '
			AND bcl.`id_lang` = ' . (int)Context::getContext()->language->id . '
			ORDER BY bc.`position`';

        return Db::getInstance()->executeS($sql);
    }

    /* Get all CMS blocks */
    /**
     * @param int|null $id_shop
     *
     * @return array|bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAllCMSStructure($id_shop = null)
    {
        $id_shop = static::getShopId($id_shop);

        $categories = BlockCMSModel::getCMSCategories(false, 0, $id_shop);

        foreach ($categories as $key => $value) {
            $categories[$key]['cms_pages'] = BlockCMSModel::getCMSPages($value['id_cms_category'], $id_shop);
        }

        return $categories;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAllCMSTitles()
    {
        $titles = array_merge(
            BlockCMSModel::getCMSTitles(BlockCMSModel::LEFT_COLUMN),
            BlockCMSModel::getCMSTitles(BlockCMSModel::RIGHT_COLUMN)
        );

        foreach ($titles as $key => $title) {
            unset(
                $titles[$key]['category_link'],
                $titles[$key]['category_name'],
                $titles[$key]['categories'],
                $titles[$key]['name']
            );
        }

        return $titles;
    }

    /**
     * @param int $location
     * @param int|null $id_shop
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getCMSTitles($location, $id_shop = null)
    {
        $content = array();
        $context = Context::getContext();
        $cmsCategories = BlockCMSModel::getCMSCategoriesByLocation($location, $id_shop);

        if ($cmsCategories) {
            foreach ($cmsCategories as $cmsCategory) {
                $key = (int)$cmsCategory['id_cms_block'];
                $content[$key]['display_store'] = $cmsCategory['display_store'];
                $content[$key]['cms'] = BlockCMSModel::getCMSBlockPages($cmsCategory['id_cms_block'], $id_shop);
                $links = array();
                if ($content[$key]['cms']) {
                    foreach ($content[$key]['cms'] as $row) {
                        $row['link'] = $context->link->getCMSLink((int)$row['id_cms'], $row['link_rewrite']);
                        $links[] = $row;
                    }
                }

                $content[$key]['cms'] = $links;
                $content[$key]['categories'] = BlockCMSModel::getCMSBlockPagesCategories($cmsCategory['id_cms_block']);

                $links = array();
                if ($content[$key]['categories']) {
                    foreach ($content[$key]['categories'] as $row) {
                        $row['link'] = $context->link->getCMSCategoryLink((int)$row['id_cms'], $row['link_rewrite']);
                        $links[] = $row;
                    }
                }

                $content[$key]['categories'] = $links;
                $content[$key]['name'] = $cmsCategory['block_name'];
                $content[$key]['category_link'] = $context->link->getCMSCategoryLink((int)$cmsCategory['id_cms_category'], $cmsCategory['link_rewrite']);
                $content[$key]['category_name'] = $cmsCategory['category_name'];
            }
        }

        return $content;
    }

    /**
     * @param int|null $id_shop
     *
     * @return int
     */
    protected static function getShopId($id_shop)
    {
        return $id_shop
            ? (int)$id_shop
            : (int)Context::getContext()->shop->id;
    }

}
