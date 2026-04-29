# INSTRUCAO PARA A IA DO APP WINDOWS

Voce deve considerar o comportamento atual da API exatamente assim:

## 1. Primeiro faz login para pegar o token

Antes de acessar produtos, o app deve fazer login neste endpoint:

```http
POST /api/login
Content-Type: application/json
```

Body principal:

```json
{
	"cnpj_cpf": "12345678000199",
	"senha": "minhaSenhaApi123"
}
```

Resposta esperada:

```json
{
	"sucesso": true,
	"token": "TOKEN_DA_EMPRESA",
	"empresa": {
		"id": 1,
		"nome": "Empresa Exemplo",
		"cnpj_cpf": "12345678000199"
	}
}
```

Depois do login, o app deve salvar o valor retornado em `token`.

## 2. Todas as rotas de produtos usam Bearer token

O app deve enviar este header:

```http
Authorization: Bearer TOKEN_DA_EMPRESA
Accept: application/json
```

## 3. Comportamento atual do endpoint de produtos

O endpoint abaixo funciona como `criar ou atualizar` por `CODIGO`, sempre dentro da empresa autenticada pelo token:

```http
POST /api/produtos
Authorization: Bearer TOKEN_DA_EMPRESA
Content-Type: application/json
Accept: application/json
```

Regra real da API:

- se o `CODIGO` nao existir para aquela empresa, a API cria o produto
- se o `CODIGO` ja existir para aquela empresa, a API atualiza o produto existente
- a busca e feita por `empresa_id + CODIGO`
- uma empresa nao altera produto de outra empresa, mesmo se o `CODIGO` for igual

## 4. Exemplo de envio para criar ou atualizar

```json
{
	"CODIGO": "78901",
	"NOME": "Coca-Cola 2L",
	"PRECO": 9.99,
	"OFERTA": 8.99,
	"IMG": "https://exemplo.com/imagens/coca-2l.jpg",
	"departamento_id": 1,
	"grupo_id": 10
}
```

Se enviar esse body e o produto `78901` ainda nao existir para a empresa autenticada, a API cria.

Se enviar esse mesmo `CODIGO` de novo para a mesma empresa, a API atualiza os dados.

## 5. Endpoints reais de produtos

Listar produtos:

```http
GET /api/produtos
Authorization: Bearer TOKEN_DA_EMPRESA
```

Buscar um produto pelo codigo:

```http
GET /api/produtos/{CODIGO}
Authorization: Bearer TOKEN_DA_EMPRESA
```

Criar ou atualizar pelo CODIGO:

```http
POST /api/produtos
Authorization: Bearer TOKEN_DA_EMPRESA
Content-Type: application/json
```

Atualizar explicitamente pela rota:

```http
PUT /api/produtos/{CODIGO}
Authorization: Bearer TOKEN_DA_EMPRESA
Content-Type: application/json
```

Excluir:

```http
DELETE /api/produtos/{CODIGO}
Authorization: Bearer TOKEN_DA_EMPRESA
```

## 6. O que a IA do app deve fazer

No cadastro e na edicao de produto do desktop, voce pode usar o mesmo endpoint `POST /api/produtos` com o `CODIGO` do produto.

Resumo para a IA:

1. primeiro faz `POST /api/login`
2. pega o `token`
3. salva o token
4. envia `Authorization: Bearer TOKEN_DA_EMPRESA`
5. para cadastrar ou atualizar produto, usa `POST /api/produtos`
6. se o `CODIGO` existir na empresa, a API atualiza
7. se o `CODIGO` nao existir na empresa, a API cria
