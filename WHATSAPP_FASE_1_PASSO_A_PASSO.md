
Siga este passo a passo para criar um app só para WhatsApp na Meta.

Fluxo validado em teste

O fluxo abaixo foi validado com sucesso no Laravel usando a conta de teste da Meta.

- Webhook validado com a URL publica da plataforma em /webhooks/whatsapp
- Verify token igual ao valor de WHATSAPP_WEBHOOK_VERIFY_TOKEN no servidor
- Integracao salva no painel WhatsApp do Laravel com:
	- WhatsApp Business Account ID
	- Phone Number ID
	- Access Token
	- numero exibido
- Envio funcionando pelo Laravel quando a campanha foi criada como template
- Template de teste que funcionou: hello_world
- Idioma que funcionou: en_US

Importante sobre o teste

- Mensagem livre nao funciona para primeiro contato fora da janela de 24 horas
- Se tentar enviar freeform para um contato sem interacao recente, o sistema bloqueia corretamente
- No ambiente de teste da Meta, o caminho mais seguro para validar o modulo e usar template hello_world com idioma en_US
- O numero de teste da Meta serve para validar a integracao, mas nao e o numero final de producao da empresa

Criar App

Acesse https://developers.facebook.com/
Entre com a conta Meta que vai administrar o app
Clique em Meus Apps
Clique em Criar App
Se a Meta pedir tipo de app, escolha a opção mais próxima de Business
Dê um nome para o app, por exemplo: API Totem WhatsApp
Informe o e-mail de contato
Conclua a criação
Adicionar WhatsApp

Dentro do app criado, procure Adicionar produto
Escolha WhatsApp
Clique em Configurar
Configurar Webhook

Dentro do produto WhatsApp, abra a área de Configuration ou Webhooks
No campo Callback URL, coloque:
https://SEU-DOMINIO/webhooks/whatsapp
No campo Verify Token, coloque:
o mesmo valor configurado em WHATSAPP_WEBHOOK_VERIFY_TOKEN no .env
Clique para verificar/salvar
Vincular Conta e Número

Ainda no produto WhatsApp, vincule ou crie a WhatsApp Business Account
Adicione o número que será usado
Faça a verificação desse número, se a Meta solicitar
Confirme que o número ficou associado à conta certa da empresa
Pegar os Dados para o Sistema

Copie o WhatsApp Business Account ID
Copie o Phone Number ID
Gere ou copie o Access Token
Se houver, confirme também o número exibido
Esses dados depois vão no painel do seu sistema, na tela de WhatsApp da empresa.

Configurar no Seu Sistema

No seu servidor, deixe no .env:

WHATSAPP_GRAPH_VERSION=v25.0
WHATSAPP_WEBHOOK_VERIFY_TOKEN=defina_um_token_forte

Depois limpe cache de configuracao.

Se estiver usando Docker/Sail:

./vendor/bin/sail artisan optimize:clear

Preenchimento no Laravel

No painel WhatsApp da empresa ativa no Laravel, preencha:

- WhatsApp Business Account ID da empresa
- Phone Number ID do numero da empresa
- Numero exibido no WhatsApp da empresa
- Access Token da Meta

Para o primeiro teste validado no sistema:

- Tipo da campanha: Template aprovado
- Nome do template: hello_world
- Idioma do template: en_US
- Destinatario: numero liberado no ambiente de teste da Meta

Se aparecer o erro "Contato fora da janela de 24 horas", isso significa que voce tentou enviar mensagem livre para um contato sem interacao recebida recente. Nesse caso, use template aprovado.

Proximo passo para producao

Depois de validar o fluxo com o numero de teste da Meta, avance para producao nesta ordem:

1. No WhatsApp Manager, adicione e verifique o numero real da empresa.
2. Garanta que a conta tenha metodo de pagamento configurado.
3. Gere ou obtenha o token que sera usado pela empresa real.
4. No Laravel, troque o Phone Number ID, o Business Account ID e o Access Token de teste pelos dados reais da empresa.
5. Crie ou aprove templates reais da empresa na Meta.
6. Publique o app quando precisar receber dados reais em producao.
7. Teste recebimento de webhook com mensagem real para validar atualizacao de status e abertura da janela de 24 horas.

Modelo multiempresa

- O sistema atual foi preparado para guardar a integracao por empresa.
- Cada empresa pode usar seu proprio numero, seu proprio Phone Number ID e seu proprio Access Token.
- O webhook pode continuar unico na plataforma, desde que a Meta envie eventos para a mesma URL publica.