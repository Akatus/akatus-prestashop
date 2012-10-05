{if $status == 'ok'}
	
	<br />
	<h3>{$titulo}</h3>
<p>{$mensagem} <BR /><BR /></p>
	<p>{l s='Em caso de dúvidas favor utilizar o' mod='akatus'}	<a href="{$base_dir}contact-form.php">{l s='formulário de contato' mod='cheque'}</a>.</p>

	
	{else}
	<p class="warning">
	{l s='Houve alguma falha no envio do seu pedido. Por Favor entre em contato com o nosso Suporte' mod='akatus'} 
	<a href="{$base_dir}contact-form.php">{l s='customer support' mod='akatus'}</a>.
	</p>
{/if}
