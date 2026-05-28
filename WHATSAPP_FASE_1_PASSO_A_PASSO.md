# WhatsApp Fase 1 - Passo a Passo

Este arquivo documenta como usar o modulo de WhatsApp Fase 1 dentro do projeto e o que precisa ser configurado na Meta.

## Objetivo da Fase 1

O modulo de WhatsApp foi criado separado da area atual de Instagram e Facebook.

## Modelo adotado pelo sistema

O modelo adotado nesta Fase 1 e:

- cada empresa usa o proprio numero dela;
- cada empresa conecta a propria conta dela na Meta;
- cada empresa envia mensagem com o proprio remetente;
- o dono da plataforma nao precisa ter um numero central para os clientes usarem.

Em outras palavras:

- a plataforma nao vai disparar com um numero unico do dono do sistema;
- a plataforma apenas fornece o painel para cada empresa salvar a integracao dela;
- a responsabilidade do numero remetente e da propria empresa que vai usar o modulo.

Ele permite:

- cadastrar a integracao WhatsApp da empresa ativa;
- cadastrar contatos com numero e opt-in;
- criar campanhas de WhatsApp;
- disparar campanha manualmente;
- agendar campanhas;
- receber webhook de status e de mensagens recebidas.

## Onde acessar no painel

No menu do sistema:

- Rede Social
- aba WhatsApp

O modulo atual de WhatsApp fica separado da aba de Instagram e Facebook.

## Como o numero funciona por empresa

Sim. Cada empresa vai cadastrar o proprio numero dela.

Importante:

- esse numero precisa existir e estar configurado na Meta para a propria empresa;
- nao precisa ser um numero do dono da plataforma;
- nao existe numero central obrigatorio do sistema para os clientes dispararem.

No modelo atual:

- cada empresa possui sua propria integracao WhatsApp;
- cada integracao guarda o numero da empresa conectado na Meta;
- o cadastro fica vinculado pela coluna `empresa_id`;
- hoje o sistema esta preparado para 1 numero WhatsApp por empresa.

Na pratica isso significa:

- Empresa A salva o numero dela;
- Empresa B salva o numero dela;
- Empresa A envia com o numero dela;
- Empresa B envia com o numero dela;
- os contatos e campanhas da Empresa A nao se misturam com os da Empresa B;
- os contatos e campanhas da Empresa B nao se misturam com os da Empresa A.

Observacao:

- se no futuro precisar usar varios numeros por empresa, sera necessario ampliar a modelagem, porque a Fase 1 foi feita com 1 integracao por empresa.

## Campos que a empresa vai preencher no painel

Na aba WhatsApp, a empresa vai preencher:

- Business Account ID
- Phone Number ID
- Numero exibido
- Access Token
- Expiracao do token, se quiser registrar

Esses dados ficam salvos para a empresa ativa.

## Estrutura criada no projeto

### Tabelas

- `whatsapp_integrations`
- `whatsapp_contacts`
- `whatsapp_campaigns`
- `whatsapp_campaign_recipients`

### Responsabilidades

- `whatsapp_integrations`: dados da conta WhatsApp da empresa
- `whatsapp_contacts`: contatos da empresa
- `whatsapp_campaigns`: campanhas criadas para a empresa
- `whatsapp_campaign_recipients`: status por destinatario

## Passo a passo para usar no projeto

### 1. Aplicar as migrations

Se estiver usando Sail localmente:

```bash
./vendor/bin/sail artisan migrate
```

Se estiver no servidor sem Docker, use o PHP correto do servidor, por exemplo:

```bash
/www/server/php/84/bin/php artisan migrate --force
```

### 2. Configurar o `.env`

Adicionar pelo menos:

```env
WHATSAPP_GRAPH_VERSION=v25.0
WHATSAPP_WEBHOOK_VERIFY_TOKEN=coloque_um_token_forte_aqui
```

Depois limpar e reconstruir cache de configuracao:

```bash
/www/server/php/84/bin/php artisan optimize:clear
/www/server/php/84/bin/php artisan optimize
```

No Sail:

```bash
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan optimize
```

### 3. Abrir o painel e salvar a integracao da empresa

No sistema:

- selecione a empresa ativa, se necessario;
- entre em Rede Social;
- abra a aba WhatsApp;
- preencha os dados da Meta;
- clique em salvar integracao.

### 4. Cadastrar os contatos

Cada contato deve ter:

- nome;
- numero WhatsApp;
- opt-in ativo, se autorizado;
- origem do opt-in, se quiser controlar;
- status ativo.

Sem opt-in o sistema nao deve usar o contato para disparo regular.

### 5. Criar a campanha

Na campanha, informar:

- nome da campanha;
- tipo da mensagem;
- mensagem livre ou nome do template;
- idioma do template, quando for template;
- imagem opcional;
- contatos selecionados;
- agendamento, se quiser.

### 6. Entender a regra de envio

Mensagem livre:

- so deve ser usada quando o contato falou com a empresa nas ultimas 24 horas.

Template aprovado:

- deve ser usado fora da janela de 24 horas;
- exige template aprovado na Meta.

### 7. Disparar manualmente ou por agendamento

Manual:

- usar o botao de disparo da campanha.

Agendado:

- preencher a data;
- garantir que o scheduler esteja rodando.

## Scheduler

O comando criado foi:

```bash
php artisan whatsapp:dispatch-scheduled
```

Ele ja foi registrado no scheduler para rodar a cada minuto.

Em servidor aaPanel ou similar, garantir cron de 1 em 1 minuto com:

```bash
/www/server/php/84/bin/php /caminho/do/projeto/artisan schedule:run
```

## Webhook da Meta

O projeto espera o webhook em:

- verificacao: `/webhooks/whatsapp`
- recebimento: `/webhooks/whatsapp`

URL completa exemplo:

```text
https://seu-dominio.com/webhooks/whatsapp
```

O token de verificacao precisa ser igual ao valor de:

```env
WHATSAPP_WEBHOOK_VERIFY_TOKEN
```

## O que fazer na Meta para liberar

Importante antes de comecar:

- quem precisa ter numero configurado na Meta e a empresa que vai disparar;
- o dono da plataforma nao precisa cadastrar um numero proprio so para os clientes usarem;
- cada empresa cliente deve ter o proprio numero configurado na Meta, se quiser disparar com o proprio remetente.

### 1. Criar ou usar um app no Meta for Developers

- acessar Meta for Developers;
- criar um app ou reutilizar um app ja existente;
- adicionar o produto WhatsApp Business Platform.

### 2. Criar ou vincular a WABA

- criar ou vincular a conta WhatsApp Business Account;
- vincular o numero da empresa que vai enviar as mensagens.

### 3. Pegar os dados necessarios

Voce vai precisar pegar na Meta:

- Business Account ID;
- Phone Number ID;
- numero exibido;
- Access Token.

### 4. Configurar o webhook

Na Meta:

- configurar a callback URL para `https://seu-dominio.com/webhooks/whatsapp`;
- informar o mesmo verify token do `.env`;
- validar o webhook;
- assinar pelo menos o evento `messages`.

### 5. Criar templates, se for disparo fora da janela de 24 horas

No WhatsApp Manager:

- criar templates;
- enviar para aprovacao;
- usar no painel exatamente o nome aprovado;
- usar tambem o idioma aprovado, como `pt_BR`.

### 6. Garantir opt-in

Antes de enviar mensagens:

- a empresa precisa ter autorizacao do contato;
- o opt-in precisa estar claro e documentado.

## Como a empresa usa o proprio numero

Exemplo de fluxo:

### Empresa A

- conecta o numero A na Meta;
- salva o Phone Number ID A;
- cria contatos e campanhas dela;
- envia usando o numero A.

### Empresa B

- conecta o numero B na Meta;
- salva o Phone Number ID B;
- cria contatos e campanhas dela;
- envia usando o numero B.

As duas operam separadas dentro do mesmo sistema.

## O dono da plataforma precisa ter numero proprio na Meta?

Nao, neste modelo adotado nao.

O dono da plataforma so precisaria ter um numero proprio na Meta se quisesse:

- testar com a propria conta;
- operar uma estrutura centralizada;
- usar um numero proprio como remetente.

Mas este nao e o modelo escolhido aqui.

O modelo escolhido foi:

- cada empresa cliente conecta o proprio numero dela;
- cada empresa cliente dispara com o proprio numero dela.

## Checklist rapido para a empresa cliente configurar na Meta

Antes de usar o modulo no painel, cada empresa cliente precisa resolver este checklist:

### Cadastro e conta

- ter acesso ao Meta Business da propria empresa;
- ter ou criar a propria WABA;
- ter um numero proprio da empresa para usar no WhatsApp Business Platform.

### Numero de envio

- cadastrar o numero da propria empresa na Meta;
- validar esse numero na Meta, quando o fluxo pedir;
- confirmar qual numero vai aparecer como remetente para os clientes.

### Dados que a empresa vai precisar trazer para o painel

- `Business Account ID`;
- `Phone Number ID`;
- `Display Phone Number`;
- `Access Token`.

### Webhook

- apontar o webhook da Meta para a URL do seu sistema;
- usar o mesmo `verify token` configurado no projeto;
- assinar pelo menos o evento `messages`.

### Templates

- se quiser enviar fora da janela de 24 horas, criar templates no WhatsApp Manager;
- aguardar aprovacao dos templates;
- usar no painel exatamente o nome e idioma aprovados.

### Politica de uso

- ter autorizacao do cliente para receber mensagem;
- manter o opt-in registrado;
- evitar disparo para contatos sem permissao.

### Ultima conferência no painel

Depois da configuracao na Meta, a empresa deve entrar no sistema e preencher na aba WhatsApp:

- Business Account ID;
- Phone Number ID;
- Numero exibido;
- Access Token.

## Limitacao atual da Fase 1

Hoje a Fase 1 foi feita para:

- 1 integracao WhatsApp por empresa;
- contatos da propria empresa;
- campanhas da propria empresa;
- envio individual para contatos selecionados.

Se depois for necessario:

- varios numeros por empresa;
- grupos de WhatsApp;
- catalogo integrado;
- sincronizacao automatica de templates aprovados;

isso entra como evolucao da Fase 2 em diante.

## Arquivos principais do modulo

- `app/Livewire/Admin/WhatsAppCampaignsPanel.php`
- `resources/views/livewire/admin/whatsapp-campaigns-panel.blade.php`
- `app/Services/WhatsAppService.php`
- `app/Services/WhatsAppGraphService.php`
- `app/Http/Controllers/Admin/WhatsAppWebhookController.php`
- `app/Console/Commands/DispatchScheduledWhatsAppCampaigns.php`
- `database/migrations/2026_05_28_120000_create_whatsapp_module_tables.php`

## Resposta curta para a duvida do numero

Sim.

Cada empresa vai ter campo para cadastrar os dados do proprio numero dentro da aba WhatsApp do modulo Rede Social.

No modelo atual, cada empresa usa o seu proprio numero e a sua propria integracao.