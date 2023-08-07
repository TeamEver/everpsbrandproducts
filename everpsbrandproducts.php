<?php
/**
 * Project : everpsbrandproducts
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.team-ever.com
 */

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Everpsbrandproducts extends Module
{
    private $html;
    private $postErrors = array();
    private $postSuccess = array();

    public function __construct()
    {
        $this->name = 'everpsbrandproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Team Ever';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Ever PS Manufacturer Products');
        $this->description = $this->l('Show products in same manufacturer on product page');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->templateFile = 'module:everpsbrandproducts/views/templates/hook/everpsbrandproducts.tpl';
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('EVERPSBRANDPRODUCTS_NBR', 4);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('displayProductExtraContent');
    }

    public function uninstall()
    {
        Configuration::deleteByName('EVERPSBRANDPRODUCTS_NBR');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitEverpsmanufacturerproductsModule')) == true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }
        $this->context->smarty->assign(array(
            'image_dir' => $this->_path.'views/img',
        ));

        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverpsmanufacturerproductsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Number of products shown'),
                        'name' => 'EVERPSBRANDPRODUCTS_NBR',
                        'desc' => $this->l('Use this module in live mode'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'EVERPSBRANDPRODUCTS_NBR' => Configuration::get(
                'EVERPSBRANDPRODUCTS_NBR'
            ),
        );
    }

    public function postValidation()
    {
        if (((bool)Tools::isSubmit('submitEverpsmanufacturerproductsModule')) == true) {
            if (!Tools::getValue('EVERPSBRANDPRODUCTS_NBR')
                || !Validate::isInt(Tools::getValue('EVERPSBRANDPRODUCTS_NBR'))
            ) {
                $this->posterrors[] = $this->l('error : [Number] is not valid');
            }
        }
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addCSS($this->_path.'views/css/ever.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        // $this->context->controller->addJS($this->_path.'/views/js/front.js');
        // $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayFooterProduct()
    {
        $product = new Product(
            (int)Tools::getValue('id_product'),
            false,
            (int)Context::getContext()->language->id,
            (int)Context::getContext()->shop->id
        );
        $manufacturer = new Manufacturer(
            (int)$product->id_manufacturer,
            (int)Context::getContext()->language->id,
            (int)Context::getContext()->shop->id
        );
        if (!Validate::isLoadedObject($manufacturer)) {
            return;
        }
        $nbr = Configuration::get(
            'EVERPSBRANDPRODUCTS_NBR'
        );
        $manufacturer_products = Manufacturer::getProducts(
            (int)$product->id_manufacturer,
            (int)Context::getContext()->language->id,
            1,
            (int)$nbr
        );

        if (!empty($manufacturer_products)) {
            $showPrice = true;
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever(
                    $this->context->link
                ),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $productsForTemplate = array();

            $presentationSettings->showPrices = $showPrice;

            if (is_array($manufacturer_products)) {
                foreach ($manufacturer_products as $productId) {
                    $productsForTemplate[] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct(array('id_product' => $productId['id_product'])),
                        $this->context->language
                    );
                }
            }
            $this->context->smarty->assign(array(
                'evermanufacturer' => $manufacturer,
                'evermanufacturer_products' => $productsForTemplate,
            ));
            return $this->context->smarty->fetch(
                $this->local_path.'views/templates/hook/everpsbrandproducts.tpl'
            );
        }
    }

    public function hookDisplayProductExtraContent()
    {
        return $this->hookDisplayFooterProduct();
    }
}
