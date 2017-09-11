<?php
if (!defined('_PS_VERSION_')) {
	exit;
}

class Carriertcc extends CarrierModule
{
	protected $config_form = false;

	public function __construct()
	{
		$this->name = 'carriertcc';
		$this->tab = 'shipping_logistics';
		$this->version = '0.1.0';
		$this->author = 'wfpaisa';
		$this->need_instance = 0;

		/**
		 * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
		 */
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Carrier TCC');
		$this->description = $this->l('Permite integrar los servicios del Transportista TCC Colombia en su tienda ');
	}

	/**
	 * Don't forget to create update methods if needed:
	 * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
	 */
	public function install()
	{
		if (extension_loaded('curl') == false)
		{
			$this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
			return false;
		}

		$carrier = $this->addCarrier();
		$this->addZones($carrier);
		$this->addGroups($carrier);
		$this->addRanges($carrier);	

		return parent::install() &&
			$this->registerHook('header') &&
			$this->registerHook('backOfficeHeader') &&
			$this->registerHook('updateCarrier');
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	/**
	 * Load the configuration form
	 */
	public function getContent()
	{
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitCarriertccModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);

		$output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

		return $output.$this->renderForm();
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
		$helper->submit_action = 'submitCarriertccModule';
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
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter 0 in test mode, and 1 in production mode'),
						'name' => 'CARRIERTCC_ACCOUNT_MODE',
						'label' => $this->l('Mode'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter a valid URL ex(http://clientes.tcc.com.co/servicios/liquidacionacuerdos.asmx?wsdl)'),
						'name' => 'CARRIERTCC_ACCOUNT_URL',
						'label' => $this->l('Url'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter a valid method, Ex(LiquidaDespacho2)'),
						'name' => 'CARRIERTCC_ACCOUNT_METHOD',
						'label' => $this->l('Method'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter a valid Cuenta, contact TCC Support'),
						'name' => 'CARRIERTCC_ACCOUNT_CUENTA',
						'label' => $this->l('Cuenta'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter a valid Clave, contact TCC Support'),
						'name' => 'CARRIERTCC_ACCOUNT_CLAVE',
						'label' => $this->l('Clave'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'desc' => $this->l('Enter a DANE number city origen, medellin(5001000),bogota:(11001000)'),
						'name' => 'CARRIERTCC_ACCOUNT_CITY',
						'label' => $this->l('Ciudad Origen'),
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
			'CARRIERTCC_ACCOUNT_MODE' => Configuration::get('CARRIERTCC_ACCOUNT_MODE', '0'),
			'CARRIERTCC_ACCOUNT_URL' => Configuration::get('CARRIERTCC_ACCOUNT_URL', 'http://clientes.tcc.com.co/servicios/liquidacionacuerdos.asmx?wsdl'),
			'CARRIERTCC_ACCOUNT_METHOD' => Configuration::get('CARRIERTCC_ACCOUNT_METHOD', 'LiquidaDespacho2'),
			'CARRIERTCC_ACCOUNT_CUENTA' => Configuration::get('CARRIERTCC_ACCOUNT_CUENTA', null),
			'CARRIERTCC_ACCOUNT_CLAVE' => Configuration::get('CARRIERTCC_ACCOUNT_CLAVE', null),
			'CARRIERTCC_ACCOUNT_CITY' => Configuration::get('CARRIERTCC_ACCOUNT_CITY', '5001000'),

		);
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

	public function getOrderShippingCost($params, $shipping_cost)
	{
		if (Context::getContext()->customer->logged == true)
		{


			$id_address_delivery = Context::getContext()->cart->id_address_delivery;
			$address = new Address($id_address_delivery);
			

			
			if($address->id_country != 69) return false;  // Only Colombia

			if(!$address->postcode) return false; // Necesary post code -> Codigo dane


			/*
				Responce cache
			*/

			// Avoid three times the same request
// si la direccion cambia?
			if($this->context->cookie->shipping_date != Context::getContext()->cart->date_upd){
			

				// WEBSERVICE
				$webservice_total = 0;
				$products_total = $this->getProductsTotals();
				$webservice_total = $this->getWebservice($products_total,$address->postcode);
				

				$webservice_total = $this->changeCurrency($webservice_total);

				//webservice_total + (value admin carrier + Shipping costs in every productos)
				$shipping_total = $webservice_total + $shipping_cost;
				
				
				$this->context->cookie->shipping_date = Context::getContext()->cart->date_upd;
				$this->context->cookie->shipping_total = $shipping_total;

			 }else{
			 	$shipping_total = $this->context->cookie->shipping_total;
			 }
							

			//print_r("\n-------------------------------------------------------------------------------------------------------");
			// print_r($address);
			// print_r($shipping_cost); // Costo de envio segun el ingresado manualmente los transportistas del administrador
			// print_r($params); // Datos del pedido
			// print_r(Context::getContext()->cart);
			// print_r($this->context->cookie);
			// print_r($this->getConfigFormValues()); // Configuraciones
			// 
			
			if($shipping_total == 0) return false;

			return $shipping_total;
			
			
		}

		return $shipping_cost;
	}


	/*	Get products
		Return total of price, width, height, depth and weight.
	*/
	protected function getProductsTotals(){
		$products = Context::getContext()->cart->getProducts();
		
		$products_total = array(
			'price'=>0,
			'width' => 0,
			'height' => 0,
			'depth' => 0,
			'weight' => 0,
		);

		foreach ($products as $product){
			$products_total['price'] += (float)$product['total']; //Add values
			$products_total['weight'] += ( (float)$product['weight'] * (float)$product['cart_quantity'] ); // Add value + (weight * quantity)
			$products_total['height'] += ( (float)$product['height'] * (float)$product['cart_quantity'] ); // Add value + (height * quantity)
			$products_total['width'] = ((float)$products_total['width'] < (float)$product['width'])? (float)$product['width'] : $products_total['width']; // greater width
			$products_total['depth'] = ((float)$products_total['depth'] < (float)$product['depth'])? (float)$product['depth'] : $products_total['depth']; // greater depth
		}

		// Approximate the greater
		$products_total['price'] = ceil($products_total['price']);
		$products_total['weight'] = ceil($products_total['weight']);

		// Volume
		$products_total['volume'] = (($products_total['width'] / 100) * ($products_total['height'] / 100) * ($products_total['depth'] / 100) ) * 400;

		return $products_total;
	}

	/* Convert to current currency */
	protected function changeCurrency($money){

		$currency_cop_id = Context::getContext()->currency->getIdByIsoCode('COP');
		$currency_current =  new Currency(Context::getContext()->cart->id_currency);
		$money_to_change = (float)$money;

		if( $currency_cop_id != $currency_current->id){

			$currency_cop = new Currency($currency_cop_id);
			
			// Change to store default rate
			$money_to_change = $money_to_change / $currency_cop->conversion_rate;

			// Change user currency rate, and default decimals
			$money_to_change = round($money_to_change * $currency_current->conversion_rate, $currency_current->decimals);

		}

		return $money_to_change;
	}

	protected function getWebservice($params,$city_to){

		$carr_data = $this->getConfigFormValues();
		$carr_mode = $carr_data['CARRIERTCC_ACCOUNT_MODE'];
		
		$url = $carr_data['CARRIERTCC_ACCOUNT_URL'];

		/* Initialize webservice TCC WSDL */
		$client = new SoapClient($url);

		//print_r($params);

		/* Get TCC functions and types functions */
		// print_r($client->__getFunctions());
		// print_r($client->__getTypes()); 

		/* Parameters for the TCC request  */
		$soap_params = array(
			"Clave" => $carr_data['CARRIERTCC_ACCOUNT_CLAVE'],
			"Despacho" => array(
				"cuenta" => $carr_data['CARRIERTCC_ACCOUNT_CUENTA'],
				"idciudadorigen" => $carr_data['CARRIERTCC_ACCOUNT_CITY'], // Medellin
				"idciudaddestino" => $city_to, //Bogota
				"valormercancia" => $params['price'],
				"unidadnegocio"=> "2",
				"recogida" => "0", //Indica si la mercancía se recoge en origen True=si, False= no
				"traecd" => "0", //Indica si el cliente lleva el paquete hasta TCC True=si, False=no
				"recogecd" => "0",//Indica si el remitente recoge el paquete en TCC True=si, False=no
				"boomerang" => "0",
				"unidades" => array(
					"unidad2"=>array(
						"tipoempaque"=>"",
						"unidades"=>"1",
						"peso"=>$params['weight'], // kilos
						"volumen"=>$params['volume'], //pasar a metros= alto * largo * ancho * 400 
						"alto"=>$params['height'], //cm
						"largo"=>$params['depth'],//cm
						"ancho"=>$params['width'],//cm
					)
				)

			),
			"Liquidacion"=>"",
			"Respuesta"=>"0",
			"Mensaje"=>"0",

		);

		/* Invoke webservice method */
		//$responseCity = $client->__soapCall("consultarMaestroCiudades", array($soap_params));
		$response = $client->__soapCall($carr_data['CARRIERTCC_ACCOUNT_METHOD'], array($soap_params));

		/* Response object to array to XML */
		$response1 = get_object_vars($response);
		
		
		if($response1['Liquidacion']){
			$response2 = new SimpleXMLElement($response1['Liquidacion']);
			
			if(array_key_exists('valortotal',$response2)){
				
				// redondear
				$resp = ceil((float)$response2->valortotal/100)*100;
				
				return $resp;
			}
			
		}


		return 0;

	}

	public function getOrderShippingCostExternal($params)
	{
		return true;
	}

	protected function addCarrier()
	{
		$carrier = new Carrier();

		$carrier->name = $this->l('TCC');
		$carrier->is_module = true;
		$carrier->active = 1;
		$carrier->range_behavior = 1;
		$carrier->need_range = 1;
		$carrier->shipping_external = true;
		$carrier->range_behavior = 0;
		$carrier->external_module_name = $this->name;
		$carrier->shipping_method = 2;

		foreach (Language::getLanguages() as $lang)
			$carrier->delay[$lang['id_lang']] = $this->l('Super fast delivery');

		if ($carrier->add() == true)
		{
			@copy(dirname(__FILE__).'/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
			Configuration::updateValue('MYSHIPPINGMODULE_CARRIER_ID', (int)$carrier->id);
			return $carrier;
		}

		return false;
	}

	protected function addGroups($carrier)
	{
		$groups_ids = array();
		$groups = Group::getGroups(Context::getContext()->language->id);
		foreach ($groups as $group)
			$groups_ids[] = $group['id_group'];

		$carrier->setGroups($groups_ids);
	}

	protected function addRanges($carrier)
	{
		$range_price = new RangePrice();
		$range_price->id_carrier = $carrier->id;
		$range_price->delimiter1 = '0';
		$range_price->delimiter2 = '10000';
		$range_price->add();

		$range_weight = new RangeWeight();
		$range_weight->id_carrier = $carrier->id;
		$range_weight->delimiter1 = '0';
		$range_weight->delimiter2 = '10000';
		$range_weight->add();
	}

	protected function addZones($carrier)
	{
		$zones = Zone::getZones();

		foreach ($zones as $zone)
			$carrier->addZone($zone['id_zone']);
	}

	/**
	* Add the CSS & JavaScript files you want to be loaded in the BO.
	*/
	public function hookBackOfficeHeader()
	{
		if (Tools::getValue('module_name') == $this->name) {
			$this->context->controller->addJS($this->_path.'views/js/back.js');
			$this->context->controller->addCSS($this->_path.'views/css/back.css');
		}
	}

	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */
	public function hookHeader()
	{
		$this->context->controller->addJS($this->_path.'/views/js/front.js');
		$this->context->controller->addCSS($this->_path.'/views/css/front.css');
	}

	public function hookUpdateCarrier($params)
	{
		/**
		 * Not needed since 1.5
		 * You can identify the carrier by the id_reference
		*/
	}
}
