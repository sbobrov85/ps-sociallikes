<?php
/*
 * Copyright (C) 2017 sbobrov85 <sbobrov85@gmail.com>.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SocialLikes extends Module
{
    /**
     * ! Do not touch $this->l(...) - it's hack for localization via admin panel
     * @var array settings list for supported social networks
     */
    protected static $networks = array(
        'facebook' => array(),
        'twitter' => array(
            'options' => array(
                'via' => array(
                    'type' => 'text',
                    'label' => 'Via (site or your own)' //$this->l('Via (site or your own)');
                ),
                'related' => array(
                    'type' => 'text',
                    'label' => 'Related (any other twitter)' //$this->l('Related (any other twitter)');
                )
            )
        ),
        'mailru' => array(),
        'vkontakte' => array(
            'enabled_by_default' => true
        ),
        'odnoklassniki' => array(
            'enabled_by_default' => true
        ),
        'plusone' => array(),
        'pinterest' => array(
            'options' => array(
                'media' => array(
                    'type' => 'text',
                    'label' => 'Specify image url' //$this->l('Specify image url');
                )
            )
        ),
    );

    /**
     * @var array module templates list
     */
    protected static $templatesList = array(
        'sociallikes',
        'sociallikes_header'
    );

    /**
     * @var array supported css styles for social-likes
     */
    protected static $supportedStyles = array(
        'birman',
        'classic',
        'flat'
    );

    /**
     * @var array options list for general tab (key => default value)
     */
    protected static $generalTabOptions = array(
        'style' => 'classic',
        'header' => 1,
        'layout' => 'default',
        'counters' => 1,
        'zeroes' => 0,
        'autoinit' => 1,
        'id_attr' => '',
    );

    /**
     * Contains cache prefix for options part.
     */
    const OPTIONS_CACHE_PREFIX = 'sociallikes_options';

    /**
     * Contains settings prefix for options.
     */
    private static $SETTINGS_PREFIX = '';

    public function __construct()
    {
        $this->name = 'sociallikes';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'sbobrov85';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.6.1',
            'max' => '1.6.1.17'
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Social likes');
        $this->description = $this->l(
            'Single style social like buttons: Facebook, Twitter, Google+,'
                . ' Pinterest and also popular Russian social networks on'
                . ' product page.'
        );

        self::setSettingsPrefix('PS_SL');
    }

    /**
     * Set class property for settings prefix
     * @param string $settingsPrefix settings prefix
     * @throws Exception if settingsPrefix is empty
     */
    public static function setSettingsPrefix($settingsPrefix)
    {
        if (empty($settingsPrefix)) {
            throw new Exception('Settings prefix require non empty value!');
        }

        $settingsPrefix = rtrim($settingsPrefix, '_') . '_';
        self::$SETTINGS_PREFIX = Tools::strtoupper($settingsPrefix);
    }

    /**
     * Build module settings key
     * @param string $paramName param name
     * @return string full settings key
     * @throws Exception if settings prefix not set
     */
    public static function buildSettingsKey($paramName)
    {
        if (empty(self::$SETTINGS_PREFIX)) {
            throw new Exception('Requre settings prefix for using method!');
        }

        return self::$SETTINGS_PREFIX . Tools::strtoupper($paramName);
    }

    /**
     * Get config field value by param name
     * @param string $paramName param name
     * @return mixed config value
     * @throws Exception if param name is empty
     */
    public static function getConfigFieldValue($paramName)
    {
        if (empty($paramName)) {
            throw new Exception('Require not empty param name!');
        }

        $settingsKey = self::buildSettingsKey($paramName);

        return Tools::getValue(
            $settingsKey,
            Configuration::get($settingsKey)
        );
    }

    /**
     * Set config field value by param name
     * @param string $paramName param name
     * @param mixed $value value for set, if null then read from request
     * @throws Exception if param name is empty
     */
    public static function setConfigFieldValue($paramName, $value = null)
    {
        if (empty($paramName)) {
            throw new Exception('Require not empty param name!');
        }

        $settingsKey = self::buildSettingsKey($paramName);

        if (!isset($value)) {
            $value = Tools::getValue($settingsKey);
        }

        Configuration::updateValue($settingsKey, $value);
    }

    /**
     * Generate admin link
     * @param ModuleCore $module module class with properties
     * @param bool $withToken get link with token or not (default true)
     * @return string
     */
    public static function generateAdminLink(ModuleCore $module, $withToken = true)
    {
        $link = Context::getContext()->link;

        $adminLink = $link->getAdminLink('AdminModules', $withToken)
            .'&conf=6&configure=' . $module->name
            .'&tab_module=' . $module->tab
            .'&module_name=' . $module->name;

        return $adminLink;
    }

    /**
     * Get supported network list
     * @return array
     */
    protected function getNetworksList()
    {
        $result = array();

        if (isset(self::$networks) && is_array(self::$networks)) {
            $result = array_keys(self::$networks);
        }

        return $result;
    }

    /**
     * Activate every option by default
     */
    protected function updateDefaultOptions()
    {
        // update general tab default options
        foreach (self::$generalTabOptions as $optionsName => $optionsDefault) {
            self::setConfigFieldValue($optionsName, $optionsDefault);
        }

        // update network default options
        foreach (self::$networks as $networkName => $networkProperties) {
            self::setConfigFieldValue(
                $networkName,
                (int) !empty($networkProperties['enabled_by_default'])
            );
            self::setConfigFieldValue(
                $networkName . '_text',
                $networkName
            );
        }
    }

    /**
     * Register module hooks
     */
    protected function registerHooks()
    {
        $this->registerHook('header');
        $this->registerHook('displayRightColumnProduct');
        $this->registerHook('displayFooter');

        $this->registerHook('displaySocialLikes');
    }

    /**
     * Standart action does on install
     * @return boolean true, if install success, false if fail
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->updateDefaultOptions();

        $this->registerHooks();

        return true;
    }

    /**
     * Clear module templates cache
     */
    protected function clearTemplatesCache()
    {
        if (isset(self::$templatesList) && is_array(self::$templatesList)) {
            foreach (self::$templatesList as $templateName) {
                Tools::clearCache(
                    Context::getContext()->smarty,
                    $this->getTemplatePath($templateName . '.tpl')
                );
            }
        }
    }

    /**
     * Build admin link
     * @param bool $withToken get link with token or not (default true)
     * @return string
     */
    protected function getAdminLink($withToken = true)
    {
        return $this->context->link->getAdminLink('AdminModules', $withToken)
            .'&conf=6&configure=' . $this->name
            .'&tab_module=' . $this->tab
            .'&module_name=' . $this->name;
    }

    /**
     * Store module settings from form data
     */
    protected function storeSettings()
    {
        $this->processConfigFieldsValues();

        $this->html .= $this->displayConfirmation($this->l('Settings updated'));

        $this->clearTemplatesCache();

        Tools::redirectAdmin(self::generateAdminLink($this));
    }

    /**
     * Get settings form tabs
     * @return array
     */
    protected function getFormTabs()
    {
        $tabs = array();

        $tabs['general'] = $this->l('General options');

        $networksList = $this->getNetworksList();

        foreach ($networksList as $networkName) {
            $tabs[$networkName] = $networkName;
        }

        return $tabs;
    }

    /**
     * Get settings form fields for general tab
     * @return array
     */
    protected function getFormFieldsGeneral()
    {
        $fields = array();

        // add options for supported themes
        $stylesValues = array();
        foreach (self::$supportedStyles as $styleName) {
            $stylesValues[] = array(
                'id' => $styleName,
                'name' => $styleName
            );
        }
        $fields[] = array(
            'type' => 'select',
            'label' => $this->l('Buttons style'),
            'name' => self::buildSettingsKey('style'),
            'options' => array(
                'query' => $stylesValues,
                'id' => 'id',
                'name' => 'name'
            ),
            'default_value' => 'classic',
            'tab' => 'general'
        );

        // add options for layout
        $fields[] = array(
            'type' => 'select',
            'label' => $this->l('Layout'),
            'name' => self::buildSettingsKey('layout'),
            'options' => array(
                'query' => array(
                    array(
                        'id' => 'default',
                        'name' => $this->l('Default (Horizontal line)')
                    ),
                    array(
                        'id' => 'vertical',
                        'name' => $this->l('Vertical (Vertical line)')
                    ),
                    array(
                        'id' => 'single',
                        'name' => $this->l('Single (Show only one icon)')
                    ),
                    array(
                        'id' => 'notext',
                        'name' => $this->l('Icons (No text)')
                    )
                ),
                'id' => 'id',
                'name' => 'name',
            ),
            'tab' => 'general'
        );

        // switch options
        $switches = array(
            'header' => $this->l('Show module header'),
            'counters' => $this->l('Show counters'),
            'zeroes' => $this->l('Show zero counters'),
            'autoinit' => $this->l('Use autoinit')
        );
        foreach ($switches as $switchName => $switchLabel) {
            $fields[] = array(
                'type' => 'switch',
                'label' => $switchLabel,
                'name' => self::buildSettingsKey($switchName),
                'values' => array(
                    array(
                        'id' => 'no',
                        'value' => 0,
                        'label' => $this->l('No')
                    ),
                    array(
                        'id' => 'yes',
                        'value' => 1,
                        'label' => $this->l('Yes')
                    )
                ),
                'tab' => 'general'
            );
        }

        // id_attr option
        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('ID attribute'),
            'name' => self::buildSettingsKey('id_attr'),
            'tab' => 'general'
        );

        return $fields;
    }

    /**
     * Get form fields for networks options
     * @param string $networkName social network name
     * @param array $networkProperties additional options for network
     * @return array
     */
    protected function getFormFieldsNetwork($networkName, $networkProperties)
    {
        $fields = array();

        // disable|enable
        $fields[] = array(
            'type' => 'switch',
            'label' => $this->l('Display button'),
            'name' => self::buildSettingsKey($networkName),
            'values' => array(
                array(
                    'id' => Tools::strtolower($networkName).'_active_on',
                    'value' => 1,
                    'label' => $this->l('Enabled')
                ),
                array(
                    'id' => Tools::strtolower($networkName).'_active_off',
                    'value' => 0,
                    'label' => $this->l('Disabled')
                )
            ),
            'tab' => $networkName
        );

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Text'),
            'name' => self::buildSettingsKey($networkName . '_text'),
            'tab' => $networkName
        );

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => self::buildSettingsKey($networkName . '_title'),
            'tab' => $networkName
        );

        // custom options for network
        if (!empty($networkProperties['options']) &&
            is_array($networkProperties['options'])) {
            foreach ($networkProperties['options'] as $optionName => $optionParams) {
                if (!empty($optionParams)) {
                    $field = array();

                    switch ($optionParams['type']) {
                        case 'text':
                            $field['type'] = $optionParams['type'];
                            break;
                    }

                    $field['label'] = $this->l($optionParams['label']);
                    $field['name'] = self::buildSettingsKey(
                        $networkName . '_' . $optionName
                    );
                    $field['tab'] = $networkName;

                    $fields[] = $field;
                }
            }
        }

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Sort priority'),
            'name' => self::buildSettingsKey($networkName . '_sort'),
            'tab' => $networkName
        );

        return $fields;
    }

    /**
     * Get settings form fields
     * @return array
     */
    protected function getFormFields()
    {
        $fields = array();

        $fields = array_merge($fields, $this->getFormFieldsGeneral());

        // networks options
        foreach (self::$networks as $networkName => $networkProperties) {
            $fields = array_merge(
                $fields,
                $this->getFormFieldsNetwork($networkName, $networkProperties)
            );
        }

        return $fields;
    }

    /**
     * Set or get config filter value
     * @param string $paramName param name
     * @param string $method 'update' for set or any for get
     * @return array empty for set or key => value pair for get
     */
    protected function processConfigFieldValue($paramName, $method)
    {
        $result = array();

        switch ($method) {
            case 'update':
                self::setConfigFieldValue($paramName);
                break;
            default:
                $settingsKey = self::buildSettingsKey($paramName);
                $result[$settingsKey] = self::getConfigFieldValue($paramName);
        }

        return $result;
    }

    /**
     * Set or get config fields values
     * @param string $method 'update' for set or any for get
     * @return array empty for set or values for get
     */
    protected function processConfigFieldsValues($method = 'update')
    {
        $result = array();

        // set or get general tab options
        foreach (array_keys(self::$generalTabOptions) as $optionsName) {
            $result = array_merge(
                $result,
                $this->processConfigFieldValue($optionsName, $method)
            );
        }

        // set or get networks options
        foreach (self::$networks as $networkName => $networkProperties) {
            $result = array_merge(
                $result,
                $this->processConfigFieldValue($networkName, $method),
                $this->processConfigFieldValue($networkName . '_text', $method),
                $this->processConfigFieldValue($networkName . '_title', $method),
                $this->processConfigFieldValue($networkName . '_sort', $method)
            );
            if (!empty($networkProperties['options']) &&
                is_array($networkProperties['options'])
            ) {
                foreach (array_keys($networkProperties['options']) as $optionName) {
                    $result = array_merge(
                        $result,
                        $this->processConfigFieldValue(
                            $networkName . '_' . $optionName,
                            $method
                        )
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Get fields values from config
     * @return array
     */
    protected function getConfigFieldsValues()
    {
        return $this->processConfigFieldsValues('get');
    }

    /**
     * Build content for settings page and process save form
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitSocialLikes')) {
            $this->storeSettings();
        }

        $helper = new HelperForm();

        $helper->submit_action = 'submitSocialLikes';
        $helper->currentIndex = self::generateAdminLink($this, false);
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues()
        );

        return $this->html . $helper->generateForm(array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->displayName,
                        'icon' => 'icon-share'
                    ),
                    'tabs' => $this->getFormTabs(),
                    'input' => $this->getFormFields(),
                    'submit' => array(
                        'title' => $this->l('Save')
                    )
                )
            )
        ));
    }

    /**
     * Include css, js resources required for module
     */
    protected function addAssets()
    {
        // add common css
        $this->context->controller->addCss(
            $this->_path . 'css/social-likes_all.css'
        );

        // add selected buttons css
        $style = self::getConfigFieldValue('style');
        if (!$style || !in_array($style, self::$supportedStyles)) {
            $style = self::$generalTabOptions['style'];
        }
        $this->context->controller->addCss(
            $this->_path . "css/social-likes_$style.css"
        );

        // add common js
        $this->context->controller->addJS(
            $this->_path . 'js/social-likes.min.js'
        );
    }

    /**
     * Get product properties required for module display
     * @return array
     */
    protected function getProductProperties()
    {
        // get generic item data and validate it.
        $product = $this->context->controller->getProduct();
        if (!Validate::isLoadedObject($product)) {
            return array();
        }

        $properties = array(
            'id' => isset($product->id) ? $product->id : null,
            'price' => Tools::ps_round(
                $product->getPrice(
                    !Product::getTaxCalculationMethod(
                        (int) $this->context->cookie->id_customer
                    ),
                    null
                ),
                _PS_PRICE_COMPUTE_PRECISION_
            ),
            'pretax_price' => Tools::ps_round(
                $product->getPrice(false, null),
                _PS_PRICE_COMPUTE_PRECISION_
            ),
            'weight' => $product->weight,
            'weight_unit' => Configuration::get('PS_WEIGHT_UNIT'),
            'cover' => isset($product->id) ?
                Product::getCover((int) $product->id) : '',
            'link_rewrite' => isset($product->link_rewrite) &&
                $product->link_rewrite ? $product->link_rewrite : '',
        );

        return $properties;
    }

    /**
     * Hook action for add information into header
     * @param array $params
     * @return string
     */
    public function hookDisplayHeader($params)
    {
        $phpSelf = isset($this->context->controller->php_self) ?
            $this->context->controller->php_self : null;

        switch ($phpSelf) {
            case 'product':
                $properties = $this->getProductProperties();
                break;
            default:
                $properties = array();
        }

        $this->addAssets();

        if (!empty($properties)) {
            $this->context->smarty->assign($properties);
            return $this->display(
                __FILE__,
                'sociallikes_header.tpl'
            );
        }

        return;
    }

    /**
     * Get config field values for template display
     * @return array
     */
    protected function getConfigFieldValuesForTemplate()
    {
        $values = array(
            'properties' => array(),
            'sociallikes' => array()
        );

        // get general options
        foreach (array_keys(self::$generalTabOptions) as $optionName) {
            $values['properties'][$optionName] = self::getConfigFieldValue($optionName);
        }

        // additional options
        $blockClasses = array();
        if (!empty($values['properties']['autoinit'])) {
            $blockClasses[] = 'social-likes';
        }
        if ($values['properties']['layout'] != 'default') {
            $blockClasses[] = 'social-likes_' . $values['properties']['layout'];
        }
        $values['properties']['block_classes'] = implode(' ', $blockClasses);

        if ($values['properties']['layout'] == 'single') {
            $values['properties']['single_title'] = $this->l('Share');
        }

        if ($values['properties']['header']) {
            $values['properties']['header_title'] = $this->l('Share with');
        }

        // build enabled social network list
        foreach (self::$networks as $networkName => $networkProperties) {
            $isDisplay = self::getConfigFieldValue($networkName);
            if ($isDisplay) {
                foreach (array('text', 'title', 'sort') as $valueName) {
                    $value = self::getConfigFieldValue(
                        $networkName . '_' . $valueName
                    );
                    $values['sociallikes'][$networkName][$valueName] = $value;
                }
                $specificOptions = array();
                if (!empty($networkProperties['options']) &&
                    is_array($networkProperties['options'])
                ) {
                    foreach (array_keys($networkProperties['options']) as $optionName) {
                        $specificOptions[$optionName] = self::getConfigFieldValue(
                            $networkName . '_' . $optionName
                        );
                    }
                }
                $values['sociallikes'][$networkName]['specific'] = $specificOptions;
            }
        }

        uasort($values['sociallikes'], function ($a, $b) {
            return (int) $a['sort'] <= (int) $b['sort'] ? 1 : -1;
        });

        return $values;
    }

    /**
     * Hook for display social likes buttons
     * @return string
     */
    public function hookDisplaySocialLikes()
    {
        $this->context->smarty->assign(
            $this->getConfigFieldValuesForTemplate()
        );

        return $this->display(
            __FILE__,
            'sociallikes.tpl'
        );
    }

    /**
     * Hook for display social likes buttons on product page
     * @return string
     */
    public function hookDisplayRightColumnProduct()
    {
        return $this->hookDisplaySocialLikes();
    }
}
