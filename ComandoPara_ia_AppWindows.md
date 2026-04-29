Este fluxo e somente para acesso externo a API publica pelo app desktop Windows Forms.

Nao e o login do painel administrativo web.
Nao e para uso interno do painel.

Este app Windows Forms ja existe e ja possui outros formularios e endpoints funcionando.

Nao recriar o projeto do zero.
Nao mudar fluxos que ja estao funcionando.
Fazer apenas o ajuste do fluxo de login da API publica da empresa.

O backend foi ajustado assim:

- a empresa pode existir sem usar integracao com API
- por isso o campo `senha_integracao_api` e opcional no cadastro e na edicao da empresa
- essa senha e usada somente para integracao externa com a API publica
- no backend ela e salva com hash
- no login da API publica o app deve enviar `cnpj_cpf` e `senha`
- a API compara a `senha` enviada com o hash salvo em `senha_integracao_api`

O que precisa ser ajustado no app desktop existente:

1. Revisar o formulario de login atual

- o formulario deve pedir `cnpj_cpf`
- o formulario deve pedir `senha`
- essa senha e a senha de integracao da API publica da empresa

2. Ajustar a chamada de login

Endpoint correto da API publica:

- `POST /api/login`

Payload correto:

```json
{
	"cnpj_cpf": "19131243000197",
	"senha": "novaSenhaIntegracao456"
}
```

Exemplo em C#:

```csharp
var loginPayload = new
{
		cnpj_cpf = txtCnpjCpf.Text,
		senha = txtSenha.Text
};

var response = await httpClient.PostAsJsonAsync("/api/login", loginPayload);
var content = await response.Content.ReadAsStringAsync();
```

3. Tratar o retorno do login

Resposta de sucesso esperada:

```json
{
	"sucesso": true,
	"token": "TOKEN_DA_EMPRESA",
	"empresa": {
		"id": 1,
		"nome": "Mercado Com API",
		"cnpj_cpf": "19131243000197"
	}
}
```

Resposta de erro esperada:

```json
{
	"sucesso": false,
	"mensagem": "Token ou credenciais invalidos."
}
```

4. Guardar e reutilizar o token

Depois do login com sucesso, guardar o token retornado e usar nas outras rotas protegidas da API publica com:

```http
Authorization: Bearer TOKEN_DA_EMPRESA
```

Exemplo de configuracao do `HttpClient` depois do login:

```csharp
using System.Net.Http.Headers;

httpClient.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
```

5. Ajustar os textos da interface

Deixar claro no app:

- a senha usada no login e a senha de integracao da API publica
- essa senha e configurada no campo `senha_integracao_api` da empresa
- se a empresa nao tiver configurado `senha_integracao_api`, o login da API publica nao vai funcionar

6. Se existir formulario de cadastro ou edicao da empresa no app, ajustar apenas este ponto

- mostrar o campo `senha_integracao_api`
- indicar que ele e opcional
- explicar que ele so deve ser preenchido se a empresa for usar integracao externa com a API publica

O que a IA deve entregar:

- ajuste no formulario de login existente
- ajuste na chamada existente para usar `POST /api/login`
- envio correto de `cnpj_cpf` e `senha`
- tratamento correto do token retornado
- aplicacao do Bearer token nas rotas protegidas da API publica que ja existem no app
- ajuste dos textos da interface referentes a `senha_integracao_api`

Nao quero criacao de novo projeto.
Nao quero reestruturacao completa.
Quero somente a correcao do fluxo de login da API publica e dos textos ligados a essa integracao.
