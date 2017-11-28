<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*   @author    PrestaShop SA <contact@prestashop.com>
*   @copyright 2007-2015 PrestaShop SA
*   @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*   International Registered Trademark & Property of PrestaShop SA
*/

if (!class_exists('ModuleHelper')) {
    require_once 'helpers/ModuleHelper.php';
}

if (!defined('_PS_VERSION_')) {
    exit;
}

class SocialLikes extends Module
{

    /**
     * @var array settings list for supported social networks
     */
    protected static $networks = array(
        'facebook' => array(),
        'twitter' => array(
            'options' => array(
                'via' => array(
                    'type' => 'text',
                    'label' => 'Via (site or your own)'
                ),
                'related' => array(
                    'type' => 'text',
                    'label' => 'Related (any other twitter)'
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
                    'label' => 'Specify image url'
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

    public function __construct()
    {
        $this->name = 'sociallikes';
        $this->tab = 'advertising_marketing';
        $this->version = '0.6.4';
        $this->author = 'sbobrov85';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Social likes');
        $this->description = $this->l(
            'Single style social like buttons: Facebook, Twitter, Google+,'
                . ' Pinterest and also popular Russian social networks on'
                . ' product page.'
        );

        ModuleHelper::setSettingsPrefix('PS_SL');
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
            ModuleHelper::setConfigFieldValue($optionsName, $optionsDefault);
        }

        // update network default options
        foreach (self::$networks as $networkName => $networkProperties) {
            ModuleHelper::setConfigFieldValue(
                $networkName,
                (int) !empty($networkProperties['enabled_by_default'])
            );
            ModuleHelper::setConfigFieldValue(
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

        Tools::redirectAdmin(ModuleHelper::generateAdminLink($this));
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
            'name' => ModuleHelper::buildSettingsKey('style'),
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
            'name' => ModuleHelper::buildSettingsKey('layout'),
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
                'name' => ModuleHelper::buildSettingsKey($switchName),
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
            'name' => ModuleHelper::buildSettingsKey('id_attr'),
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
            'name' => ModuleHelper::buildSettingsKey($networkName),
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
            'name' => ModuleHelper::buildSettingsKey($networkName . '_text'),
            'tab' => $networkName
        );

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => ModuleHelper::buildSettingsKey($networkName . '_title'),
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
                    $field['name'] = ModuleHelper::buildSettingsKey(
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
            'name' => ModuleHelper::buildSettingsKey($networkName . '_sort'),
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
                ModuleHelper::setConfigFieldValue($paramName);
                break;
            default:
                $settingsKey = ModuleHelper::buildSettingsKey($paramName);
                $result[$settingsKey] = ModuleHelper::getConfigFieldValue($paramName);
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
        $helper->currentIndex = ModuleHelper::generateAdminLink($this, false);
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
        $style = ModuleHelper::getConfigFieldValue('style');
        if (!$style || !in_array($style, self::$supportedStyles)) {
            $style = 'classic';
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
            $values['properties'][$optionName] = ModuleHelper::getConfigFieldValue($optionName);
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
            $isDisplay = ModuleHelper::getConfigFieldValue($networkName);
            if ($isDisplay) {
                foreach (array('text', 'title', 'sort') as $valueName) {
                    $value = ModuleHelper::getConfigFieldValue(
                        $networkName . '_' . $valueName
                    );
                    $values['sociallikes'][$networkName][$valueName] = $value;
                }
                $specificOptions = array();
                if (!empty($networkProperties['options']) &&
                    is_array($networkProperties['options'])
                ) {
                    foreach (array_keys($networkProperties['options']) as $optionName) {
                        $specificOptions[$optionName] = ModuleHelper::getConfigFieldValue(
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
