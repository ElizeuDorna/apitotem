# API Totem - Documentação de Endpoints

**Base URL:** `http://192.168.1.50:5000/api/produtos`

## Endpoints Disponíveis

### 1. Listar Todos os Produtos
**GET** `/api/produtos`

> Você pode filtrar pelo departamento ou grupo usando parâmetros de query:
> `/api/produtos?departamento_id=1&grupo_id=2`


**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": [
    {
      "id": 1,
      "CODIGO": "12345678901234",
      "NOME": "PRODUTO DE TESTE",
      "PRECO": 99.99,
      "OFERTA": 0.00,
      "IMG": "https://dominio.com/imagem.jpg",
      "created_at": "2026-02-27T10:30:00.000000Z",
      "updated_at": "2026-02-27T10:30:00.000000Z"
    }
  ],
  "total": 1
}
```

---

### 2. Cadastrar Novo Produto
**POST** `/api/produtos`

**Body (JSON):**
```json
{
  "CODIGO": "12345678901234",
  "NOME": "PRODUTO DE TESTE",
  "PRECO": 99.99,
  "OFERTA": 0.00,
  "IMG": "https://dominio.com/imagem.jpg"
}
```

**Resposta de sucesso (201):**
```json
{
  "sucesso": true,
  "mensagem": "Produto cadastrado com sucesso",
  "CODIGO": "12345678901234",
  "dados": {
    "id": 1,
    "CODIGO": "12345678901234",
    "NOME": "PRODUTO DE TESTE",
    "PRECO": 99.99,
    "OFERTA": 0.00,
    "IMG": "https://dominio.com/imagem.jpg",
    "updated_at": "2026-02-27T10:30:00.000000Z",
    "created_at": "2026-02-27T10:30:00.000000Z"
  }
}
```

---

### 3. Obter Produto Específico
**GET** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/12345678901234`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": {
    "id": 1,
    "CODIGO": "12345678901234",
    "NOME": "PRODUTO DE TESTE",
    "PRECO": 99.99,
    "OFERTA": 0.00,
    "IMG": "https://dominio.com/imagem.jpg",
    "created_at": "2026-02-27T10:30:00.000000Z",
    "updated_at": "2026-02-27T10:30:00.000000Z"
  }
}
```

---

### 4. Atualizar Produto
**PUT** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/12345678901234`

**Body (JSON) - todos campos opcionais:**
```json
{
  "CODIGO": "12345678901234",
  "NOME": "NOME ATUALIZADO DO PRODUTO",
  "PRECO": 120.50,
  "OFERTA": 110.00,
  "IMG": "https://dominio.com/imagem_atualizada.jpg"
}
```

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Produto atualizado com sucesso",
  "CODIGO": "12345678901234",
  "dados": {
    "id": 1,
    "CODIGO": "12345678901234",
    "NOME": "NOME ATUALIZADO DO PRODUTO",
    "PRECO": 120.50,
    "OFERTA": 110.00,
    "IMG": "https://dominio.com/imagem_atualizada.jpg",
    "created_at": "2026-02-27T10:30:00.000000Z",
    "updated_at": "2026-02-27T10:35:00.000000Z"
  }
}
```

---

### 5. Deletar Produto
**DELETE** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/12345678901234`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Produto deletado com sucesso",
  "CODIGO": "12345678901234"
}
```

---

## Exemplos com cURL

### Listar todos os produtos
```bash
curl -X GET http://192.168.1.50:5000/api/produtos
```

### Cadastrar produto
```bash
curl -X POST http://192.168.1.50:5000/api/produtos \
  -H "Content-Type: application/json" \
  -d '{
    "CODIGO": "12345678901234",
    "NOME": "PRODUTO DE TESTE",
    "PRECO": 99.99,
    "OFERTA": 0.00,
    "IMG": "https://dominio.com/imagem.jpg"
  }'
```

### Obter um produto pelo CODIGO
```bash
curl -X GET http://192.168.1.50:5000/api/produtos/12345678901234
```

### Atualizar produto pelo CODIGO
```bash
curl -X PUT http://192.168.1.50:5000/api/produtos/12345678901234 \
  -H "Content-Type: application/json" \
  -d '{
    "NOME": "NOME ATUALIZADO",
    "PRECO": 120.50
  }'
```

### Deletar produto pelo CODIGO
```bash
curl -X DELETE http://192.168.1.50:5000/api/produtos/12345678901234
```

---

## Validações

| Campo | Tipo | Validação |
|-------|------|-----------|
| CODIGO | string | Obrigatório (CREATE), exatamente 14 caracteres, único |
| NOME | string | Obrigatório, máx 255 caracteres |
| PRECO | decimal | Obrigatório, mínimo 0,00 |
| OFERTA | decimal | Opcional, mínimo 0,00 |
| IMG | string | Opcional, URL válida, máx 500 caracteres |

---

## Observações Importantes

- **CODIGO é o identificador único** do produto (não é o ID interno)

---

## Departamentos

> **A tabela de departamentos agora só possui `id` automático e `nome`.**

### Endpoints
- **GET** `/api/departamentos` – listar todos
- **GET** `/api/departamentos/{id}` – obter um departamento
- **POST** `/api/departamentos` – criar novo departamento
- **PUT** `/api/departamentos/{id}` – atualizar departamento
- **DELETE** `/api/departamentos/{id}` – remover departamento

### Exemplo de POST
```bash
curl -X POST http://192.168.1.50:5000/api/departamentos \
  -H "Content-Type: application/json" \
  -d '{"nome":"Nome do Departamento"}'
```

## Grupos

> **campo `codigo` representa agora o ID do departamento ao qual o grupo pertence.**

### Endpoints
- **GET** `/api/grupos` – listar todos
- **GET** `/api/grupos/{id}` – obter um grupo
- **POST** `/api/grupos` – criar novo grupo (campo `codigo` recebe o `departamento_id`)
- **PUT** `/api/grupos/{id}` – atualizar grupo
- **DELETE** `/api/grupos/{id}` – remover grupo

### Exemplo de POST
```bash
curl -X POST http://192.168.1.50:5000/api/grupos \
  -H "Content-Type: application/json" \
  -d '{"codigo":1,"nome":"Nome do Grupo"}'
```

_Para os **produtos** somente:_
- Todos os mecanismos de pesquisa, UPDATE e DELETE usam **CODIGO**
- O campo **CODIGO deve ter exatamente 14 caracteres**
- O campo CODIGO deve ser único (não pode haver duplicatas)
- Route Model Binding usa CODIGO automaticamente nas rotas
