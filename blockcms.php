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

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

include_once(dirname(__FILE__) . '/BlockCMSModel.php');

class BlockCms extends Module
{
    /**
     * @var string
     */
    protected $_html;

    /**
     * @var string
     */
    protected $_display;

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'blockcms';
        $this->tab = 'front_office_features';
        $this->version = '3.0.2';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block CMS');
        $this->description = $this->l('Adds a block with several CMS links.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
    }

    /**
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('leftColumn')
            || !$this->registerHook('rightColumn')
            || !$this->registerHook('header')
            || !$this->registerHook('footer')
            || !$this->registerHook('actionObjectCmsUpdateAfter')
            || !$this->registerHook('actionObjectCmsDeleteAfter')
            || !$this->registerHook('actionShopDataDuplication')
            || !$this->registerHook('actionAdminStoresControllerUpdate_optionsAfter')
            || !BlockCMSModel::createTables()
            || !Configuration::updateValue('FOOTER_CMS', '')
            || !Configuration::updateValue('FOOTER_BLOCK_ACTIVATION', 1)
            || !Configuration::updateValue('FOOTER_POWEREDBY', 1)
            || !Configuration::updateValue('FOOTER_PRICE-DROP', 1)
            || !Configuration::updateValue('FOOTER_NEW-PRODUCTS', 1)
            || !Configuration::updateValue('FOOTER_BEST-SALES', 1)
            || !Configuration::updateValue('FOOTER_CONTACT', 1)
            || !Configuration::updateValue('FOOTER_SITEMAP', 1)
        ) {
            return false;
        }

        $this->_clearCache('blockcms.tpl');

        // Install fixtures for blockcms
        $default = Db::getInstance()->insert('cms_block', array(
            'id_cms_category' => 1,
            'location' => 0,
            'position' => 0,
        ));

        if (!$default) {
            return false;
        }

        $result = true;
        $id_cms_block = Db::getInstance()->Insert_ID();
        $shops = Shop::getShops(true, null, true);

        foreach ($shops as $shop) {
            $result &= Db::getInstance()->insert('cms_block_shop', array(
                'id_cms_block' => $id_cms_block,
                'id_shop' => $shop
            ));
        }

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $result &= Db::getInstance()->insert('cms_block_lang', array(
                'id_cms_block' => $id_cms_block,
                'id_lang' => $lang['id_lang'],
                'name' => $this->l('Information'),
            ));
        }

        $pages = CMS::getCMSPages(null, 1);
        foreach ($pages as $cms) {
            $result &= Db::getInstance()->insert('cms_block_page', array(
                'id_cms_block' => $id_cms_block,
                'id_cms' => $cms['id_cms'],
                'is_category' => 0,
            ));
        }

        return $result;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $this->_clearCache('blockcms.tpl');
        if (!parent::uninstall() ||
            !BlockCMSModel::DropTables() ||
            !Configuration::deleteByName('FOOTER_CMS') ||
            !Configuration::deleteByName('FOOTER_BLOCK_ACTIVATION') ||
            !Configuration::deleteByName('FOOTER_POWEREDBY') ||
            !Configuration::deleteByName('FOOTER_PRICE-DROP') ||
            !Configuration::deleteByName('FOOTER_NEW-PRODUCTS') ||
            !Configuration::deleteByName('FOOTER_BEST-SALES') ||
            !Configuration::deleteByName('FOOTER_CONTACT') ||
            !Configuration::deleteByName('FOOTER_SITEMAP')
        ) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initToolbar()
    {
        $current_index = AdminController::$currentIndex;
        $token = Tools::getAdminTokenLite('AdminModules');
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        if (empty($back)) {
            $back = $current_index . '&amp;configure=' . $this->name . '&token=' . $token;
        }

        $buttons = [];
        switch ($this->_display) {
            case 'add':
                $buttons['cancel'] = array(
                    'href' => $back,
                    'desc' => $this->l('Cancel')
                );
                break;
            case 'edit':
                $buttons['cancel'] = array(
                    'href' => $back,
                    'desc' => $this->l('Cancel')
                );
                break;
            case 'index':
                $buttons['new'] = array(
                    'href' => $current_index . '&amp;configure=' . $this->name . '&amp;token=' . $token . '&amp;addBlockCMS',
                    'desc' => $this->l('Add new')
                );
                break;
            default:
                break;
        }
        return $buttons;
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displayForm()
    {
        $this->context->controller->addJqueryPlugin('tablednd');

        $this->context->controller->addJS(_PS_JS_DIR_ . 'admin/dnd.js');

        $current_index = AdminController::$currentIndex;
        $token = Tools::getAdminTokenLite('AdminModules');

        $this->_display = 'index';

        $configForm = array(
            'legend' => array(
                'title' => $this->l('CMS block configuration'),
                'icon' => 'icon-list-alt'
            ),
            'input' => array(
                array(
                    'type' => 'cms_blocks',
                    'label' => $this->l('CMS Blocks'),
                    'name' => 'cms_blocks',
                    'values' => array(
                        0 => BlockCMSModel::getCMSBlocksByLocation(BlockCMSModel::LEFT_COLUMN, Shop::getContextShopID()),
                        1 => BlockCMSModel::getCMSBlocksByLocation(BlockCMSModel::RIGHT_COLUMN, Shop::getContextShopID()))
                )
            ),
            'buttons' => array(
                'newBlock' => array(
                    'title' => $this->l('New block'),
                    'href' => $current_index . '&amp;configure=' . $this->name . '&amp;token=' . $token . '&amp;addBlockCMS',
                    'class' => 'pull-right',
                    'icon' => 'process-icon-new'
                )
            )
        );

        $linkForm = array(
            'tinymce' => true,
            'legend' => array(
                'title' => $this->l('Configuration of the various links in the footer'),
                'icon' => 'icon-link'
            ),
            'input' => array(
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display various links and information in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'cms_pages',
                    'label' => $this->l('Footer links'),
                    'name' => 'footerBox[]',
                    'values' => BlockCMSModel::getAllCMSStructure(),
                    'desc' => $this->l('Please mark every page that you want to display in the footer CMS block.')
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Footer information'),
                    'name' => 'footer_text',
                    'rows' => 5,
                    'cols' => 60,
                    'lang' => true
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'PS_STORES_DISPLAY_FOOTER',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "Our stores" link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_display_price-drop',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "Price drop" link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_display_new-products',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "New products" link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_display_best-sales',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "Best sales" link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_display_contact',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "Contact us" link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_display_sitemap',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display sitemap link in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'checkbox',
                    'name' => 'cms_footer_powered_by',
                    'values' => array(
                        'query' => array(
                            array(
                                'id' => 'on',
                                'name' => $this->l('Display "Powered by thirty bees" in the footer'),
                                'val' => '1'
                            ),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'name' => 'submitFooterCMS',
                'title' => $this->l('Save'),
            )
        );


        $fieldsValues = [];

        $footer_boxes = explode('|', (string)Configuration::get('FOOTER_CMS'));
        foreach ($footer_boxes as $value) {
            $fieldsValues[$value] = true;
        }

        $fieldsValues['cms_footer_on'] = Configuration::get('FOOTER_BLOCK_ACTIVATION');
        $fieldsValues['cms_footer_powered_by_on'] = Configuration::get('FOOTER_POWEREDBY');
        $fieldsValues['PS_STORES_DISPLAY_FOOTER_on'] = Configuration::get('PS_STORES_DISPLAY_FOOTER');
        $fieldsValues['cms_footer_display_price-drop_on'] = Configuration::get('FOOTER_PRICE-DROP');
        $fieldsValues['cms_footer_display_new-products_on'] = Configuration::get('FOOTER_NEW-PRODUCTS');
        $fieldsValues['cms_footer_display_best-sales_on'] = Configuration::get('FOOTER_BEST-SALES');
        $fieldsValues['cms_footer_display_contact_on'] = Configuration::get('FOOTER_CONTACT');
        $fieldsValues['cms_footer_display_sitemap_on'] = Configuration::get('FOOTER_SITEMAP');

        foreach ($this->getLanguages() as $language) {
            $footer_text = Configuration::get('FOOTER_CMS_TEXT_' . $language['id_lang']);
            $fieldsValues['footer_text'][$language['id_lang']] = $footer_text;
        }

        $helper = $this->initForm();
        $helper->submit_action = '';
        $helper->title = $this->l('CMS Block configuration');

        $helper->fields_value = $fieldsValues;
        $this->_html .= $helper->generateForm([
            ['form' => $configForm],
            ['form' => $linkForm]
        ]);
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function displayAddForm()
    {
        $token = Tools::getAdminTokenLite('AdminModules');
        $back = Tools::safeOutput(Tools::getValue('back', ''));
        $current_index = AdminController::$currentIndex;
        if (empty($back)) {
            $back = $current_index . '&amp;configure=' . $this->name . '&token=' . $token;
        }

        if (Tools::isSubmit('editBlockCMS') && Tools::getValue('id_cms_block')) {
            $this->_display = 'edit';
            $id_cms_block = (int)Tools::getValue('id_cms_block');
            $cmsBlock = BlockCMSModel::getBlockCMS($id_cms_block);
            $cmsBlockCategories = BlockCMSModel::getCMSBlockPagesCategories($id_cms_block);
            $cmsBlockPages = BlockCMSModel::getCMSBlockPages(Tools::getValue('id_cms_block'));
        } else {
            $this->_display = 'add';
        }

        $editForm = array(
            'tinymce' => true,
            'legend' => array(
                'title' => isset($cmsBlock) ? $this->l('Edit the CMS block.') : $this->l('New CMS block'),
                'icon' => isset($cmsBlock) ? 'icon-edit' : 'icon-plus-square'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name of the CMS block'),
                    'name' => 'block_name',
                    'lang' => true,
                    'desc' => $this->l('If you leave this field empty, the block name will use the category name by default.')
                ),
                array(
                    'type' => 'select_category',
                    'label' => $this->l('CMS category'),
                    'name' => 'id_category',
                    'options' => array(
                        'query' => BlockCMSModel::getCMSCategories(true),
                        'id' => 'id_cms_category',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Location'),
                    'name' => 'block_location',
                    'options' => array(
                        'query' => array(
                            array(
                                'id' => BlockCMSModel::LEFT_COLUMN,
                                'name' => $this->l('Left column')),
                            array(
                                'id' => BlockCMSModel::RIGHT_COLUMN,
                                'name' => $this->l('Right column')),
                        ),
                        'id' => 'id',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Add link to Store Locator'),
                    'name' => 'display_stores',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'display_stores_on',
                            'value' => 1,
                            'label' => $this->l('Yes')),
                        array(
                            'id' => 'display_stores_off',
                            'value' => 0,
                            'label' => $this->l('No')),
                    ),
                    'desc' => $this->l('Adds the "Our stores" link at the end of the block.')
                ),
                array(
                    'type' => 'cms_pages',
                    'label' => $this->l('CMS content'),
                    'name' => 'cmsBox[]',
                    'values' => BlockCMSModel::getAllCMSStructure(),
                    'desc' => $this->l('Please mark every page that you want to display in this block.')
                ),
            ),
            'buttons' => array(
                'cancelBlock' => array(
                    'title' => $this->l('Cancel'),
                    'href' => $back,
                    'icon' => 'process-icon-cancel'
                )
            ),
            'submit' => array(
                'name' => 'submitBlockCMS',
                'title' => $this->l('Save'),
            )
        );

        $fieldsValues = [];
        foreach ($this->getLanguages() as $language) {
            if (Tools::getValue('block_name_' . $language['id_lang'])) {
                $fieldsValues['block_name'][$language['id_lang']] = Tools::getValue('block_name_' . $language['id_lang']);
            } else {
                if (isset($cmsBlock) && isset($cmsBlock[$language['id_lang']]['name'])) {
                    $fieldsValues['block_name'][$language['id_lang']] = $cmsBlock[$language['id_lang']]['name'];
                } else {
                    $fieldsValues['block_name'][$language['id_lang']] = '';
                }
            }
        }

        if (Tools::getValue('display_stores')) {
            $fieldsValues['display_stores'] = Tools::getValue('display_stores');
        } else {
            if (isset($cmsBlock) && isset($cmsBlock[1]['display_store'])) {
                $fieldsValues['display_stores'] = $cmsBlock[1]['display_store'];
            } else {
                $fieldsValues['display_stores'] = '';
            }
        }

        if (Tools::getValue('id_category')) {
            $fieldsValues['id_category'] = (int)Tools::getValue('id_category');
        } else {
            if (isset($cmsBlock) && isset($cmsBlock[1]['id_cms_category'])) {
                $fieldsValues['id_category'] = $cmsBlock[1]['id_cms_category'];
            }
        }

        if (Tools::getValue('block_location')) {
            $fieldsValues['block_location'] = Tools::getValue('block_location');
        } else {
            if (isset($cmsBlock) && isset($cmsBlock[1]['location'])) {
                $fieldsValues['block_location'] = $cmsBlock[1]['location'];
            } else {
                $fieldsValues['block_location'] = 0;
            }
        }

        if ($cmsBoxes = Tools::getValue('cmsBox')) {
            foreach ($cmsBoxes as $value) {
                $fieldsValues[$value] = true;
            }
        } else {
            if (isset($cmsBlockPages) && $cmsBlockPages) {
                foreach ($cmsBlockPages as $item) {
                    $fieldsValues['0_' . $item['id_cms']] = true;
                }
            }
            if (isset($cmsBlockCategories) && $cmsBlockCategories) {
                foreach ($cmsBlockCategories as $item) {
                    $fieldsValues['1_' . $item['id_cms']] = true;
                }
            }
        }

        $helper = $this->initForm();

        if (isset($id_cms_block)) {
            $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&id_cms_block=' . $id_cms_block;
            $helper->submit_action = 'editBlockCMS';
        } else {
            $helper->submit_action = 'addBlockCMS';
        }

        $helper->fields_value = $fieldsValues;
        $this->_html .= $helper->generateForm([
            ['form' => $editForm]
        ]);
    }

    /**
     * @return HelperForm
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initForm()
    {
        /** @var AdminModulesController $controller */
        $controller = $this->context->controller;

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = 'blockcms';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->getLanguages();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $controller->default_form_language;
        $helper->allow_employee_form_lang = $controller->allow_employee_form_lang;
        $helper->toolbar_scroll = true;
        $helper->toolbar_btn = $this->initToolbar();

        return $helper;
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function changePosition()
    {
        if (!Validate::isInt(Tools::getValue('position')) ||
            (Tools::getValue('location') != BlockCMSModel::LEFT_COLUMN &&
                Tools::getValue('location') != BlockCMSModel::RIGHT_COLUMN) ||
            (Tools::getValue('way') != 0 && Tools::getValue('way') != 1)) {
            Tools::displayError();
        }

        $this->_html .= 'pos change!';
        $position = (int)Tools::getValue('position');
        $location = (int)Tools::getValue('location');
        $id_cms_block = (int)Tools::getValue('id_cms_block');

        if (Tools::getValue('way') == 0) {
            $new_position = $position + 1;
        } else {
            $new_position = $position - 1;
        }

        BlockCMSModel::updateCMSBlockPositions($id_cms_block, $position, $new_position, $location);
        Tools::redirectAdmin('index.php?tab=AdminModules&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    protected function _postValidation()
    {
        $this->_errors = array();

        if (Tools::isSubmit('submitBlockCMS')) {
            $cmsBoxes = Tools::getValue('cmsBox');

            if (!Validate::isInt(Tools::getValue('display_stores')) || (Tools::getValue('display_stores') != 0 && Tools::getValue('display_stores') != 1)) {
                $this->_errors[] = $this->l('Invalid store display value.');
            }
            if (!Validate::isInt(Tools::getValue('block_location')) || (Tools::getValue('block_location') != BlockCMSModel::LEFT_COLUMN && Tools::getValue('block_location') != BlockCMSModel::RIGHT_COLUMN)) {
                $this->_errors[] = $this->l('Invalid block location.');
            }
            if (!is_array($cmsBoxes)) {
                $this->_errors[] = $this->l('You must choose at least one page -- or subcategory -- in order to create a CMS block.');
            } else {
                foreach ($cmsBoxes as $cmsBox) {
                    if (!preg_match('#^[01]_[0-9]+$#', $cmsBox)) {
                        $this->_errors[] = $this->l('Invalid CMS page and/or category.');
                    }
                }
                foreach ($this->getLanguages() as $language) {
                    if (strlen(Tools::getValue('block_name_' . $language['id_lang'])) > 40) {
                        $this->_errors[] = $this->l('The block name is too long.');
                    }
                }
            }
        } else {
            if (Tools::isSubmit('deleteBlockCMS') && !Validate::isInt(Tools::getValue('id_cms_block'))) {
                $this->_errors[] = $this->l('Invalid id_cms_block');
            } else {
                if (Tools::isSubmit('submitFooterCMS')) {
                    if (Tools::getValue('footerBox') && is_array(Tools::getValue('footerBox'))) {
                        foreach (Tools::getValue('footerBox') as $cmsBox) {
                            if (!preg_match('#^[01]_[0-9]+$#', $cmsBox)) {
                                $this->_errors[] = $this->l('Invalid CMS page and/or category.');
                            }
                        }
                    }

                    $empty_footer_text = true;
                    $footer_text = array((int)Configuration::get('PS_LANG_DEFAULT') => Tools::getValue('footer_text_' . (int)Configuration::get('PS_LANG_DEFAULT')));

                    foreach ($this->getLanguages() as $language) {
                        if ($language['id_lang'] == (int)Configuration::get('PS_LANG_DEFAULT')) {
                            continue;
                        }

                        $footer_text_value = Tools::getValue('footer_text_' . (int)$language['id_lang']);
                        if (!empty($footer_text_value)) {
                            $empty_footer_text = false;
                            $footer_text[(int)$language['id_lang']] = $footer_text_value;
                        } else {
                            $footer_text[(int)$language['id_lang']] = $footer_text[(int)Configuration::get('PS_LANG_DEFAULT')];
                        }
                    }

                    if (!$empty_footer_text && empty($footer_text[(int)Configuration::get('PS_LANG_DEFAULT')])) {
                        $this->_errors[] = $this->l('Please provide footer text for the default language.');
                    } else {
                        foreach ($this->getLanguages() as $language) {
                            Configuration::updateValue('FOOTER_CMS_TEXT_' . (int)$language['id_lang'], $footer_text[(int)$language['id_lang']], true);
                        }
                    }

                    if ((Tools::getValue('cms_footer_on') != 0) && (Tools::getValue('cms_footer_on') != 1)) {
                        $this->_errors[] = $this->l('Invalid footer activation.');
                    }
                }
            }
        }
        if ($this->_errors) {
            foreach ($this->_errors as $err) {
                $this->_html .= '<div class="alert alert-danger">' . $err . '</div>';
            }

            return false;
        }
        return true;
    }

    /**
     * @return false|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _postProcess()
    {
        if (!$this->_postValidation()) {
            return false;
        }

        $this->_clearCache('blockcms.tpl');

        $this->_errors = array();
        if (Tools::isSubmit('submitBlockCMS')) {
            $id_cms_category = (int)Tools::getvalue('id_category');
            $display_store = (int)Tools::getValue('display_stores');
            $location = (int)Tools::getvalue('block_location');
            $position = BlockCMSModel::getMaxPosition($location);

            if (Tools::isSubmit('addBlockCMS')) {
                $id_cms_block = BlockCMSModel::insertCMSBlock($id_cms_category, $location, $position, $display_store);

                if ($id_cms_block !== false) {
                    foreach ($this->getLanguages() as $language) {
                        BlockCMSModel::insertCMSBlockLang($id_cms_block, $language['id_lang']);
                    }

                    $shops = Shop::getContextListShopID();

                    foreach ($shops as $shop) {
                        BlockCMSModel::insertCMSBlockShop($id_cms_block, $shop);
                    }
                }

                $this->_errors[] = $this->l('Cannot create a block!');
            } elseif (Tools::isSubmit('editBlockCMS')) {
                $id_cms_block = Tools::getvalue('id_cms_block');
                $old_block = BlockCMSModel::getBlockCMS($id_cms_block);

                BlockCMSModel::deleteCMSBlockPage($id_cms_block);

                if ($old_block[1]['location'] != (int)Tools::getvalue('block_location')) {
                    BlockCMSModel::updatePositions($old_block[1]['position'], $old_block[1]['position'] + 1, $old_block[1]['location']);
                }

                BlockCMSModel::updateCMSBlock($id_cms_block, $id_cms_category, $position, $location, $display_store);

                foreach ($this->getLanguages() as $language) {
                    $block_name = Tools::getValue('block_name_' . $language['id_lang']);
                    BlockCMSModel::updateCMSBlockLang($id_cms_block, $block_name, $language['id_lang']);
                }
            }

            $cmsBoxes = Tools::getValue('cmsBox');
            if ($cmsBoxes && isset($id_cms_block)) {
                foreach ($cmsBoxes as $cmsBox) {
                    $cms_properties = explode('_', $cmsBox);
                    BlockCMSModel::insertCMSBlockPage($id_cms_block, $cms_properties[1], $cms_properties[0]);
                }
            }

            if (Tools::isSubmit('addBlockCMS')) {
                $redirect = 'addBlockCMSConfirmation';
            } elseif (Tools::isSubmit('editBlockCMS')) {
                $redirect = 'editBlockCMSConfirmation';
            } else {
                $redirect = '';
            }

            Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&' . $redirect);
        } elseif (Tools::isSubmit('deleteBlockCMS') && Tools::getValue('id_cms_block')) {
            $id_cms_block = Tools::getvalue('id_cms_block');

            if ($id_cms_block) {
                BlockCMSModel::deleteCMSBlock((int)$id_cms_block);
                BlockCMSModel::deleteCMSBlockPage((int)$id_cms_block);

                Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&deleteBlockCMSConfirmation');
            } else {
                $this->_html .= $this->displayError($this->l('Error: You are trying to delete a non-existing CMS block.'));
            }
        } elseif (Tools::isSubmit('submitFooterCMS')) {
            $powered_by = Tools::getValue('cms_footer_powered_by_on') ? 1 : 0;
            $footer_boxes = Tools::getValue('footerBox') ? implode('|', Tools::getValue('footerBox')) : '';
            $block_activation = (Tools::getValue('cms_footer_on') == 1) ? 1 : 0;

            Configuration::updateValue('PS_STORES_DISPLAY_FOOTER', Tools::getValue('PS_STORES_DISPLAY_FOOTER_on'));
            Configuration::updateValue('FOOTER_CMS', rtrim($footer_boxes, '|'));
            Configuration::updateValue('FOOTER_POWEREDBY', $powered_by);
            Configuration::updateValue('FOOTER_BLOCK_ACTIVATION', $block_activation);

            Configuration::updateValue('FOOTER_PRICE-DROP', (int)Tools::getValue('cms_footer_display_price-drop_on'));
            Configuration::updateValue('FOOTER_NEW-PRODUCTS', (int)Tools::getValue('cms_footer_display_new-products_on'));
            Configuration::updateValue('FOOTER_BEST-SALES', (int)Tools::getValue('cms_footer_display_best-sales_on'));
            Configuration::updateValue('FOOTER_CONTACT', (int)Tools::getValue('cms_footer_display_contact_on'));
            Configuration::updateValue('FOOTER_SITEMAP', (int)Tools::getValue('cms_footer_display_sitemap_on'));

            $this->_html .= $this->displayConfirmation($this->l('Your footer information has been updated.'));
        } elseif (Tools::isSubmit('addBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('CMS block added.'));
        } elseif (Tools::isSubmit('editBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('CMS block edited.'));
        } elseif (Tools::isSubmit('deleteBlockCMSConfirmation')) {
            $this->_html .= $this->displayConfirmation($this->l('Deletion successful.'));
        } elseif (Tools::isSubmit('id_cms_block') && Tools::isSubmit('way') && Tools::isSubmit('position') && Tools::isSubmit('location')) {
            $this->changePosition();
        } elseif (Tools::isSubmit('updatePositions')) {
            $this->updatePositionsDnd();
        }
        if ($this->_errors) {
            foreach ($this->_errors as $err) {
                $this->_html .= '<div class="alert error">' . $err . '</div>';
            }
        }
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $this->_html = '';
        $this->_postProcess();

        if (Tools::isSubmit('addBlockCMS') || Tools::isSubmit('editBlockCMS')) {
            $this->displayAddForm();
        } else {
            $this->displayForm();
        }

        return $this->_html;
    }

    /**
     * @param $column
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayBlockCMS($column)
    {
        if (!$this->isCached('blockcms.tpl', $this->getCacheId($column))) {
            $cms_titles = BlockCMSModel::getCMSTitles($column);

            $this->smarty->assign(array(
                'block' => 1,
                'cms_titles' => $cms_titles,
                'contact_url' => 'contact'
            ));
        }
        return $this->display(__FILE__, 'blockcms.tpl', $this->getCacheId($column));
    }

    /**
     * @param $name
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCacheId($name = null)
    {
        return parent::getCacheId('blockcms|' . $name);
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookActionAdminStoresControllerUpdate_optionsAfter()
    {
        if (Tools::getIsset('PS_STORES_DISPLAY_FOOTER')) {
            $this->_clearCache('blockcms.tpl');
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookActionObjectCmsUpdateAfter()
    {
        $this->_clearCache('blockcms.tpl');
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function hookActionObjectCmsDeleteAfter()
    {
        $this->_clearCache('blockcms.tpl');
    }

    /**
     * @param $params
     *
     * @return void
     */
    public function hookHeader($params)
    {
        $this->context->controller->addCSS(($this->_path) . 'blockcms.css', 'all');
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookLeftColumn()
    {
        return $this->displayBlockCMS(BlockCMSModel::LEFT_COLUMN);
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookRightColumn()
    {
        return $this->displayBlockCMS(BlockCMSModel::RIGHT_COLUMN);
    }

    /**
     * @return string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookFooter()
    {
        if (!(Configuration::get('FOOTER_BLOCK_ACTIVATION'))) {
            return;
        }

        if (!$this->isCached('blockcms.tpl', $this->getCacheId(BlockCMSModel::FOOTER))) {
            $display_poweredby = Configuration::get('FOOTER_POWEREDBY');
            $this->smarty->assign(
                array(
                    'block' => 0,
                    'contact_url' => 'contact',
                    'cmslinks' => BlockCMSModel::getCMSTitlesFooter(),
                    'display_stores_footer' => Configuration::get('PS_STORES_DISPLAY_FOOTER'),
                    'display_poweredby' => ((int)$display_poweredby === 1 || $display_poweredby === false),
                    'footer_text' => Configuration::get('FOOTER_CMS_TEXT_' . (int)$this->context->language->id),
                    'show_price_drop' => Configuration::get('FOOTER_PRICE-DROP'),
                    'show_new_products' => Configuration::get('FOOTER_NEW-PRODUCTS'),
                    'show_best_sales' => Configuration::get('FOOTER_BEST-SALES'),
                    'show_contact' => Configuration::get('FOOTER_CONTACT'),
                    'show_sitemap' => Configuration::get('FOOTER_SITEMAP')
                )
            );
        }
        return $this->display(__FILE__, 'blockcms.tpl', $this->getCacheId(BlockCMSModel::FOOTER));
    }

    /**
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function updatePositionsDnd()
    {
        if (Tools::getValue('cms_block_0')) {
            $positions = Tools::getValue('cms_block_0');
        } elseif (Tools::getValue('cms_block_1')) {
            $positions = Tools::getValue('cms_block_1');
        } else {
            $positions = array();
        }

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);

            if (isset($pos[2])) {
                BlockCMSModel::updateCMSBlockPosition($pos[2], $position);
            }
        }
    }

    /**
     * @param $params
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionShopDataDuplication($params)
    {
        //get all cmd block to duplicate in new shop
        $cms_blocks = Db::getInstance()->executeS('
			SELECT * FROM `' . _DB_PREFIX_ . 'cms_block` cb
			JOIN `' . _DB_PREFIX_ . 'cms_block_shop` cbf
				ON (cb.`id_cms_block` = cbf.`id_cms_block` AND cbf.`id_shop` = ' . (int)$params['old_id_shop'] . ') ');

        if ($cms_blocks) {
            foreach ($cms_blocks as $cms_block) {
                Db::getInstance()->execute('
					INSERT IGNORE INTO ' . _DB_PREFIX_ . 'cms_block (`id_cms_block`, `id_cms_category`, `location`, `position`, `display_store`)
					VALUES (NULL, ' . (int)$cms_block['id_cms_category'] . ', ' . (int)$cms_block['location'] . ', ' . (int)$cms_block['position'] . ', ' . (int)$cms_block['display_store'] . ');');

                $id_block_cms = Db::getInstance()->Insert_ID();

                Db::getInstance()->execute('INSERT IGNORE INTO ' . _DB_PREFIX_ . 'cms_block_shop (`id_cms_block`, `id_shop`) VALUES (' . (int)$id_block_cms . ', ' . (int)$params['new_id_shop'] . ');');

                $langs = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'cms_block_lang` WHERE `id_cms_block` = ' . (int)$cms_block['id_cms_block']);

                foreach ($langs as $lang) {
                    Db::getInstance()->execute('
						INSERT IGNORE INTO `' . _DB_PREFIX_ . 'cms_block_lang` (`id_cms_block`, `id_lang`, `name`)
						VALUES (' . (int)$id_block_cms . ', ' . (int)$lang['id_lang'] . ', \'' . pSQL($lang['name']) . '\');');
                }

                $pages = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'cms_block_page` WHERE `id_cms_block` = ' . (int)$cms_block['id_cms_block']);

                foreach ($pages as $page) {
                    Db::getInstance()->execute('
						INSERT IGNORE INTO `' . _DB_PREFIX_ . 'cms_block_page` (`id_cms_block_page`, `id_cms_block`, `id_cms`, `is_category`)
						VALUES (NULL, ' . (int)$id_block_cms . ', ' . (int)$page['id_cms'] . ', ' . (int)$page['is_category'] . ');');
                }
            }
        }
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    protected function getLanguages()
    {
        /** @var AdminModulesController $controller */
        $controller = $this->context->controller;
        return $controller->getLanguages();
    }
}
