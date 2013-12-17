<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
include(dirname(__FILE__).'/akatusb.php');
	
$currency = new Currency(intval(isset($_POST['currency_payement']) ? $_POST['currency_payement'] : $cookie->id_currency));
$total = (number_format($cart->getOrderTotal(true, 3), 2, '.', ''));

$akatus = new AkatusB();

$mailVars = array
(
	'{bankwire_owner}' 		=> $akatus->textshowemail, 
	'{bankwire_details}' 	=> '', 
	'{bankwire_address}' 	=> ''
);




	
	$akatus->validateOrder
	(
		$cart->id, 
		Configuration::get('AKATUS_STATUS_5'), 
		$total, 
		$akatus->displayName, 
		NULL, 
		$mailVars, 
		$currency->id
	);
	


	$desconto=Configuration::get('AKATUSB_DESCONTO');
	
	if($desconto > 0)
		$total=number_format($total-($total*($desconto/100)), 2, '.', '');

	
	
	$order 		= new Order($akatus->currentOrder);
	$idCustomer = $order->id_customer;
	$idLang		= $order->id_lang;
	$customer 	= new Customer(intval($idCustomer));
	$CusMail	= $customer->email;
	
	$id_compra=$order->id;

	

/*

	Seleciona o endereço da fatura para enviar
	ao gateway da Akatus. Mais informações sobre o assunto adiante

*/
	$conexao=mysql_connect(_DB_SERVER_, _DB_USER_, _DB_PASSWD_);
mysql_select_db( _DB_NAME_, $conexao);

$endereco = mysql_query('
	SELECT a.`id_state`, a.`id_customer`, a.`firstname` nome, a.`lastname` sobrenome, 
	a.`address1` endereco, a.`address2` complemento, a.`postcode` cep, a.`city` cidade, c.`email`, s.`iso_code`, a.`phone` 
	FROM `'._DB_PREFIX_.'address` a, `'._DB_PREFIX_.'customer` c
	
	left join `'._DB_PREFIX_.'state` s
		on s.`id_state`=`id_state`
		
	WHERE a.`id_address`='.$cart->id_address_invoice.' AND c.`id_customer`=a.`id_customer` LIMIT 1', $conexao);


			
	$endereco = mysql_fetch_object($endereco);		
	$endereco->telefone=str_pad(substr(preg_replace("/[^0-9]/","",$endereco->phone), 0, 10), 10, "0", STR_PAD_RIGHT);

    $fingerprint_akatus = isset($_POST['fingerprint_akatus']) ? $_POST['fingerprint_akatus'] : '';
    $fingerprint_partner_id = isset($_POST['fingerprint_partner_id']) ? $_POST['fingerprint_partner_id'] : '';

	
    $xml='<?xml version="1.0" encoding="utf-8"?><carrinho>
		<recebedor>
			<api_key>'. Configuration::get('AKATUS_API_KEY').'</api_key>
			<email>'.Configuration::get('AKATUS_EMAIL_CONTA').'</email>
		</recebedor>
		<pagador>
			<nome>'.$endereco->nome.' '.$endereco->sobrenome.'</nome>
			<email>'.$endereco->email.'</email>
				
				<enderecos>
					<endereco>
						<tipo>comercial</tipo>
						<logradouro>'.$endereco->endereco.'</logradouro>
						<numero>XXX</numero>
						<bairro>'.$endereco->complemento.'</bairro>
						<cidade>'.$endereco->cidade.'</cidade>
						<estado>'.$endereco->sigla_estado.'</estado>
						<pais>BRA</pais>
						<cep>'.str_replace(array('.', '-'), '', $endereco->cep).'</cep>
					</endereco>
				</enderecos>

			<telefones>
				<telefone>
					<tipo>residencial</tipo>
					<numero>'.$endereco->telefone.'</numero>
				</telefone>
			</telefones>
		</pagador>

		<produtos>
		   
			<produto>
				<codigo>1</codigo>
				<descricao>Pedido '.$id_compra.' em http://'.Configuration::get('PS_SHOP_DOMAIN').'/</descricao>
				<quantidade>1</quantidade>
				<preco>'. $total .'</preco>
				<peso>0.0</peso>
				<frete>0.00</frete>
				<desconto>0.00</desconto>
			</produto>
		</produtos>
		
		<transacao>
		
			<desconto_total>0.00</desconto_total>
			<peso_total>0.00</peso_total>
			<frete_total>0.00</frete_total>
			<moeda>BRL</moeda>
			
			<referencia>'.($id_compra).'</referencia>
			<meio_de_pagamento>boleto</meio_de_pagamento>
		
            <ip>'.filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4).'</ip>
            <fingerprint_akatus>'.$fingerprint_akatus.'</fingerprint_akatus>                
            <fingerprint_partner_id>'.$fingerprint_partner_id.'</fingerprint_partner_id>                	

		</transacao>
	
	</carrinho>';
	
	

		$xml=utf8_encode($xml);
	
		$URL = "https://www.akatus.com/api/v1/carrinho.xml";
		
		$ch = curl_init($URL);

		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$aka = curl_exec($ch);

		curl_close($ch);

		
		$aka=AkatusB::xml2array($aka);
	
	/*
		De acordo com  o retorno da Akatus, define a mensagem
		que aparecerá na página final do pagamento.
	
	*/
	
    $status = $aka['resposta']['status'];    
    $transacao = $aka['resposta']['transacao'];    
	
	if($status =='erro')
	{
		 $fim_url='&res=1&msg='.urlencode($aka['resposta']['descricao']);
		 $novo_status=(Configuration::get('AKATUS_STATUS_4'));
	}
	else if($status == 'Aguardando Pagamento' )
	{
		$fim_url='&res=2&boleto='.urlencode($aka['resposta']['url_retorno']);
		$novo_status=Configuration::get('AKATUS_STATUS_1');	
	}
	else
	{
		$fim_url='&res=5';
		$novo_status=(Configuration::get('AKATUS_STATUS_4'));
	}

    Db::getInstance()->Execute("
        INSERT INTO transacoes_akatus (referencia, id_transacao) 
        VALUES($id_compra, '$transacao')
    ");
	
		$extraVars 			= array();
        $history 			= new OrderHistory();
        $history->id_order 	= $id_compra;
        $history->changeIdOrderState($novo_status, $id_compra);
	

	Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?id_cart='.$cart->id.'&id_module='.$akatus->id.'&id_order='.$akatus->currentOrder.'&key='.$order->secure_key.$fim_url);

?>
