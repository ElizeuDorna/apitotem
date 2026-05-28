
Siga este passo a passo para criar um app só para WhatsApp na Meta.

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