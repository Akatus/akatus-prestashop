<?php

/*
|---------------------------------------------------|
|  M�DULO DE PAGAMENTO AKATUS - CART�ES DE CR�DITO  |
|---------------------------------------------------|
|  Este m�dulo permite receber pagamentos atrav�s   |
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

include(dirname(__FILE__).'/../../../config/config.inc.php');
include(dirname(__FILE__).'/../akatus.php');

if ($_POST['token']==Configuration::get('AKATUS_TOKEN'))
{

	    $id_transacao 		= $_POST['referencia'];
        $status_pagamento 	= $_POST['status'];

        $order 				= new Order(intval($id_transacao));
        $cart 				= Cart::getCartByOrderId($id_transacao);

        $mailVars 			= array('{bankwire_owner}' => '', '{bankwire_details}' => '',
            '{bankwire_address}' => '');

		/*
			O Status "Completo" ainda n�o existe na API da Akatus
			contudo, adicionei ele aqui pois � poss�vel que um dia
			ele seja adicionado
		*/
		
		switch($_POST['status'])
		{
			case 'Completo':
				$status = Configuration::get('AKATUS_STATUS_0');
			break;

			case 'Aguardando Pagamento':
				$status = Configuration::get('AKATUS_STATUS_1');
			break;
			
			case 'Aprovado':
				$status = Configuration::get('AKATUS_STATUS_2');
			break;
			
			case 'Cancelado':
				$status = Configuration::get('AKATUS_STATUS_4');
			break;
			
			case 'Em Análise':
				$status = Configuration::get('AKATUS_STATUS_3');
			break;

			case 'Devolvido':
				$status = configuration::get('AKATUS_STATUS_6');

			break;

            case 'Estornado':
				$status = configuration::get('AKATUS_STATUS_7');

			break;
			
			default:
				$status = _PS_OS_ERROR_;
			break;
		}

		$akatus				= new Akatus();	
		$idCustomer 		= $order->id_customer;
		$idLang				= $order->id_lang;
		$customer 			= new Customer(intval($idCustomer));
		$CusMail			= $customer->email;

        $extraVars 			= array();
        $history 			= new OrderHistory();
        $history->id_order 	= intval($id_transacao);
        $history->changeIdOrderState(intval($status), intval($id_transacao));

        exit;
}

?>
