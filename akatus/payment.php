<?php

/*
+---------------------------------------------------+
|  MÓDULO DE PAGAMENTO AKATUS - CARTÕES DE CRÉDITO  |
|---------------------------------------------------|
|													|
|  Este módulo permite receber pagamentos através   |
|  do gateway de pagamentos Akatus em lojas			|
|  utilizando a plataforma Prestashop				|
|													|
|---------------------------------------------------|
|													|
|  Desenvolvido por: www.andresa.com.br				|
|					 contato@andresa.com.br			|
|													|
+---------------------------------------------------+
*/

/**
 * @author Andresa Martins da Silva
 * @copyright Andresa Web Studio
 * @site http://www.andresa.com.br
 * @version 1.0 Beta
 **/
 
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');

include(dirname(__FILE__).'/../../header.php');

include(dirname(__FILE__).'/akatus.php');




$akatus = new Akatus();
echo $akatus->execPayment($cart);

include_once(dirname(__FILE__).'/../../footer.php');

?>