# API Totem - Documentação de Endpoints

**Base URL:** `http://localhost:8000/api`

## Endpoints Disponíveis

### PRODUTOS

#### 1. Listar Todos os Produtos
**GET** `/api/produtos`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": [
    {
      "id": 1,
      "CODIGO": "PROD001",
      "NOME": "Monitor Dell 24",
      "PRECO": 500.00,
      "OFERTA": 450.00,
      "IMG": "https://dominio.com/monitor.jpg",
      "departamento_id": 1,
      "grupo_id": 1,
      "created_at": "2026-02-27T10:30:00.000000Z",
      "updated_at": "2026-02-27T10:30:00.000000Z",
      "departamentoRelation": {
        "id": 1,
        "nome": "TI",
        "created_at": "2026-02-27T10:25:00.000000Z",
        "updated_at": "2026-02-27T10:25:00.000000Z"
      },
      "grupoRelation": {
        "id": 1,
        "nome": "Backend",
        "departamento_id": 1,
        "created_at": "2026-02-27T10:28:00.000000Z",
        "updated_at": "2026-02-27T10:28:00.000000Z"
      }
    }
  ],
  "total": 1
}
```

---

#### 2. Cadastrar Novo Produto
**POST** `/api/produtos`

**Body (JSON):**
```json
{
  "CODIGO": "PROD001",
  "NOME": "Monitor Dell 24",
  "PRECO": 500.00,
  "OFERTA": 450.00,
  "IMG": "https://dominio.com/monitor.jpg",
  "departamento_id": 1,
  "grupo_id": 1
}
```

**Campos obrigatórios:**
- `CODIGO`: string, máx 14 caracteres, único
- `NOME`: string, obrigatório
- `PRECO`: decimal, obrigatório, mínimo 0
- `departamento_id`: integer, **obrigatório** (deve existir na tabela departamentos)
- `grupo_id`: integer, **obrigatório** (deve existir na tabela grupos e pertencer ao departamento_id)

**Campos opcionais:**
- `OFERTA`: decimal, mínimo 0
- `IMG`: URL válida, máx 500 caracteres

**Resposta de sucesso (201):**
```json
{
  "sucesso": true,
  "mensagem": "Produto cadastrado com sucesso",
  "CODIGO": "PROD001",
  "dados": {
    "id": 1,
    "CODIGO": "PROD001",
    "NOME": "Monitor Dell 24",
    "PRECO": 500.00,
    "OFERTA": 450.00,
    "IMG": "https://dominio.com/monitor.jpg",
    "departamento_id": 1,
    "grupo_id": 1,
    "created_at": "2026-02-27T10:30:00.000000Z",
    "updated_at": "2026-02-27T10:30:00.000000Z",
    "departamentoRelation": {
      "id": 1,
      "nome": "TI",
      "created_at": "2026-02-27T10:25:00.000000Z",
      "updated_at": "2026-02-27T10:25:00.000000Z"
    },
    "grupoRelation": {
      "id": 1,
      "nome": "Backend",
      "departamento_id": 1,
      "created_at": "2026-02-27T10:28:00.000000Z",
      "updated_at": "2026-02-27T10:28:00.000000Z"
    }
  }
}
```

**Respostas de erro (422):**

Grupo não pertence ao departamento informado:
```json
{
  "sucesso": false,
  "mensagem": "O grupo selecionado não pertence ao departamento informado."
}
```

Campo obrigatório faltando:
```json
{
  "message": "O departamento é obrigatório. (and 4 more errors)",
  "errors": {
    "departamento_id": ["O departamento é obrigatório."],
    "grupo_id": ["O grupo é obrigatório."]
  }
}
```

---

#### 3. Obter Produto Específico
**GET** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/PROD001`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": {
    "id": 1,
    "CODIGO": "PROD001",
    "NOME": "Monitor Dell 24",
    "PRECO": 500.00,
    "OFERTA": 450.00,
    "IMG": "https://dominio.com/monitor.jpg",
    "departamento_id": 1,
    "grupo_id": 1,
    "created_at": "2026-02-27T10:30:00.000000Z",
    "updated_at": "2026-02-27T10:30:00.000000Z",
    "departamentoRelation": {
      "id": 1,
      "nome": "TI"
    },
    "grupoRelation": {
      "id": 1,
      "nome": "Backend",
      "departamento_id": 1
    }
  }
}
```

---

#### 4. Atualizar Produto
**PUT** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/PROD001`

**Body (JSON) - campos opcionais:**
```json
{
  "NOME": "Monitor Dell 27",
  "PRECO": 599.99,
  "OFERTA": 549.99,
  "departamento_id": 1,
  "grupo_id": 1
}
```

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Produto atualizado com sucesso",
  "CODIGO": "PROD001",
  "dados": {
    "id": 1,
    "CODIGO": "PROD001",
    "NOME": "Monitor Dell 27",
    "PRECO": 599.99,
    "OFERTA": 549.99,
    "departamento_id": 1,
    "grupo_id": 1,
    "created_at": "2026-02-27T10:30:00.000000Z",
    "updated_at": "2026-02-27T11:00:00.000000Z",
    "departamentoRelation": {
      "id": 1,
      "nome": "TI"
    },
    "grupoRelation": {
      "id": 1,
      "nome": "Backend",
      "departamento_id": 1
    }
  }
}
```

---

#### 5. Deletar Produto
**DELETE** `/api/produtos/{CODIGO}`

**Exemplo:** `/api/produtos/PROD001`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Produto deletado com sucesso",
  "CODIGO": "PROD001"
}
```

---

### DEPARTAMENTOS

#### 1. Listar Todos os Departamentos
**GET** `/api/departamentos`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": [
    {
      "id": 1,
      "nome": "TI",
      "created_at": "2026-02-27T10:25:00.000000Z",
      "updated_at": "2026-02-27T10:25:00.000000Z"
    },
    {
      "id": 2,
      "nome": "RH",
      "created_at": "2026-02-27T10:26:00.000000Z",
      "updated_at": "2026-02-27T10:26:00.000000Z"
    }
  ]
}
```

---

#### 2. Cadastrar Novo Departamento
**POST** `/api/departamentos`

**Body (JSON):**
```json
{
  "nome": "Financeiro"
}
```

**Campos:**
- `nome`: string, obrigatório, máx 255 caracteres

**Resposta de sucesso (201):**
```json
{
  "sucesso": true,
  "mensagem": "Departamento criado com sucesso",
  "dados": {
    "id": 3,
    "nome": "Financeiro",
    "created_at": "2026-02-27T10:40:00.000000Z",
    "updated_at": "2026-02-27T10:40:00.000000Z"
  }
}
```

---

#### 3. Obter Departamento Específico
**GET** `/api/departamentos/{id}`

**Exemplo:** `/api/departamentos/1`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": {
    "id": 1,
    "nome": "TI",
    "created_at": "2026-02-27T10:25:00.000000Z",
    "updated_at": "2026-02-27T10:25:00.000000Z"
  }
}
```

---

#### 4. Atualizar Departamento
**PUT** `/api/departamentos/{id}`

**Body (JSON):**
```json
{
  "nome": "Tecnologia da Informação"
}
```

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Departamento atualizado com sucesso",
  "dados": {
    "id": 1,
    "nome": "Tecnologia da Informação",
    "created_at": "2026-02-27T10:25:00.000000Z",
    "updated_at": "2026-02-27T11:00:00.000000Z"
  }
}
```

---

#### 5. Deletar Departamento
**DELETE** `/api/departamentos/{id}`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Departamento removido"
}
```

---

### GRUPOS

#### 1. Listar Todos os Grupos
**GET** `/api/grupos`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": [
    {
      "id": 1,
      "nome": "Backend",
      "departamento_id": 1,
      "created_at": "2026-02-27T10:28:00.000000Z",
      "updated_at": "2026-02-27T10:28:00.000000Z",
      "departamento": {
        "id": 1,
        "nome": "TI",
        "created_at": "2026-02-27T10:25:00.000000Z",
        "updated_at": "2026-02-27T10:25:00.000000Z"
      }
    }
  ]
}
```

---

#### 2. Cadastrar Novo Grupo
**POST** `/api/grupos`

**Body (JSON):**
```json
{
  "nome": "Frontend",
  "departamento_id": 1
}
```

**Campos:**
- `nome`: string, obrigatório, máx 255 caracteres
- `departamento_id`: integer, **obrigatório** (deve existir na tabela departamentos)

**Regra de negócio:**
- **Um grupo sempre pertence a um departamento** (chave estrangeira obrigatória)
- Ao deletar um departamento, todos seus grupos são deletados em cascata
- Ao deletar um grupo, todos os produtos associados são deletados em cascata

**Resposta de sucesso (201):**
```json
{
  "sucesso": true,
  "mensagem": "Grupo criado com sucesso",
  "dados": {
    "id": 2,
    "nome": "Frontend",
    "departamento_id": 1,
    "created_at": "2026-02-27T10:45:00.000000Z",
    "updated_at": "2026-02-27T10:45:00.000000Z",
    "departamento": {
      "id": 1,
      "nome": "TI",
      "created_at": "2026-02-27T10:25:00.000000Z",
      "updated_at": "2026-02-27T10:25:00.000000Z"
    }
  }
}
```

**Resposta de erro (422):**
```json
{
  "message": "O departamento é obrigatório.",
  "errors": {
    "departamento_id": ["O departamento é obrigatório."]
  }
}
```

---

#### 3. Obter Grupo Específico
**GET** `/api/grupos/{id}`

**Exemplo:** `/api/grupos/1`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "dados": {
    "id": 1,
    "nome": "Backend",
    "departamento_id": 1,
    "created_at": "2026-02-27T10:28:00.000000Z",
    "updated_at": "2026-02-27T10:28:00.000000Z",
    "departamento": {
      "id": 1,
      "nome": "TI"
    }
  }
}
```

---

#### 4. Atualizar Grupo
**PUT** `/api/grupos/{id}`

**Body (JSON):**
```json
{
  "nome": "Backend APIs",
  "departamento_id": 1
}
```

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Grupo atualizado com sucesso",
  "dados": {
    "id": 1,
    "nome": "Backend APIs",
    "departamento_id": 1,
    "created_at": "2026-02-27T10:28:00.000000Z",
    "updated_at": "2026-02-27T11:05:00.000000Z",
    "departamento": {
      "id": 1,
      "nome": "TI"
    }
  }
}
```

---

#### 5. Deletar Grupo
**DELETE** `/api/grupos/{id}`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "mensagem": "Grupo removido"
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
    "NOME": "PRODUTO DE TESTE",
    "PRECO": 99.99,
    "OFERTA": 0.00,
    "IMG": "https://dominio.com/imagem.jpg"
  }'
```

### Obter um produto
```bash
curl -X GET http://192.168.1.50:5000/api/produtos/1
```

### Atualizar produto
```bash
curl -X PUT http://192.168.1.50:5000/api/produtos/1 \
  -H "Content-Type: application/json" \
  -d '{
    "NOME": "NOME ATUALIZADO",
    "PRECO": 120.50
  }'
```

### Deletar produto
```bash
curl -X DELETE http://192.168.1.50:5000/api/produtos/1
```

---

## Validações

| Campo | Tipo | Validação |
|-------|------|-----------|
| NOME | string | Obrigatório, máx 255 caracteres |
| PRECO | decimal | Obrigatório, mínimo 0,00 |
| OFERTA | decimal | Opcional, mínimo 0,00 |
| IMG | string | Opcional, deve ser URL válida, máx 500 caracteres |

---

## Fluxo de Migração

Para aplicar as novas mudanças na base de dados, execute:

```bash
php artisan migrate
```

Isso irá:
1. Dropar as tabelas antigas (`produtodnm` e `produto`)
2. Criar a nova tabela `produto` com a estrutura simplificada
