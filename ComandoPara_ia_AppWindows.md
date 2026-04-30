# Endpoint Produtos

Este arquivo resume o comportamento real do endpoint de produtos da API, com base no controller atual.

## Autenticacao

Todos os endpoints abaixo exigem autenticacao via Bearer Token da empresa.

Header obrigatorio:

```http
Authorization: Bearer SEU_TOKEN_DA_EMPRESA
Accept: application/json
Content-Type: application/json
```

Rotas protegidas pelo middleware `identify.company`:

- `GET /api/produtos`
- `POST /api/produtos`
- `GET /api/produtos/{CODIGO}`
- `PUT /api/produtos/{CODIGO}`
- `DELETE /api/produtos/{CODIGO}`

## Estrutura de retorno do produto

Quando um produto e retornado com sucesso, o formato atual da API e este:

```json
{
	"id": 100,
	"codigo": "78901",
	"nome": "Coca-Cola 2L",
	"preco": 9.99,
	"oferta": 8.99,
	"imagem": "https://exemplo.com/imagens/coca-2l.jpg",
	"grupo": {
		"id": 10,
		"nome": "Refrigerantes"
	},
	"departamento": {
		"id": 1,
		"nome": "Bebidas"
	}
}
```

Observacoes importantes:

- O campo retornado e `codigo`, mas no envio o campo aceito e `CODIGO`.
- O campo retornado e `nome`, mas no envio o campo aceito e `NOME`.
- O campo retornado e `preco`, mas no envio o campo aceito e `PRECO`.
- O campo retornado e `oferta`, mas no envio o campo aceito e `OFERTA`.
- O campo retornado e `imagem`, mas no envio o campo aceito e `IMG`.

## 1. Listar produtos

### Requisicao

```http
GET /api/produtos
```

### Comportamento atual

- Lista apenas os produtos da empresa identificada pelo token.
- Ordena por `created_at` decrescente.
- Carrega `departamento` e `grupo` no retorno.
- Nao usa filtros por query string no controller atual.

### Resposta de sucesso

Status: `200 OK`

```json
{
	"success": true,
	"data": {
		"produtos": [
			{
				"id": 100,
				"codigo": "78901",
				"nome": "Coca-Cola 2L",
				"preco": 9.99,
				"oferta": 8.99,
				"imagem": "https://exemplo.com/imagens/coca-2l.jpg",
				"grupo": {
					"id": 10,
					"nome": "Refrigerantes"
				},
				"departamento": {
					"id": 1,
					"nome": "Bebidas"
				}
			}
		]
	},
	"meta": {
		"total_produtos": 1
	}
}
```

### Exemplo curl

```bash
curl -X GET "http://localhost:8000/api/produtos" \
	-H "Authorization: Bearer SEU_TOKEN_DA_EMPRESA" \
	-H "Accept: application/json"
```

### Possiveis erros

- `401 Unauthorized` quando o token nao for enviado.
- `401 Unauthorized` quando o token for invalido ou a empresa estiver inativa.

## 2. Criar produto ou atualizar pelo CODIGO

### Requisicao

```http
POST /api/produtos
```

### Comportamento atual

Este endpoint funciona como upsert por `CODIGO` dentro da empresa autenticada:

- se o `CODIGO` nao existir para a empresa, cria um novo produto
- se o `CODIGO` ja existir para a empresa, atualiza o produto existente

### Body JSON

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

### Campos aceitos

- `CODIGO`: opcional, string, maximo de 14 caracteres
- `NOME`: obrigatorio, string, maximo de 255 caracteres
- `PRECO`: obrigatorio, numerico, minimo 0
- `OFERTA`: opcional, numerico, minimo 0
- `IMG`: opcional, string, maximo de 500 caracteres
- `departamento_id`: obrigatorio, inteiro, precisa existir
- `grupo_id`: obrigatorio, inteiro, precisa existir

### Validacoes de negocio

Mesmo que `departamento_id` e `grupo_id` existam no banco, a API ainda valida se:

- o departamento pertence a empresa autenticada
- o grupo pertence a empresa autenticada
- o grupo pertence ao departamento informado

Se essa relacao estiver errada, a API retorna erro `422`.

### Regra do campo IMG

`IMG` so aceita:

- URL valida
- caminho interno iniciando com `/storage/`
- caminho interno iniciando com `/storage-images/`

### Regra do campo OFERTA

Se `OFERTA` nao for enviada, vier vazia ou vier nula no create, o valor salvo passa a ser `0`.

### Resposta quando cria

Status: `201 Created`

```json
{
	"sucesso": true,
	"mensagem": "Produto cadastrado com sucesso",
	"dados": {
		"id": 100,
		"codigo": "78901",
		"nome": "Coca-Cola 2L",
		"preco": 9.99,
		"oferta": 8.99,
		"imagem": "https://exemplo.com/imagens/coca-2l.jpg",
		"grupo": {
			"id": 10,
			"nome": "Refrigerantes"
		},
		"departamento": {
			"id": 1,
			"nome": "Bebidas"
		}
	}
}
```

### Resposta quando atualiza pelo mesmo CODIGO

Status: `200 OK`

```json
{
	"sucesso": true,
	"mensagem": "Produto atualizado com sucesso",
	"dados": {
		"id": 100,
		"codigo": "78901",
		"nome": "Coca-Cola 2L Zero",
		"preco": 10.49,
		"oferta": 9.49,
		"imagem": "https://exemplo.com/imagens/coca-2l-zero.jpg",
		"grupo": {
			"id": 10,
			"nome": "Refrigerantes"
		},
		"departamento": {
			"id": 1,
			"nome": "Bebidas"
		}
	}
}
```

### Exemplos curl

Criar ou atualizar:

```bash
curl -X POST "http://localhost:8000/api/produtos" \
	-H "Authorization: Bearer SEU_TOKEN_DA_EMPRESA" \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{
		"CODIGO": "78901",
		"NOME": "Coca-Cola 2L",
		"PRECO": 9.99,
		"OFERTA": 8.99,
		"IMG": "https://exemplo.com/imagens/coca-2l.jpg",
		"departamento_id": 1,
		"grupo_id": 10
	}'
```

### Possiveis erros

Token ausente ou invalido:

```json
{
	"sucesso": false,
	"mensagem": "Token nao informado."
}
```

Erro de validacao de campos:

```json
{
	"message": "The given data was invalid.",
	"errors": {
		"NOME": [
			"O campo NOME e obrigatorio."
		]
	}
}
```

Relacionamento invalido entre empresa, grupo e departamento:

```json
{
	"sucesso": false,
	"mensagem": "Relacionamento invalido: grupo/departamento fora da empresa autenticada."
}
```

Imagem invalida:

```json
{
	"message": "The given data was invalid.",
	"errors": {
		"IMG": [
			"O campo IMG deve ser uma URL valida ou um caminho interno iniciando com /storage/ ou /storage-images/."
		]
	}
}
```

## 3. Buscar um produto pelo CODIGO

### Requisicao

```http
GET /api/produtos/{CODIGO}
```

Exemplo:

```http
GET /api/produtos/78901
```

### Comportamento atual

- O parametro de rota representa o `CODIGO` do produto.
- A busca considera apenas a empresa autenticada.
- O retorno inclui `grupo` e `departamento`.

### Resposta de sucesso

Status: `200 OK`

```json
{
	"sucesso": true,
	"dados": {
		"id": 100,
		"codigo": "78901",
		"nome": "Coca-Cola 2L",
		"preco": 9.99,
		"oferta": 8.99,
		"imagem": "https://exemplo.com/imagens/coca-2l.jpg",
		"grupo": {
			"id": 10,
			"nome": "Refrigerantes"
		},
		"departamento": {
			"id": 1,
			"nome": "Bebidas"
		}
	}
}
```

### Resposta quando nao encontra

Status: `404 Not Found`

```json
{
	"sucesso": false,
	"mensagem": "Produto nao encontrado para a empresa autenticada."
}
```

### Exemplo curl

```bash
curl -X GET "http://localhost:8000/api/produtos/78901" \
	-H "Authorization: Bearer SEU_TOKEN_DA_EMPRESA" \
	-H "Accept: application/json"
```

## 4. Atualizar um produto pelo CODIGO

### Requisicao

```http
PUT /api/produtos/{CODIGO}
```

### Comportamento atual

- Busca o produto pelo `CODIGO` dentro da empresa autenticada.
- Permite atualizacao parcial.
- Se `CODIGO` for enviado, ele precisa continuar unico dentro da empresa.
- Se `departamento_id` ou `grupo_id` forem enviados, a relacao entre eles tambem e validada.

### Body JSON de exemplo

```json
{
	"NOME": "Coca-Cola 2L Zero",
	"PRECO": 10.49,
	"OFERTA": 9.49,
	"IMG": "https://exemplo.com/imagens/coca-2l-zero.jpg",
	"departamento_id": 1,
	"grupo_id": 10
}
```

### Campos aceitos

- `CODIGO`: opcional, string, maximo 14, unico por empresa
- `NOME`: opcional, se enviado precisa ser string valida
- `PRECO`: opcional, se enviado precisa ser numerico e maior ou igual a 0
- `OFERTA`: opcional, numerico, minimo 0
- `IMG`: opcional, URL valida ou caminho interno permitido
- `departamento_id`: opcional, inteiro, precisa existir
- `grupo_id`: opcional, inteiro, precisa existir

### Regra do campo OFERTA no update

Se `OFERTA` for enviado como `null` ou string vazia, a API converte para `0`.

### Resposta de sucesso

Status: `200 OK`

```json
{
	"sucesso": true,
	"mensagem": "Produto atualizado com sucesso",
	"dados": {
		"id": 100,
		"codigo": "78901",
		"nome": "Coca-Cola 2L Zero",
		"preco": 10.49,
		"oferta": 9.49,
		"imagem": "https://exemplo.com/imagens/coca-2l-zero.jpg",
		"grupo": {
			"id": 10,
			"nome": "Refrigerantes"
		},
		"departamento": {
			"id": 1,
			"nome": "Bebidas"
		}
	}
}
```

### Possiveis erros

Codigo duplicado dentro da mesma empresa:

```json
{
	"message": "The given data was invalid.",
	"errors": {
		"CODIGO": [
			"Este codigo ja existe para esta empresa."
		]
	}
}
```

Produto nao encontrado:

```json
{
	"sucesso": false,
	"mensagem": "Produto nao encontrado para a empresa autenticada."
}
```

### Exemplo curl

```bash
curl -X PUT "http://localhost:8000/api/produtos/78901" \
	-H "Authorization: Bearer SEU_TOKEN_DA_EMPRESA" \
	-H "Accept: application/json" \
	-H "Content-Type: application/json" \
	-d '{
		"NOME": "Coca-Cola 2L Zero",
		"PRECO": 10.49,
		"OFERTA": 9.49,
		"IMG": "https://exemplo.com/imagens/coca-2l-zero.jpg",
		"departamento_id": 1,
		"grupo_id": 10
	}'
```

## 5. Deletar um produto pelo CODIGO

### Requisicao

```http
DELETE /api/produtos/{CODIGO}
```

Exemplo:

```http
DELETE /api/produtos/78901
```

### Comportamento atual

- Busca pelo `CODIGO` do produto dentro da empresa autenticada.
- Se encontrar, deleta o registro e devolve o codigo removido.

### Resposta de sucesso

Status: `200 OK`

```json
{
	"sucesso": true,
	"mensagem": "Produto deletado com sucesso",
	"CODIGO": "78901"
}
```

### Resposta quando nao encontra

Status: `404 Not Found`

```json
{
	"sucesso": false,
	"mensagem": "Produto nao encontrado para a empresa autenticada."
}
```

### Exemplo curl

```bash
curl -X DELETE "http://localhost:8000/api/produtos/78901" \
	-H "Authorization: Bearer SEU_TOKEN_DA_EMPRESA" \
	-H "Accept: application/json"
```

## Resumo rapido

- O endpoint usa token da empresa e nao token de device.
- O `POST /api/produtos` nao e apenas create: ele cria ou atualiza pelo `CODIGO`.
- O `GET /api/produtos/{CODIGO}` usa `CODIGO` e nao `id`.
- O `PUT /api/produtos/{CODIGO}` aceita update parcial.
- O `DELETE /api/produtos/{CODIGO}` responde com o `CODIGO` deletado.
- A documentacao antiga do projeto menciona filtros em listagem, mas o controller atual nao implementa esses filtros.