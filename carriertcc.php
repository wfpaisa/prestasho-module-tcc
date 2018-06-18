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


// CARRIERTCC_CIUDAD_ORIGEN
// correo y si/no
// 
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
						'label' => $this->l('URL'),
						'name' => 'CARRIERTCC_URL',
						'desc' => $this->l('URL webservice, Ejemplo(http://clientes.tcc.com.co/servicios/liquidacionacuerdos.asmx?wsdl)'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'label' => $this->l('Método'),
						'name' => 'CARRIERTCC_METODO',
						'desc' => $this->l('Método de consulta al webservice, Ejemplo(consultarliquidacion)'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'label' => $this->l('Clave'),
						'name' => 'CARRIERTCC_CLAVE',
						'desc' => $this->l('Clave proporcionada por TCC, Ejemplo(MedPropiedades)'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'CARRIERTCC_CUENTA_PAQUETERIA',
						'label' => $this->l('Cuenta paquetería'),
						'desc' => $this->l('Cuenta de paquetería proporcionada por TCC, Ejemplo(1021910)'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'label' => $this->l('Cuenta mensajería'),
						'name' => 'CARRIERTCC_CUENTA_MENSAJERIA',
						'desc' => $this->l('Cuenta de mensajería proporcionada por TCC, Ejemplo(5032800)'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'label' => $this->l('Ciudad Origen'),
						'name' => 'CARRIERTCC_CIUDAD_ORIGEN',
						'desc' => $this->l('Ciudad de origen, Ejemplo para medellin(5001000), ver lista de códigos DANE <<CÓDIGOS DANE>>'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'label' => $this->l('Error email'),
						'name' => 'CARRIERTCC_ERROR_EMAIL',
						'desc' => $this->l('En caso de retornar TCC un error enviarlo a un email, dejarlo en blanco para desactivar'),
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
			'CARRIERTCC_URL' => Configuration::get('CARRIERTCC_URL', 'http://clientes.tcc.com.co/servicios/liquidacionacuerdos.asmx?wsdl'),
			'CARRIERTCC_METODO' => Configuration::get('CARRIERTCC_METODO', 'consultarliquidacion'),
			'CARRIERTCC_CLAVE' => Configuration::get('CARRIERTCC_CLAVE', null),
			'CARRIERTCC_CUENTA_PAQUETERIA' => Configuration::get('CARRIERTCC_CUENTA_PAQUETERIA', null),
			'CARRIERTCC_CUENTA_MENSAJERIA' => Configuration::get('CARRIERTCC_CUENTA_MENSAJERIA', null),
			'CARRIERTCC_CIUDAD_ORIGEN' => Configuration::get('CARRIERTCC_CIUDAD_ORIGEN', '5001000'),
			'CARRIERTCC_ERROR_EMAIL' => Configuration::get('CARRIERTCC_ERROR_EMAIL', null),

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

			// DEBUG
			// print_r( json_encode( Context::getContext()->cart->getProducts(),JSON_PRETTY_PRINT ) );
			$id_address_delivery = Context::getContext()->cart->id_address_delivery;
			$address = new Address($id_address_delivery);
			

			
			if($address->id_country != 69) return false;  // Only Colombia

			if(!$address->postcode) return false; // Necesary post code -> Codigo dane


			/*
				Responce cache
			*/

			// Avoid three times the same request
			// si la direccion cambia?

			if($this->context->cookie->shipping_date_tcc != Context::getContext()->cart->date_upd){
			

				// WEBSERVICE
				$webservice_total = 0;
				$products_total = $this->getProductsTotals();
				$webservice_total = $this->getWebservice($products_total,$address->postcode);
				

				$webservice_total = $this->changeCurrency($webservice_total);

				//webservice_total + (value admin carrier + Shipping costs in every productos)
				$shipping_total_tcc = $webservice_total + $shipping_cost;
				
				
				$this->context->cookie->shipping_date_tcc = Context::getContext()->cart->date_upd;
				$this->context->cookie->shipping_total_tcc = $shipping_total_tcc;

			}else{

				$shipping_total_tcc = $this->context->cookie->shipping_total_tcc;

			}

			
			if($shipping_total_tcc == 0) return false;

			return $shipping_total_tcc;
			
			
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
			$products_total['price'] += (float)$product['price_wt']; //Add values
			$products_total['weight'] += ( (float)$product['weight'] * (float)$product['cart_quantity'] ); // Add value + (weight * quantity)
			$products_total['height'] += ( (float)$product['height'] * (float)$product['cart_quantity'] ); // Add value + (height * quantity)
			$products_total['width'] = ((float)$products_total['width'] < (float)$product['width'])? (float)$product['width'] : $products_total['width']; // greater width
			$products_total['depth'] = ((float)$products_total['depth'] < (float)$product['depth'])? (float)$product['depth'] : $products_total['depth']; // greater depth
		}

		// DEBUG
		// file_put_contents("./modules/carriertcc/logx.txt", json_encode($products_total,JSON_PRETTY_PRINT));

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

		// DEBUG
		// print_r($params);

		$carr_data = 	$this->getConfigFormValues();
		$tcc_url = 		$carr_data['CARRIERTCC_URL'];
		$client = 		new SoapClient($tcc_url);
		$tcc_cuenta = 	$carr_data['CARRIERTCC_CUENTA_MENSAJERIA'];
		$tcc_uen = 		'2';


		if(	$params['weight'] > '5' ||
			$params['height'] > '20' ||
			$params['depth'] > '20' ||
			$params['width'] > '20'
		){
			$tcc_cuenta = 	$carr_data['CARRIERTCC_CUENTA_PAQUETERIA'];
			$tcc_uen = 		'1';
		}


		$soap_params = array(
			'Clave' => 							$carr_data['CARRIERTCC_CLAVE'],
			'Liquidacion' => array(
		 		'tipoenvio' => 					'0',
		 		'fecharemesa' => 				date('Y-m-d'),
		 		'idunidadestrategicanegocio' => $tcc_uen, // 2 Mensajería, 1 Paquetería
				'cuenta' => 					$tcc_cuenta,
				'idciudadorigen' => 			$carr_data['CARRIERTCC_CIUDAD_ORIGEN'], // Medellin
				'idciudaddestino' => 			$city_to,
				'valormercancia' => 			$params['price'],
				'unidadnegocio'=> 				'1',
				'recogida' => 					'0', // Indica si la mercancía se recoge en origen True=si, False= no
				'traecd' => 					'0', // Indica si el cliente lleva el paquete hasta TCC True=si, False=no
				'recogecd' => 					'0', // Indica si el remitente recoge el paquete en TCC True=si, False=no
				'boomerang' => 					'0',
				'unidades' => array(
					array(
						'numerounidades' => 	'1',
						'pesoreal' => 			$params['weight'], // 1 Kilo
						'tipoempaque'=>			'1',
						'unidades'=>			'1',
						'pesovolumen'=>			$params['volume'], // Pasar a metros= alto * largo * ancho * 400
						'alto'=>				$params['height'], // CMs
						'largo'=>				$params['depth'], // CMs
						'ancho'=>				$params['width'], // CMs
					)
				)
			),
			'Respuesta'=>'0',
			'Mensaje'=>'0',

		);



		/* Invoke webservice method */
		$response_raw = $client->__soapCall($carr_data['CARRIERTCC_METODO'], array($soap_params));
		$res = $response_raw->consultarliquidacionResult;
		
		// DEBUG
		// file_put_contents("./modules/carriertcc/log.txt", 
		// 	"--------------\n Parametros enviados:\n--------------\n\n".
		// 	json_encode($soap_params,JSON_PRETTY_PRINT).
		// 	"\n\n\n--------------\n Parametros retornados:\n--------------\n\n".
		// 	json_encode($response_raw,JSON_PRETTY_PRINT));

		if( $res->respuesta->codigo === '-1' || !$res->total->totaldespacho){
			if($carr_data['CARRIERTCC_ERROR_EMAIL']){
				$para      = $carr_data['CARRIERTCC_ERROR_EMAIL'];
				$titulo    = 'Error envios TCC';
				$mensaje   = json_encode($response_raw,JSON_PRETTY_PRINT);
				$cabeceras = 'X-Mailer: PHP/' . phpversion();

				mail($para, $titulo, $mensaje, $cabeceras);
			}
		}else{
			return round((float)$res->total->totaldespacho,0);
		}


		// VALOR DE ENVIO
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
			$carrier->delay[$lang['id_lang']] = $this->l('¡Entrega de 1 a 6 días hábiles!');

		if ($carrier->add() == true)
		{
			@copy(dirname(__FILE__).'/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');
			Configuration::updateValue('CARRIERTCC_CARRIER_ID', (int)$carrier->id);
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
		$this->context->controller->registerJavascript('modules-carriertcc', 'modules/'.$this->name.'/views/js/carriertcc.js', ['position' => 'bottom', 'priority' => 200]);
		$this->context->controller->registerStylesheet('modules-carriertcc', 'modules/'.$this->name.'/views/css/carriertcc.css', ['media' => 'all', 'priority' => 200]);
	}

	public function hookUpdateCarrier($params)
	{
		/**
		 * Not needed since 1.5
		 * You can identify the carrier by the id_reference
		*/
	}
}
