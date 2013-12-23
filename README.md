<p align="left"> 
	<img src="https://site.akatus.com/wp-content/uploads/2012/12/logo.gif" alt="Akatus" title="Akatus"/>
</p>

# Módulo Akatus para PrestaShop (1.4.8.2 ou superior)

# Instalação

__Atenção:__ pode ser necessário desabilitar o cache do sistema para que as alterações entrem em vigor. Para isso, vá até *“Preferences >> Performance”*. Marque a opção “Force Compile” e desmarque a opção “Cache”. Clique em “Salvar”. Lembre-se de voltar essas opções da forma que estavam quando for colocar a loja em produção.

* Abra o arquivo compactado que contém os módulos e extraia os diretórios "modules" e "override” para a raiz da sua loja, sobrescrevendo os arquivos existentes.

* Efetue login na Administração. Clique em “Modules” e role a página para baixo até encontrar a opção “Payments & Gateways”.

* Localize os 3 módulo instalados e clique em INSTALL.

* Clique no botão “Configurar” e preencha os dados solicitados na tela.

## Configuração

* __E-mail da sua conta Akatus__ - E-mail de cadastro da conta Akatus

* __Token NIP__ - Código gerado no painel da conta Akatus (menu *Integração > Chaves de Segurança*)

* __API Key__ - Código gerado no painel da conta Akatus (menu *Integração > Chaves de Segurança*)

* __Parcelamento sem juros até__ – Informe quantas parcelas você deseja assumir dos juros. Para que essa opção funcione corretamente, é necessário também alterá-la no seu painel da Akatus em *“Minha Conta >> Meios de Pagamento”*. O valor padrão é 1. __*(apenas para cartão de crédito)*__

* __Número máximo de parcelas__ - Informe em até quantas parcelas o usuário poderá dividir suas compras. O padrão é 12 parcelas, e o valor mínimo da parcela será sempre 5 reais. __*(apenas para cartão de crédito)*__

* __Mensagem para pagamentos__ – Informe os textos que devem ser exibidos para o usuário caso seu pagamento seja “Aprovado”, “Cancelado” ou “Em Análise”. *(recomendamos deixar as mensagens padrões)*

* __Mensagem para a tela de impressão do boleto__ - Informe o texto que deve ser exibido no momento da emissão do boleto bancário. __*(somente boleto)*__

## Notificação Instantânea de Pagamento (NIP)

Para receber as notificações de mudanças no status das transações é necessário:

1. Acessar o menu *Integração >> Notificações*, dentro da sua conta Akatus.
2. Habilitar as notificações e inserir a URL no padrão: http://www.sualoja.com.br/modules/akatus/includes/retorno.php

Clique em Atualizar.

