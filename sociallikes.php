<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class SocialLikes extends Module
{

    /**
     * @const prefix for module settings keys
     */
    const SETTINGS_PREFIX = 'PS_SL_';

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

    public function __construct()
    {
        $this->name = 'sociallikes';
        $this->tab = 'advertising_marketing';
        $this->version = '0.3.0';
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

        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->l('No name provided');
        }
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
     * Build module settings key
     * @param string $paramName param name
     * @return string full settings key
     */
    protected function buildSettingsKey($paramName)
    {
        return self::SETTINGS_PREFIX . Tools::strtoupper($paramName);
    }

    /**
     * Activate every option by default
     */
    protected function updateDefaultOptions()
    {
        // update general tab default options
        foreach (self::$generalTabOptions as $optionsName => $optionsDefault) {
            $this->setConfigFieldValue(
                $this->buildSettingsKey($optionsName),
                $optionsDefault
            );
        }

        // update network default options
        foreach (self::$networks as $networkName => $networkProperties) {
            $this->setConfigFieldValue(
                $this->buildSettingsKey($networkName),
                (int) !empty($networkProperties['enabled_by_default'])
            );
            $this->setConfigFieldValue(
                $this->buildSettingsKey($networkName . '_text'),
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

        $this->registerHook('displaySocialLikes');
    }

    /**
     * Standart action does on install
     * @return boolean true, if install success, false if fail
     */
    public function install()
    {
        if(!parent::install()) {
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

        Tools::redirectAdmin($this->getAdminLink());
    }

    /**
     * Get settings form tabs
     * @return array
     */
    protected function getFormTabs()
    {
        $tabs = array();

        $tabs['general'] = 'general options';

        $networksList = $this->getNetworksList();

        foreach ($networksList as $networkName) {
            $tabs[$networkName] = $networkName . ' options';
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
            'label' => 'Buttons style',
            'name' => $this->buildSettingsKey('style'),
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
            'name' => $this->buildSettingsKey('layout'),
            'options' => array(
                'query' => array(
                    array(
                        'id' => 'default',
                        'name' => 'Default (Horizontal line)'
                    ),
                    array(
                        'id' => 'vertical',
                        'name' => 'Vertical (Vertical line)'
                    ),
                    array(
                        'id' => 'single',
                        'name' => 'Single (Show only one icon)'
                    ),
                    array(
                        'id' => 'notext',
                        'name' => 'Icons (No text)'
                    )
                ),
                'id' => 'id',
                'name' => 'name',
            ),
            'tab' => 'general'
        );

        // switch options
        $switches = array(
            'header' => 'Show module header',
            'counters' => 'Show counters',
            'zeroes' => 'Show zero counters',
            'autoinit' => 'Use autoinit'
        );
        foreach ($switches as $switchName => $switchLabel) {
            $fields[] = array(
                'type' => 'switch',
                'label' => $switchLabel,
                'name' => $this->buildSettingsKey($switchName),
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
            'name' => $this->buildSettingsKey('id_attr'),
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
            'label' => $networkName,
            'name' => $this->buildSettingsKey($networkName),
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
            'name' => $this->buildSettingsKey($networkName . '_text'),
            'tab' => $networkName
        );

        $fields[] = array(
            'type' => 'text',
            'label' => $this->l('Title'),
            'name' => $this->buildSettingsKey($networkName . '_title'),
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
                    $field['name'] = $this->buildSettingsKey(
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
            'name' => $this->buildSettingsKey($networkName . '_sort'),
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
     * Get config field value by key
     * @param string $settingsKey config key
     * @return mixed config value
     */
    protected function getConfigFieldValue($settingsKey)
    {
        return Tools::getValue(
            $settingsKey,
            Configuration::get($settingsKey)
        );
    }

    /**
     * Set config field value by key
     * @param string $settingsKey config key
     * @param mixed $value value for set, if null then read from request
     */
    protected function setConfigFieldValue($settingsKey, $value = null)
    {
        if(!isset($value)) {
            $value = Tools::getValue($settingsKey);
        }

        Configuration::updateValue($settingsKey, $value);
    }

    /**
     * Set or get config filter value
     * @param string $settingsKey values settings key
     * @param string $method 'update' for set or any for get
     * @return array empty for set or key => value pair for get
     */
    protected function processConfigFieldValue($settingsKey, $method)
    {
        $result = array();

        switch ($method) {
            case 'update':
                $this->setConfigFieldValue($settingsKey);
                break;
            default:
                $result[$settingsKey] = $this
                    ->getConfigFieldValue($settingsKey);
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
                $this->processConfigFieldValue(
                    $this->buildSettingsKey($optionsName),
                    $method
                )
            );
        }

        // set or get networks options
        foreach (self::$networks as $networkName => $networkProperties) {
            $result = array_merge(
                $result,
                $this->processConfigFieldValue(
                    $this->buildSettingsKey($networkName),
                    $method
                ),
                $this->processConfigFieldValue(
                    $this->buildSettingsKey($networkName . '_text'),
                    $method
                ),
                $this->processConfigFieldValue(
                    $this->buildSettingsKey($networkName . '_title'),
                    $method
                ),
                $this->processConfigFieldValue(
                    $this->buildSettingsKey($networkName . '_sort'),
                    $method
                )
            );
            if (!empty($networkProperties['options']) &&
                is_array($networkProperties['options'])
            ) {
                foreach (array_keys($networkProperties['options']) as $optionName) {
                    $result = array_merge(
                        $result,
                        $this->processConfigFieldValue(
                            $this->buildSettingsKey(
                                $networkName . '_' . $optionName
                            ),
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
        $helper->currentIndex = $this->getAdminLink(false);
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
        $style = $this->getConfigFieldValue($this->buildSettingsKey('style'));
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
     * @param mixed $product prestashop product object
     * @return array
     */
    protected function getProductProperties($product)
    {
        $properties = array(
            'price' => Tools::ps_round($product->getPrice(
                !Product::getTaxCalculationMethod(
                    (int) $this->context->cookie->id_customer),
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
        if (!isset($this->context->controller->php_self) ||
            !in_array(
                $this->context->controller->php_self,
                array('product', 'products-comparison')
            )
        ) {
            return;
        }

        $this->addAssets();

        if ($this->context->controller->php_self == 'product') {
            $product = $this->context->controller->getProduct();
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            $cacheId = 'sociallikes_header|'
                . (!empty($product->id) ? $product->id : '');

            // try load from cache or get data
            if (!$this->isCached(
                'sociallikes_header.tpl',
                $this->getCacheId($cacheId)
            )) {
                $this->context->smarty->assign(
                    $this->getProductProperties($product)
                );
            }
        }

        return $this->display(
            __FILE__,
            'sociallikes_header.tpl',
            $this->getCacheId($cacheId)
        );
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
            $values['properties'][$optionName] = $this->getConfigFieldValue(
                $this->buildSettingsKey($optionName)
            );
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

        // build enabled social network list
        foreach (self::$networks as $networkName => $networkProperties) {
            $isDisplay = $this->getConfigFieldValue(
                $this->buildSettingsKey($networkName)
            );
            if ($isDisplay) {
                $values['sociallikes'][$networkName] = array(
                    'text' => $this->getConfigFieldValue(
                        $this->buildSettingsKey($networkName . '_text')
                    ),
                    'title' => $this->getConfigFieldValue(
                        $this->buildSettingsKey($networkName . '_title')
                    ),
                    'sort' => $this->getConfigFieldValue(
                        $this->buildSettingsKey($networkName . '_sort')
                    ),
                );
                if (!empty($networkProperties['options']) &&
                    is_array($networkProperties['options'])
                ) {
                    $specificOptions = array();
                    foreach (array_keys($networkProperties['options']) as $optionName) {
                        $specificOptions[$optionName] = $this->getConfigFieldValue(
                            $this->buildSettingsKey(
                                $networkName . '_' . $optionName
                            )
                        );
                    }
                    $values['sociallikes'][$networkName]['specific'] = $specificOptions;
                }
            }
        }

        uasort($values['sociallikes'], function($a, $b) {
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
        if (!isset($this->context->controller) ||
            !method_exists($this->context->controller, 'getProduct')
        ) {
            return;
        }

        $product = $this->context->controller->getProduct();

        $cacheId = 'sociallikes|'
            . (!empty($product->id) ? $product->id : '');

        $this->context->smarty->assign(
            $this->getConfigFieldValuesForTemplate()
        );

        return $this->display(
            __FILE__,
            'sociallikes.tpl',
            $this->getCacheId($cacheId)
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
