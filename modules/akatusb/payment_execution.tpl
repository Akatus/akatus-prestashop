{capture name=path}{l s="Shipping"}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s="Order summary" mod="akatusb"}</h2>

{assign var="current_step" value="payment"}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s="Pagamento via Boleto Bancário (Akatus)" mod="akatusb"}</h3>
<p>Ao clicar em Concluir Pedido, será exibida uma nova tela para que você possa realizar a impressão do boleto bancário. Note que seu pedido começará a ser processado apenas após a confirmação do pagamento do boleto, o que pode levar até 3 dias úteis.</p>
<script type="text/javascript" src="js/validacao.js"></script>
<link href="estilos.css" rel="stylesheet" type="text/css" />
<form action="{$this_path_ssl}validation.php" method="post" onsubmit="return concluir_compra()" name="pagamento" id="pagamento">
 
    <div id="div_botao_enviar">
    <CENTER><input name="botao_enviar" id="botao_enviar" type="submit" value="Concluir Pedido" class="button" /></CENTER>
    </div>
    <div id="carregando"><center><img src="imagens/carregando.gif" /></center></div>
  </form>

<script>

    $(function(){
        $.getScript("https://static.akatus.com/js/akatus.min.js",function() {                                                                                                                                        
            var formulario = document.getElementById('pagamento');
            var config = {
                publicToken: '{$public_token}'
            };

            Akatus.init(formulario, config);
        });
    });

</script>
