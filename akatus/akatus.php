<?php
/*
|---------------------------------------------------|
|  MÓDULO DE PAGAMENTO AKATUS - CARTÕES DE CRÉDITO  |
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
 * @version 1.0 Beta
 **/

class Akatus extends PaymentModule
{
	private $_html 			= '';
    private $_postErrors 	= array();
    public $currencies;
		
	public function __construct()
    {
        $this->name 			= 'akatus';
        $this->tab 				= 'payments_gateways';
        $this->version 			= '1.0';

        $this->currencies 		= true;
        $this->currencies_mode 	= 'radio';
	
		
        parent::__construct();

        $this->page 			= basename(__file__, '.php');
        $this->displayName 		= $this->l('Akatus - Cartões de Crédito');
        $this->description 		= $this->l('Permite receber pagamentos com cartão de crédito através do gateway Akatus');
		$this->confirmUninstall = $this->l('Tem certeza de que pretende eliminar os seus dados?');
		$this->textshowemail 	= $this->l('Você receberá por mensagens por e-mail informando a cadaatualização da sua compra.');
	}
	
	public function install()
	{
		if(!$email=Configuration::get('AKATUS_EMAIL_CONTA'))
			$email='akatus@seudominio.com.br';
		
		if(!$token=Configuration::get('AKATUS_TOKEN'))
			$token='';
		
		if(!$api=Configuration::get('AKATUS_API_KEY'))
			$api='';
		
		$this->create_states();
		if 
		(
			!parent::install() 
		OR 	!Configuration::updateValue('AKATUS_EMAIL_CONTA', $email)
		OR 	!Configuration::updateValue('AKATUS_TOKEN', 	  $token)
		OR 	!Configuration::updateValue('AKATUS_API_KEY', 	  $api)
		OR 	!Configuration::updateValue('AKATUS_BTN', 	  0)  
		OR 	!Configuration::updateValue('AKATUS_MENSAGEM_EM_ANALISE',   'Seu pagamento encontra-se <span class="price">Em Análise</span> pela operadora do seu cartão de crédito. Você receberá um e-mail automático informando quando o mesmo for aprovado.')    
		OR 	!Configuration::updateValue('AKATUS_MENSAGEM_CANCELADO',   'Seu pagamento <span class="price">não</span> foi autorizado pela operadora do seu cartão. Você pode ter digitado dados errados ou a operação ultrapassa o limite disponível no seu cartão. Por favor, efetue um novo pedido e corrija seus dados. Se necessário, você também poderá escolher outra forma de pagamento.') 
		OR 	!Configuration::updateValue('AKATUS_MENSAGEM_APROVADO',   'Seu pagamento já foi <span class="price">aprovado</span> por sua operadora de cartão e em breve seu pedido começará a ser processado.')    

		OR 	!$this->registerHook('payment') 
		OR 	!$this->registerHook('paymentReturn')
		
		)
			return false;
			
		return true;
	}

	public function create_states()
	{
		#Cria novos status para os pedidos


		
		if(Configuration::get('AKATUS_STATUS_5'))
			return true;
		
		$this->order_state = array(
		array( 'c9fecd', '11110', 'Akatus - Completo',	  	  'payment' ),
		array( 'ccfbff', '00100', 'Akatus - Aguardando Pagamento', 		 ''	),
		array( 'ffffff', '10100', 'Akatus - Pagamento Aprovado',			 	 ''	),
		array( 'fcffcf', '00100', 'Akatus - Pagamento em análise',				 ''	),
		array( 'fec9c9', '11110', 'Akatus - Cancelado', 'order_canceled'	),
		array( 'd6d6d6', '00100', 'Akatus - Em Aberto', ''	)

		);
		
		$languages = Db::getInstance()->ExecuteS('
		SELECT `id_lang`, `iso_code`
		FROM `'._DB_PREFIX_.'lang`
		');
			
		foreach ($this->order_state as $key => $value)
		{
			


			Db::getInstance()->ExecuteS
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
				Db::getInstance()->ExecuteS
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

	public function uninstall()
	{
		if 
		(
			!Configuration::deleteByName('AKATUS_MENSAGEM_EM_ANALISE')
		OR	!Configuration::deleteByName('AKATUS_MENSAGEM_CANCELADO')
		OR	!Configuration::deleteByName('AKATUS_MENSAGEM_APROVADO')
		
		OR 	!parent::uninstall()
		) 
			return false;
		
		return true;
	}

	public function getContent()
	{
		/*
			Essa função é responsável por salvar os dados da configuração
		*/
		$this->_html = '<h2>Akatus - Cartões de Crédito</h2>';
		
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
						
						if (!empty($_POST['akatus_parcelas_semjuros']))
						{
							Configuration::updateValue('AKATUS_PARCELAS_SEMJUROS', $_POST['akatus_parcelas_semjuros']);
						}
						
						if (!empty($_POST['akatus_maximo_parcelas']))
						{
							Configuration::updateValue('AKATUS_MAXIMO_PARCELAS', $_POST['akatus_maximo_parcelas']);
						}
						
						if (!empty($_POST['akatus_mensagem_em_analise']))
						{
							Configuration::updateValue('AKATUS_MENSAGEM_EM_ANALISE', $_POST['akatus_mensagem_em_analise']);
						}
						
						if (!empty($_POST['akatus_mensagem_aprovado']))
						{
							Configuration::updateValue('AKATUS_MENSAGEM_APROVADO', $_POST['akatus_mensagem_aprovado']);
						}
						
						if (!empty($_POST['akatus_mensagem_cancelado']))
						{
							Configuration::updateValue('AKATUS_MENSAGEM_CANCELADO', $_POST['akatus_mensagem_cancelado']);
						}
						
					$this->displayConf();
				}
				else $this->displayErrors();
		}
		elseif (isset($_POST['submitAkatus_Btn']))
		{
			Configuration::updateValue('AKATUS_BTN', 	$_POST['btn_pg']);
			$this->displayConf();
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
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
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
		<img src="../modules/akatus/imagens/akatus.jpg" style="float:left; margin-right:15px;" />
		<b>'.$this->l('Este módulo permite aceitar pagamentos via Akatus.').'</b><br /><br />
		'.$this->l('Se o cliente escolher o módulo de pagamento, a conta do Akatus sera automaticamente creditado.').'<br />
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
			'AKATUS_PARCELAS_SEMJUROS', 
			'AKATUS_MAXIMO_PARCELAS',
			'AKATUS_MENSAGEM_CANCELADO',
			'AKATUS_MENSAGEM_EM_ANALISE',
			'AKATUS_MENSAGEM_APROVADO'
			
			  )
		);
		
		$email_conta	= array_key_exists('email_conta', $_POST) ? $_POST['email_conta'] : (array_key_exists('AKATUS_EMAIL_CONTA', $conf) ? $conf['AKATUS_EMAIL_CONTA'] : '');
		
		$token 			= array_key_exists('akatus_token', $_POST) ? $_POST['akatus_token'] : (array_key_exists('AKATUS_TOKEN', $conf) ? $conf['AKATUS_TOKEN'] : '');
		
		$api_key		= array_key_exists('akatus_api_key', $_POST) ? $_POST['akatus_api_key'] : (array_key_exists('AKATUS_API_KEY', $conf) ? $conf['AKATUS_API_KEY'] : '');
		
		$parcelas_semjuros=array_key_exists('akatus_parcelas_semjuros', $_POST) ? $_POST['akatus_parcelas_semjuros'] : (array_key_exists('AKATUS_PARCELAS_SEMJUROS', $conf) ? $conf['AKATUS_PARCELAS_SEMJUROS'] : '');
		
		$maximo_parcelas=array_key_exists('akatus_maximo_parcelas', $_POST) ? $_POST['akatus_maximo_parcelas'] : (array_key_exists('AKATUS_MAXIMO_PARCELAS', $conf) ? $conf['AKATUS_MAXIMO_PARCELAS'] : '');
		
		$mensagem_aprovado=array_key_exists('akatus_mensagem_aprovado', $_POST) ? $_POST['akatus_mensagem_aprovado'] : (array_key_exists('AKATUS_MENSAGEM_APROVADO', $conf) ? $conf['AKATUS_MENSAGEM_APROVADO'] : '');
		
		$mensagem_cancelado=array_key_exists('akatus_mensagem_cancelado', $_POST) ? $_POST['akatus_mensagem_cancelado'] : (array_key_exists('AKATUS_MENSAGEM_CANCELADO', $conf) ? $conf['AKATUS_MENSAGEM_CANCELADO'] : '');
		
		$mensagem_em_analise=array_key_exists('akatus_mensagem_em_analise', $_POST) ? $_POST['akatus_mensagem_em_analise'] : (array_key_exists('AKATUS_MENSAGEM_EM_ANALISE', $conf) ? $conf['AKATUS_MENSAGEM_EM_ANALISE'] : '');

		/*
		
		Formulário para configuração do módulo
		contém os seguintes campos:
		
		 - E-mail da Conta Akatus
		 - API KEY
		 - TOKEN NIP
		 - Parcelamento sem juros até X parcelas
		 - Número máximo de parcelas
		
		 
		 */
		 
		 $combo_sem_juros='<select name="akatus_parcelas_semjuros" id="parcelas_semjuros">
							  <option value="1">1</option>
							  <option value="2">2</option>
							  <option value="3">3</option>
							  <option value="4">4</option>
							  <option value="5">5</option>
							  <option value="6">6</option>
							  <option value="7">7</option>
							  <option value="8">8</option>
							  <option value="9">9</option>
							  <option value="10">10</option>
							  <option value="11">11</option>
							  <option value="12">12</option>
							</select>';
		 
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
			
			<label>Parcelamento sem juros até</label>
			<div class="margin-form"><input type="text" size="20" name="akatus_parcelas_semjuros" value="'.$parcelas_semjuros.'" /></div>
			<br />
			
			<label>Máximo de parcelas permitidas (até 12 parcelas)</label>
			<div class="margin-form"><input type="text" size="20" name="akatus_maximo_parcelas" value="'.$maximo_parcelas.'" /></div>
			<br />
			
			
			<label>Mensagem para pagamentos Em Análise</label>
			<div class="margin-form"><textarea name="mensagem_em_analise" cols="80" rows="5">'.$mensagem_em_analise.'</textarea></div>
			<br />
			
			<label>Mensagem para pagamentos Cancelados</label>
			<div class="margin-form"><textarea name="mensagem_cancelado" cols="80" rows="5">'.$mensagem_cancelado.'</textarea></div>
			<br />
			
			<label>Mensagem para pagamentos Aprovados</label>
			<div class="margin-form"><textarea name="mensagem_aprovado" cols="80" rows="5">'.$mensagem_aprovado.'</textarea></div>
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
			
			
		
		/*
		
		Começa a calcular os dados para exibir o pagamento com cartão
		
		*/


		
		$valor=number_format($cart->getOrderTotal(true, 3), 2, '.', '');
		$maximo_parcelas=Configuration::get('AKATUS_MAXIMO_PARCELAS');
		$juros=1.99;
		$semjuros=Configuration::get('AKATUS_PARCELAS_SEMJUROS');
		
		
		if($valor>5) 
		{
			$splitss = (int) ($valor/5);
			
			if($splitss<=$maximo_parcelas)
			{
				$total_parcelas = $splitss;
			}
			else
			{
				$total_parcelas = $maximo_parcelas;
			}
		}
		else
		{
			$total_parcelas = 1;
		}
		
		#calcula o parcelamento de acordo com o valor do pedido. A parcela mínima da Akatus é de 5 reais
	
		$parcelamento='<UL id="lista_de_parcelas">';
		
		
		for($j=1; $j<=$total_parcelas;$j++) 
		{
		
			if($semjuros>=$j) 
			{
			
				$parcelas = $valor/$j;
				$parcelas = number_format($parcelas, 2, '.', '');
				
				$parcelamento .= '<option value="'.$j.'">'.$j.'x de R$'.number_format($parcelas, 2,',', '.').' Sem Juros</option>';
				
				
				
			}
			else
			{
			
				$parcelas = Akatus::parcelar($valor, $juros, $j);
				$parcelas = number_format($parcelas, 2, '.', '');
				
				$parcelamento .= '<option value="'.$j.'">'.$j.'x de R$'.number_format($parcelas, 2,',', '.').' Com Juros de '.number_format($juros, 2, ',', ',').'% A.M.</option>
				
				';
				
				
			}
		
		}
		
		$parcelamento .='</UL>';
		
		#Calcula anos da validade do cartão
		
		$anos_validade_cartao='';
		
		for($i=date('Y'); $i<=(date('Y')+10); $i++)
		{
			@$anos_validade_cartao .='<option value="'.($i-2000).'">'.$i.'</option>';
		}

	
		$smarty->assign(array(
		'anos_validade_cartao'	=> $anos_validade_cartao,
		'parcelamento'			=> $parcelamento
		));

		
        return $this->display(__file__, 'payment_execution.tpl');
    }
	
	public function hookPayment($params)
	{
		
		global $smarty;
		$smarty->assign(array(
			'status_pagamento' =>$_REQUEST['res'],
			'imgBtn' => "modules/akatus/imagens/cartoes_akatus.jpg",
			'this_path' => $this->_path, 'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ?
			'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT,
			'UTF-8') . __PS_BASE_URI__ . 'modules/' . $this->name . '/'));
			
			
			
		return $this->display(__file__, 'payment.tpl');
		
	}
	public function xml2array($contents, $get_attributes=1, $priority = 'tag') 
	{ 
    if(!$contents) return array(); 

    if(!function_exists('xml_parser_create')) { 
     
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
		#Descomente a linha abaixo caso precise depurar
		error_reporting(E_ALL); ini_set('display_errors', 'On');
		
        global $smarty;
		
		$referencia=$params['objOrder']->id;
		
		switch($_GET['res'])
		{
			case 1:
				$titulo="O seguinte erro ocorreu:";
				$mensagem=urldecode($_GET['msg']).' - Seu pedido foi <span class="price">Cancelado</span>. Por favor, efetue um novo pedido e corrija seus dados. Se necessário você também poderá escolher uma nova forma de pagamento.';
			break;
			
			case 2:
				$titulo="Obrigado por concluir seu pedido! ";
				$mensagem=Configuration::get('AKATUS_MENSAGEM_EM_ANALISE');
			break;
			
			case 3:
			
			$titulo="Recebimento não autorizado";
			$mensagem=Configuration::get('AKATUS_MENSAGEM_CANCELADO');
			break;
			
			case 4:
			
				$titulo="Obrigado por concluir seu pedido!";
				$mensagem=Configuration::get('AKATUS_MENSAGEM_APROVADO');
			break;
			
			case 5:
				$titulo="Um erro desconhecido ocorreu";
				$mensagem='Um erro desconhecido ocorreu e seu pedido foi Cancelado. Caso seja a primeira vez que está recebendo essa mensagem, por favor, efetue um novo pedido e confira atentamente a todos os seus dados. Caso já tenha recebido essa mensagem anteriormente, por favor, entre em contato através do e-mail'.Configuration::get('AKATUS_EMAIL_CONTA').' e informe o ocorrido. Desculpe pelo inconveniente.';
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
	
	public function parcelar($valorTotal, $taxa, $nParcelas)
	{
		$taxa = $taxa/100;
		$cadaParcela = ($valorTotal*$taxa)/(1-(1/pow(1+$taxa, $nParcelas)));
		return round($cadaParcela, 2);
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
