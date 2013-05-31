/*
+---------------------------------------------------+
|  MÓDULO DE PAGAMENTO AKATUS - TEF / DÉBITO		|
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

	
	function concluir_compra()
	{
		document.getElementById('div_botao_enviar').style.display='none';
		document.getElementById('carregando').style.display='block';
		
		return true;
	}
	
