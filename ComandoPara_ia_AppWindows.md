# INSTRUCAO PARA A IA DO APP WINDOWS

Voce deve considerar o comportamento atual da API exatamente assim:

## 1. Primeiro faz login para pegar o token

Antes de acessar produtos ou enviar imagem, o app deve fazer login neste endpoint:

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

## 2. Todas as rotas protegidas usam Bearer token

O app deve enviar este header:

```http
Authorization: Bearer TOKEN_DA_EMPRESA
Accept: application/json
```

## 3. Produtos: criar ou atualizar pelo mesmo endpoint

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

Exemplo:

```json
{
	"CODIGO": "78901",
	"NOME": "Coca-Cola 2L",
	"PRECO": 9.99,
	"OFERTA": 8.99,
	"IMG": "/storage-images/empresas/12345678000199/galeria/12345678901234.png",
	"departamento_id": 1,
	"grupo_id": 10
}
```

## 4. Upload de imagem da galeria via desktop

Agora a API possui endpoint proprio para upload de imagem da empresa:

```http
POST /api/galeria-imagem/upload
Authorization: Bearer TOKEN_DA_EMPRESA
Content-Type: multipart/form-data
Accept: application/json
```

Campos do envio:

- `image`: arquivo da imagem
- `name`: nome opcional para a galeria

Exemplo de resposta:

```json
{
	"sucesso": true,
	"criado": true,
	"mensagem": "Imagem enviada com sucesso.",
	"dados": {
		"id": 10,
		"code": "12345678901234",
		"empresa_id": 1,
		"name": "Banner API",
		"source_type": "upload",
		"url": "/storage-images/empresas/12345678000199/galeria/12345678901234.png"
	}
}
```

Se a mesma imagem ja tiver sido enviada antes para a mesma empresa, a API pode reutilizar o item existente e retornar `criado: false`.

Use a `url` retornada no campo `IMG` do produto.

Essa mesma imagem fica registrada na galeria da empresa no portal web, evitando novo upload manual.

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

## 6. Resumo para a IA

1. primeiro faz `POST /api/login`
2. pega o `token`
3. salva o token
4. envia `Authorization: Bearer TOKEN_DA_EMPRESA`
5. para enviar imagem, usa `POST /api/galeria-imagem/upload`
6. pega a `dados.url` retornada pela API
7. usa essa URL no campo `IMG` do produto
8. para cadastrar ou atualizar produto, usa `POST /api/produtos`
9. se o `CODIGO` existir na empresa, a API atualiza
10. se o `CODIGO` nao existir na empresa, a API cria
