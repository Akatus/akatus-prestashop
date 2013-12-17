{capture name=path}{l s="Shipping"}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s="Order summary" mod="akatusd"}</h2>

{assign var="current_step" value="payment"}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s="Pagamento via Cartão de Débito / Transferência Eletrônica - Akatus" mod="akatusd"}</h3>
<p>Você pode efetuar o pagamento via Transferência Eletrônica / Cartão de Débito através dos bancos Bradesco, Itaú e Banco do Brasil. Para isso, tenha em mãos o Cartão de Débito da sua conta bancária e seu token. Depois, clique abaixo no botão referente ao seu banco. Ao completar a transferência, seu pagamento será automaticamente confirmado e o envio será processado.</p>
<script type="text/javascript" src="js/validacao.js"></script>
<link href="estilos.css" rel="stylesheet" type="text/css" />
<form action="{$this_path_ssl}validation.php" method="post" onsubmit="return concluir_compra()" name="pagamento" id="pagamento">
 
    <div id="div_botao_enviar">
    <CENTER>
      <input name="meio_pagamento" type="hidden" id="meio_pagamento" value="tef_itau" />
      <input name="transferencia_bradesco" type="image" src="imagens/tef_bradesco.png" onclick="javascript:document.getElementById('meio_pagamento').value='tef_bradesco';" />&nbsp;&nbsp;&nbsp;
      
      <input name="transferencia_itau" type="image" src="imagens/tef_itau.png"  onclick="javascript:document.getElementById('meio_pagamento').value='tef_itau';" />
      
      &nbsp;&nbsp;&nbsp;
      
      <input name="transferencia_bb" type="image" src="imagens/tef_bb.png"  onclick="javascript:document.getElementById('meio_pagamento').value='tef_bb';" />
      
      
      
      </CENTER>
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
