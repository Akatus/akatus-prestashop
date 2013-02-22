{capture name=path}{l s="Shipping"}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s="Order summary" mod="akatus"}</h2>

{assign var="current_step" value="payment"}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s="Pagamento via Cartão de Crédito (Akatus)" mod="akatus"}</h3>
<script type="text/javascript" src="js/validacao.js"></script>
<link href="estilos.css" rel="stylesheet" type="text/css" />
<form action="{$this_path_ssl}validation.php" method="post" onsubmit="return pagar()" name="pagamento" id="pagamento">
  <p style="margin-top:0px;"> {l s='Valor total do pedido:' mod='akatus'}
    
   
     <span id="amount_{$currencies.0.id_currency}" class="price">R$ {$total}</span>  </p>
  <div id="bandeiras_akatus" >
    <h1>1) Selecione abaixo a bandeira do seu cartão:</h1>
    <UL id="cartoes_akatus">
      <LI>
        <LABEL><img id='cartao_visa'  src='imagens/bandeiras/cartao_visa.gif' onclick='define_cartao("cartao_visa")'><BR>
          <input name='bandeira_cartao' type='radio' onclick='define_cartao("cartao_visa")' value='cartao_visa' checked="checked" />
        </label>
      </LI>
      <LI>
        <label><img id='cartao_master'  src='imagens/bandeiras/cartao_master.gif' onclick='define_cartao("cartao_master")'> <BR>
          <input name='bandeira_cartao' type='radio' value='cartao_master' onclick='define_cartao("cartao_master")' />
        </label>
      </LI>
      <LI>
        <label><img id='cartao_elo'  src='imagens/bandeiras/cartao_elo.gif' onclick='define_cartao("cartao_elo")'> <BR>
          <input name='bandeira_cartao' type='radio' value='cartao_elo' onclick='define_cartao("cartao_elo")' />
        </label>
      </LI>
      <LI>
        <LABEL><img id='cartao_diners'  src='imagens/bandeiras/cartao_diners.gif' onclick='define_cartao("cartao_diners")'> <BR>
          <input name='bandeira_cartao' type='radio' value='cartao_diners' onclick='define_cartao("cartao_diners")' />
        </label>
      </LI>
      <LI>
        <label><img id='cartao_amex'  src='imagens/bandeiras/cartao_amex.gif' onclick='define_cartao("cartao_amex")'><BR>
          <input name='bandeira_cartao' type='radio' value='cartao_amex' onclick='define_cartao("cartao_amex")' />
        </label>
      </LI>
    </ul>
  </div>
  <div id='dados_titular_cartao'>
    <div id='form_titular_cartao'>
      <h1>2) Dados do Titular do Cartão:</h1>
      Observação: Os dados do cartão são enviados diretamente para a operadora a fim de autorizar a transação. Esses dados NÃO serão armazenados pela nossa loja.<BR>
      <BR>
      <table width="600" border="0" cellpadding="4" cellspacing="3">
        <tr>
          <td width="151"><strong>Nome do Titular </strong></td>
          <td width="11">&nbsp;</td>
          <td width="416"><input name="cartao_titular" id="cartao_titular" type="text" size="26" />
            &nbsp;(como gravado no cart&atilde;o) </td>
        </tr>
        <tr>
          <td><strong>N&uacute;mero do Cart&atilde;o </strong></td>
          <td>&nbsp;</td>
          <td><input name="cartao_numero" id="cartao_numero" type="text" size="26" />
            &nbsp;</td>
        </tr>
        <tr>
          <td><strong>Validade</strong></td>
          <td>&nbsp;</td>
          <td>
              <select name="cartao_mes" id="cartao_mes">
                <option value="-1">MÊS</option>
                <option value="01">01</option>
                <option value="02">02</option>
                <option value="03">03</option>
                <option value="04">04</option>
                <option value="05">05</option>
                <option value="06">06</option>
                <option value="07">07</option>
                <option value="08">08</option>
                <option value="09">09</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
              </select>
              /
              <select name="cartao_ano" id="cartao_ano">
                <option value="-1">ANO</option>
                
			  
			 {$anos_validade_cartao}
			
              </select>
            </td>
        </tr>
        <tr>
          <td><strong>C&oacute;digo de Seguran&ccedil;a </strong></td>
          <td>&nbsp;</td>
          <td><input name="cartao_codigo" id="cartao_codigo" type="text" size="10" maxlength="4"/>
            <a href="javascript:mostrar_popup();">O qu&ecirc; &eacute; c&oacute;digo de seguran&ccedil;a? </a></td>
        </tr>
        <tr>
          <td><strong>CPF do Titular </strong></td>
          <td>&nbsp;</td>
          <td><input name="cartao_cpf" id="cartao_cpf" type="text" size="30" maxlength="11"/></td>
        </tr>
        <tr>
          <td><strong>Telefone</strong></td>
          <td>&nbsp;</td>
          <td>(
            <input name="cartao_telefone_ddd" id="cartao_telefone_ddd" type="text" size="20" maxlength="2" style="width:20px" />
            )
            <input name="cartao_telefone" id="cartao_telefone" type="text" size="40" maxlength="9" style="width:80px" /></td>
        </tr>
      </table>
      
    </div>
    <BR>
    <BR>
    <div id="parcelas_akatus">
      <h1>3) Escolha a opção de Parcelamento do Pedido</h1>
      <div style="padding:20px; padding-left:10px;">
        <select name="parcelas" style="width:400px">
          {$parcelamento}
            
            
        </select>
      </div>
    </div>
  </div>
  <CENTER>
    <BR>
    <BR>
    <div id="div_botao_enviar">
    <input name="Bot�o" id="botao_enviar" type="button" value="Concluir Pagamento" class="button" onclick="pagar()" />
    </div>
    <div id="carregando"><center><img src="imagens/carregando.gif" /></center></div>
  </CENTER>
</form>


<div id="popup" class="popup">
<P><img src="imagens/fechar.jpg" width="20" height="20" align="absmiddle" /><a style="color:#F00; font-weight:bold" href="javascript:ocultar_popup()">Clique aqui para fechar</a></P>
<p><strong>O que é o Código de Segurança?</strong><br />
O código de segurança do cartão de crédito é uma seqüência numérica complementar ao número do cartão. Ele garante a veracidade dos dados de uma transação eletrônica, uma vez que a informação é verificada somente pelo portador do cartão e não consta em nenhum tipo de leitura magnética.</p>
<p><strong>Onde localizar o código de segurança?</strong></p>
<p> <img src="imagens/visa.gif" width="189" height="135" align="left" /><br />
  <strong>Visa / MasterCard / Diners</strong><br />
  O código de segurança dos cartões<br />
  Visa / MasterCard / Diners está localizado no verso do cartão e corresponde aos três últimos dígitos da faixa numérica.<br />
  </p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p><img src="imagens/amex.gif" width="189" height="124" align="left" /><strong>American Express </strong><br />
  O código de segurança está localizado na parte frontal do cartão American Express e corresponde aos quatro dígitos localizados do lado direito acima da faixa numérica do cartão.</p>
  
  <P><a style="color:#F00; font-weight:bold" href="javascript:ocultar_popup()">Clique aqui para fechar</a></P>
  
  </div>