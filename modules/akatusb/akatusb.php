<?php


/*
|---------------------------------------------------|
|  MÓDULO DE PAGAMENTO AKATUS - BOLETO BANCÁRIO		|
|---------------------------------------------------|
|  Este módulo permite receber pagamentos através   |
|  do gateway de pagamentos Akatus em lojas			|
|   utilizando a plataforma Prestashop				|
|---------------------------------------------------|
|  Desenvolvido por: www.andresa.com.br				|
|					 contato@andresa.com.br			|
|---------------------------------------------------|
*/


/**
 * @author Andresa Martins da Silva
 * @copyright Andresa Web Studio
 * @site http://www.andresa.com.br
 **/

class AkatusB extends PaymentModule
{
	private $_html 			= '';
    private $_postErrors 	= array();
    public $currencies;
		
	public function __construct()
    {
        $this->name 			= 'akatusb';
        $this->tab 				= 'payments_gateways';
        $this->version 			= '1.0';

        $this->currencies 		= true;
        $this->currencies_mode 	= 'radio';

        parent::__construct();

        $this->page 			= basename(__file__, '.php');
        $this->displayName 		= $this->l('Akatus - Boleto Bancário');
        $this->description 		= $this->l('Permite receber pagamentos com boleto bancário através do gateway Akatus');
		$this->confirmUninstall = $this->l('Tem certeza de que pretende eliminar os seus dados?');
		$this->textshowemail 	= $this->l('Você receberá por mensagens por e-mail informando a cada atualização da sua compra.');
	}
	
	public function install()
	{
		
		$this->create_states();
        $this->create_akatus_transacoes_table();

		if(!$email=Configuration::get('AKATUS_EMAIL_CONTA'))
			$email='akatus@seudominio.com.br';
		
		if(!$token=Configuration::get('AKATUS_TOKEN'))
			$token='';
		
		if(!$api=Configuration::get('AKATUS_API_KEY'))
			$api='';

		if 
		(
			!parent::install() 
		OR 	!Configuration::updateValue('AKATUS_EMAIL_CONTA', $email)
		OR 	!Configuration::updateValue('AKATUS_TOKEN', 	  $token)
		OR 	!Configuration::updateValue('AKATUS_API_KEY', 	  $api)
		OR 	!Configuration::updateValue('AKATUSB_BTN', 	  0)  
		OR 	!Configuration::updateValue('AKATUSB_DESCONTO', 	  0)  
		OR 	!Configuration::updateValue('AKATUSB_MENSAGEM_PAGAMENTO',   'Clique no botão abaixo para efetuar a impressão do seu boleto. Note que seu pedido começará a ser processado apenas após a confirmação do pagamento do boleto, o que pode levar até 3 dias úteis.')   

		OR 	!$this->registerHook('payment') 
		OR 	!$this->registerHook('paymentReturn')
		)
			return false;
			
		return true;
	}

	public function create_states()
	{
        if (Configuration::get('AKATUS_STATUS_1')
            && Configuration::get('AKATUS_STATUS_2')
            && Configuration::get('AKATUS_STATUS_3')
            && Configuration::get('AKATUS_STATUS_4')
            && Configuration::get('AKATUS_STATUS_5')
            && Configuration::get('AKATUS_STATUS_6')
            && Configuration::get('AKATUS_STATUS_7')) {
            
            return true;
            
        }
		
		$this->order_state = array(
            array( 'c9fecd', '11110', 'Akatus - Completo',              'payment'),
            array( 'ccfbff', '00100', 'Akatus - Aguardando Pagamento',  ''),
            array( 'ffffff', '10100', 'Akatus - Pagamento Aprovado',    ''),
            array( 'fcffcf', '00100', 'Akatus - Pagamento em análise',  ''),
            array( 'fec9c9', '11110', 'Akatus - Cancelado',             'order_canceled'),
            array( 'd6d6d6', '00100', 'Akatus - Em Aberto',             ''),
            array( 'd6d6d6', '11110', 'Akatus - Devolvido',             'refund'),
            array( 'd6d6d6', '11110', 'Akatus - Estornado',             '')
		);
		
		$languages = Db::getInstance()->ExecuteS('
		SELECT `id_lang`, `iso_code`
		FROM `'._DB_PREFIX_.'lang`
		');
			
		foreach ($this->order_state as $key => $value)
		{
			Db::getInstance()->Execute
			('
				INSERT INTO `' . _DB_PREFIX_ . 'order_state` 
			( `invoice`, `send_email`, `color`, `unremovable`, `logable`, `delivery`) 
				VALUES
			('.$value[1][0].', '.$value[1][1].', \'#'.$value[0].'\', '.$value[1][2].', '.$value[1][3].', '.$value[1][4].');
			');
			
			$sql_status = Db::getInstance()->ExecuteS
		('
			SELECT `id_order_state` FROM `'. _DB_PREFIX_ . 'order_state` order by `id_order_state` desc limit 1
			
		');



		$temp_atual = $sql_status[0]["id_order_state"];

		
			


			foreach ( $languages as $language_atual )
			{
				Db::getInstance()->Execute
				('
					INSERT INTO `' . _DB_PREFIX_ . 'order_state_lang` 
				(`id_order_state`, `id_lang`, `name`, `template`)
					VALUES
				('.$temp_atual .', '.$language_atual['id_lang'].', \''.$value[2].'\', \''.$value[3].'\');
				');

			}
			
			
				$file 		= (dirname(__file__) . "/icons/$key.gif");
				$newfile 	= (dirname( dirname (dirname(__file__) ) ) . "/img/os/$temp_atual.gif");
				if (!copy($file, $newfile)) {
    			return false;
    			}
    			
    		Configuration::updateValue("AKATUS_STATUS_$key", 	$temp_atual);
    		   				
		}
	}

    public function create_akatus_transacoes_table()
    {
        Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS transacoes_akatus (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `referencia` int(11) NOT NULL,
            `id_transacao` varchar(255) NOT NULL,
            PRIMARY KEY (`id`))
		');
    }

	public function uninstall()
	{
		if 
		(
			!parent::uninstall()
		) 
			return false;
		
		return true;
	}

	public function getContent()
	{
		/*
			Essa função é responsável por salvar os dados da configuração
		*/
		$this->_html = '<h2>Akatus - Boleto Bancário</h2>';
		
		if (isset($_POST['submitAkatus']))
		{
			if (empty($_POST['email_conta'])) $this->_postErrors[] = $this->l('Digite o e-mail da sua conta Akatus');
			elseif (!Validate::isEmail($_POST['email_conta'])) $this->_postErrors[] = $this->l('Digite um e-mail válido!');
			
				if (!sizeof($this->_postErrors)) 
				{
						Configuration::updateValue('AKATUS_EMAIL_CONTA', $_POST['email_conta']);
						
						if (!empty($_POST['akatus_token']))
						{
							Configuration::updateValue('AKATUS_TOKEN', $_POST['akatus_token']);
						}
						
						if (!empty($_POST['akatus_api_key']))
						{
							Configuration::updateValue('AKATUS_API_KEY', $_POST['akatus_api_key']);
						}
						
						if (!empty($_POST['akatusb_desconto']))
						{
							Configuration::updateValue('AKATUSB_DESCONTO', number_format(str_replace(array('%', ','), array('', '.'), $_POST['akatusb_desconto']), 2, '.', ''));
						}
						
						
						
						if (!empty($_POST['akatusb_mensagem_pagamento']))
						{
							Configuration::updateValue('AKATUSB_MENSAGEM_PAGAMENTO', $_POST['akatusb_mensagem_pagamento']);
						}
						
						
					$this->displayConf();
				}
				else $this->displayErrors();
		}
		
		$this->displayAkatus();
		$this->displayFormSettingsAkatus();
		
		return $this->_html;
	}
	
	public function displayConf()
	{
		$this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Configurações do módulo atualizadas com sucesso!').'
		</div>';
	}
	
	public function displayErrors()
	{
		$nbErrors = sizeof($this->_postErrors);
		$this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('Há') : $this->l('Há')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('erros') : $this->l('error')).'</h3>
			<ol>';
		foreach ($this->_postErrors AS $error)
			$this->_html .= '<li>'.$error.'</li>';
		$this->_html .= '
			</ol>
		</div>';
	}

	public function displayAkatus()
	{
		$this->_html .= '
		<img src="../modules/akatusb/imagens/akatus.jpg" style="float:left; margin-right:15px;" />
		<b>'.$this->l('Este módulo permite aceitar pagamentos com Boleto via Akatus.').'</b><br /><br />
		'.$this->l('Se o cliente escolher esse módulo de pagamento, ele poderá imprimir um boleto bancário para pagar a compra.').'<br />
		'.$this->l('É obrigatório que todas as configurações sejam preenchidas para que o módulo funcione adequadamente.').'
		<br /><br /><br />';
	}

	public function displayFormSettingsAkatus()
	{
		$conf = Configuration::getMultiple
		(array(
			'AKATUS_EMAIL_CONTA',
			'AKATUS_TOKEN',
			'AKATUS_API_KEY',
			'AKATUSB_MENSAGEM_PAGAMENTO',
			'AKATUSB_DESCONTO'
			
			  )
		);
		
		$email_conta	= array_key_exists('email_conta', $_POST) ? $_POST['email_conta'] : (array_key_exists('AKATUS_EMAIL_CONTA', $conf) ? $conf['AKATUS_EMAIL_CONTA'] : '');
		
		$token 			= array_key_exists('akatus_token', $_POST) ? $_POST['akatus_token'] : (array_key_exists('AKATUS_TOKEN', $conf) ? $conf['AKATUS_TOKEN'] : '');
		
		$api_key		= array_key_exists('akatus_api_key', $_POST) ? $_POST['akatus_api_key'] : (array_key_exists('AKATUS_API_KEY', $conf) ? $conf['AKATUS_API_KEY'] : '');
		
		$parcelas_semjuros=array_key_exists('akatus_parcelas_semjuros', $_POST) ? $_POST['akatus_parcelas_semjuros'] : (array_key_exists('AKATUSB_PARCELAS_SEMJUROS', $conf) ? $conf['AKATUSB_PARCELAS_SEMJUROS'] : '');
		
		$maximo_parcelas=array_key_exists('akatus_maximo_parcelas', $_POST) ? $_POST['akatus_maximo_parcelas'] : (array_key_exists('AKATUSB_MAXIMO_PARCELAS', $conf) ? $conf['AKATUSB_MAXIMO_PARCELAS'] : '');
		
		$mensagem_pagamento=array_key_exists('akatusb_mensagem_pagamento', $_POST) ? $_POST['akatusb_mensagem_pagamento'] : (array_key_exists('AKATUSB_MENSAGEM_PAGAMENTO', $conf) ? $conf['AKATUSB_MENSAGEM_PAGAMENTO'] : '');
		
		$desconto=array_key_exists('akatusb_desconto', $_POST) ? $_POST['akatusb_desconto'] : (array_key_exists('AKATUSB_DESCONTO', $conf) ? $conf['AKATUSB_DESCONTO'] : '');
		

		/*
		
		Formulário para configuração do módulo
		contém os seguintes campos:
		
		 - E-mail da Conta Akatus
		 - API KEY
		 - TOKEN NIP
		 - Parcelamento sem juros até X parcelas
		 - Número máximo de parcelas
		
		 
		 */
		 
		
		 
		$this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Configurações').'</legend>
			<label>'.$this->l('E-mail da sua conta Akatus').':</label>
			<div class="margin-form"><input type="text" size="33" name="email_conta" value="'.htmlentities($email_conta, ENT_COMPAT, 'UTF-8').'" /></div>
			<br />
			
			<label>Token NIP:</label>
			<div class="margin-form"><input type="text" size="60" name="akatus_token" value="'.$token.'" /></div>
			<br />
			
			<label>API KEY:</label>
			<div class="margin-form"><input type="text" size="60" name="akatus_api_key" value="'.$api_key.'" /></div>
			<br />
			<!--
			<label>Desconto (%)</label>
			<div class="margin-form"><input type="text" size="10" name="akatusb_desconto" value="'.$desconto.'" />%</div>
			<br />
			-->
			
			
			
			<label>Mensagem para a tela de impressão do boleto</label>
			<div class="margin-form"><textarea name="akatusb_mensagem_pagamento" cols="80" rows="5">'.$mensagem_pagamento.'</textarea></div>
			<br />
			
			<center><input type="submit" name="submitAkatus" value="'.$this->l('Atualizar').'" class="button" /></center>
		</fieldset>
		</form>';
		
		
	}

    public function execPayment($cart)
    {
        global $cookie, $smarty;
		
        $invoiceAddress 	= new Address(intval($cart->id_address_invoice));

        $customerPag 		= new Customer(intval($cart->id_customer));
        $currencies 		= Currency::getCurrencies();
        $currencies_used 	= array();
		$currency 			= $this->getCurrency();

        $currencies 		= Currency::getCurrencies();		
	
		
        foreach ($currencies as $key => $currency)
            $smarty->assign(array(
			'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
            'currencies' => $currencies_used, 
			'imgBtn' => "imagens/cartoes_akatus.jpg",
			
            'currency_default' => new Currency(Configuration::get('PS_CURRENCY_DEFAULT')),
            'currencies' => $currencies_used, 
			'total' => number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $currency), 2, '.', ''), 
			'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
            'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));

        return $this->display(__file__, 'payment_execution.tpl');
    }
	
	public function hookPayment($params)
	{
		
		global $smarty;
		$smarty->assign(array(
			'imgBtn' => "modules/akatusb/imagens/boleto_akatus.jpg",
			'this_path' => $this->_path, 'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
			'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,
			'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));
			
			
			
		return $this->display(__file__, 'payment.tpl');
		
	}
	public function xml2array($contents, $get_attributes=1, $priority = 'tag') 
	{ 
		if(!$contents) return array(); 
	
		if(!function_exists('xml_parser_create')) 
		{ 
			return array(); 
		} 

    $parser = xml_parser_create(''); 
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
    xml_parse_into_struct($parser, trim($contents), $xml_values); 
    xml_parser_free($parser); 

    if(!$xml_values) return;

    $xml_array = array(); 
    $parents = array(); 
    $opened_tags = array(); 
    $arr = array(); 

    $current = &$xml_array;

    $repeated_tag_index = array();
    foreach($xml_values as $data) { 
        unset($attributes,$value);

        extract($data);
		
        $result = array(); 
        $attributes_data = array(); 
         
        if(isset($value)) { 
            if($priority == 'tag') $result = $value; 
            else $result['value'] = $value; 
        } 

        if(isset($attributes) and $get_attributes) { 
            foreach($attributes as $attr => $val) { 
                if($priority == 'tag') $attributes_data[$attr] = $val; 
                else $result['attr'][$attr] = $val; 
            } 
        } 

        if($type == "open") {
            $parent[$level-1] = &$current; 
            if(!is_array($current) or (!in_array($tag, array_keys($current)))) {
                $current[$tag] = $result; 
                if($attributes_data) $current[$tag. '_attr'] = $attributes_data; 
                $repeated_tag_index[$tag.'_'.$level] = 1; 

                $current = &$current[$tag]; 

            } else {

                if(isset($current[$tag][0])) {
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
                    $repeated_tag_index[$tag.'_'.$level]++; 
                } else {
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag.'_'.$level] = 2; 
                     
                    if(isset($current[$tag.'_attr'])) {  
                        $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
                        unset($current[$tag.'_attr']); 
                    } 

                } 
                $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1; 
                $current = &$current[$tag][$last_item_index]; 
            } 

        } elseif($type == "complete") { 
            if(!isset($current[$tag])) { 
                $current[$tag] = $result; 
                $repeated_tag_index[$tag.'_'.$level] = 1; 
                if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data; 

            } else { 
                if(isset($current[$tag][0]) and is_array($current[$tag])) {
                    $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result; 
                     
                    if($priority == 'tag' and $get_attributes and $attributes_data) { 
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
                    } 
                    $repeated_tag_index[$tag.'_'.$level]++; 

                } else { 
                    $current[$tag] = array($current[$tag],$result);
                    $repeated_tag_index[$tag.'_'.$level] = 1; 
                    if($priority == 'tag' and $get_attributes) { 
                        if(isset($current[$tag.'_attr'])) { 
                             
                            $current[$tag]['0_attr'] = $current[$tag.'_attr']; 
                            unset($current[$tag.'_attr']); 
                        } 
                         
                        if($attributes_data) { 
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data; 
                        } 
                    } 
                    $repeated_tag_index[$tag.'_'.$level]++; 
                } 
            } 

        } elseif($type == 'close') { 
            $current = &$parent[$level-1]; 
        } 
    } 
     
    return($xml_array); 
} 
	
	public function hookPaymentReturn($params)
    {
        global $smarty;
		
		$referencia=$params['objOrder']->id;
		$url_boleto=urldecode($_GET['boleto']);
		
		switch($_GET['res'])
		{
			case 1:
				$titulo="O seguinte erro ocorreu:";
				$mensagem=urldecode($_GET['msg']);
			break;
			
			case 2:
				$titulo="Pedido concluído com sucesso!";
				
				$mensagem=Configuration::get('AKATUSB_MENSAGEM_PAGAMENTO').'<BR /><BR /><a href="'.$url_boleto.'" target="_blank"><img src="modules/akatusb/imagens/botao_imprimir_boleto.png" /></a><BR />Link direto para o boleto: '.$url_boleto.'';
				
			
			break;
			
			
			case 5:
			
				$titulo="Um erro desconhecido ocorreu";
				$mensagem="Um erro desconhecido ocorreu e seu pedido foi cancelado. Por favor, efetue um novo pedido, verificando atentamente a todos os seus dados. Caso não seja a primeira vez que está recebendo esta mensagem, por favor, entre em contato conosco através do e-mail ".Configuration::get('AKATUS_EMAIL_CONTA').' e nos informe o ocorrido.<BR><BR>Desculpe-nos pelo inconveniente.<BR><BR>';
			break;
		}
		
		
		$smarty->assign(array(
			'status' 		=> 'ok', 
			'id_order' 		=> $params['objOrder']->id,
			'secure_key' 	=> $params['objOrder']->secure_key,
			'id_module' 	=> $this->id,
			'url_loja'		=> __PS_BASE_URI__,
			'titulo'		=> $titulo,
			'mensagem'		=> $mensagem
		));
		
		return $this->display(__file__, 'payment_return.tpl');
    }
	
	
    
    function hookHome($params)
	{
    	include(dirname(__FILE__).'/includes/retorno.php');
    }
    
        function getStatus($param)
    {
    	global $cookie;
    		
    		$sql_status = Db::getInstance()->Execute
		('
			SELECT `name`
			FROM `'._DB_PREFIX_.'order_state_lang`
			WHERE `id_order_state` = '.$param.'
			AND `id_lang` = '.$cookie->id_lang.'
			
		');
		
		return mysql_result($sql_status, 0);
    }
   
	
	public function getUrlByMyOrder($myOrder)
	{

		$module				= Module::getInstanceByName($myOrder->module);			
		$pagina_qstring		= __PS_BASE_URI__."order-confirmation.php?id_cart="
							  .$myOrder->id_cart."&id_module=".$module->id."&id_order="
							  .$myOrder->id."&key=".$myOrder->secure_key;			
		
		if	(	$_SERVER['HTTPS']	!=	"on"	)
		$protocolo			=	"http";
		
		else
		$protocolo			=	"https";
		
		$retorno 			= $protocolo . "://" . $_SERVER['SERVER_NAME'] . $pagina_qstring;	
				
		return $retorno;

	}
    
}
?>
