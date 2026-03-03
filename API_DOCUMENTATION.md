# API Totem - Documentação de Endpoints

**Base URL:** `http://localhost:8000/api`

## Autenticação Multiempresa (Atual)

### Login da empresa
**POST** `/api/login`

**Body (JSON):**
```json
{
  "cnpj": "12345678000199",
  "senha": "senha_da_empresa"
}
```

Também aceita:
- `cnpj_cpf` no lugar de `cnpj`
- `chave` no lugar de `senha`

**Resposta de sucesso (200):**
```json
{
  "sucesso": true,
  "token": "TOKEN_DA_EMPRESA",
  "empresa": {
    "id": 10,
    "nome": "Empresa X",
    "cnpj_cpf": "12345678000199"
  }
}
```

### Uso do token nas próximas requisições
Enviar no header:
```http
Authorization: Bearer TOKEN_DA_EMPRESA
```

### Rotas protegidas por empresa
- `/api/produtos`
- `/api/grupos`
- `/api/departamentos`
- `/api/configuracoes` (e alias `/api/configuracao`)

### Regra de isolamento
Todos os dados são filtrados automaticamente por `empresa_id` da empresa autenticada.
Não é mais necessário enviar `cnpj_cpf` para isolamento dos dados.

## Endpoints Disponíveis

## TV Activation API

### 1. Gerar código de ativação
**POST** `/api/tv/activation-code`

**Body (JSON):**
```json
{
  "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
}
```

**Resposta (200):**
```json
{
  "code": "84721",
  "expires_in": 300
}
```

### 2. Verificar ativação
**POST** `/api/tv/check-activation`

**Body (JSON):**
```json
{
  "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
}
```

**Pendente (200):**
```json
{
  "status": "pending"
}
```

**Ativado (200):**
```json
{
  "status": "activated",
  "token": "TOKEN_PERMANENTE_DA_TV"
}
```

### 3. Heartbeat da TV
**POST** `/api/tv/heartbeat`

Pode enviar token no header `Authorization: Bearer TOKEN_DA_TV` ou no body:

```json
{
  "token": "TOKEN_DA_TV"
}
```

### 4. Produtos para TV (protegido por token do device)
**GET** `/api/tv/produtos`

Header obrigatório:
```http
Authorization: Bearer TOKEN_DA_TV
```

### 5. Ofertas para TV (protegido por token do device)
**GET** `/api/tv/ofertas`

Header obrigatório:
```http
Authorization: Bearer TOKEN_DA_TV
```

As rotas de TV com token filtram automaticamente por `empresa_id` do dispositivo ativado.

### Painel admin para ativação

- Tela: `GET /admin/ativar-tv`
- Ação: `POST /admin/activate-device`
- Campos: `code`, `empresa_id` (admin), `nome_tv`, `local`

### Limpeza automática

Comando agendado: `devices:cleanup-activations`

Remove ativações expiradas e não utilizadas.

### PRODUTOS

#### 1. Listar Todos os Produtos
**GET** `/api/produtos`

**Query Parameters:**
- `departamento_id` (optional): Filtrar por departamento
- `grupo_id` (optional): Filtrar por grupo

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
      "departamento": {
        "id": 1,
        "nome": "TI"
      },
      "grupo": {
        "id": 1,
        "nome": "Backend",
        "departamento_id": 1
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
    "departamento": {
      "id": 1,
      "nome": "TI"
    },
    "grupo": {
      "id": 1,
      "nome": "Backend",
      "departamento_id": 1
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
    "departamento": {
      "id": 1,
      "nome": "TI"
    },
    "grupo": {
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
    "updated_at": "2026-02-27T10:30:00.000000Z",
    "departamento": {
      "id": 1,
      "nome": "TI"
    },
    "grupo": {
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
curl -X GET http://localhost:8000/api/produtos
```

### Cadastrar produto (com departamento e grupo obrigatórios)
```bash
curl -X POST http://localhost:8000/api/produtos \
  -H "Content-Type: application/json" \
  -d '{
    "CODIGO": "PROD001",
    "NOME": "Monitor Dell",
    "PRECO": 500.00,
    "OFERTA": 450.00,
    "departamento_id": 1,
    "grupo_id": 1
  }'
```

### Obter um produto
```bash
curl -X GET http://localhost:8000/api/produtos/PROD001
```

### Atualizar produto
```bash
curl -X PUT http://localhost:8000/api/produtos/PROD001 \
  -H "Content-Type: application/json" \
  -d '{
    "NOME": "Monitor Dell 27",
    "PRECO": 599.99
  }'
```

### Deletar produto
```bash
curl -X DELETE http://localhost:8000/api/produtos/PROD001
```

### Listar departamentos
```bash
curl -X GET http://localhost:8000/api/departamentos
```

### Criar departamento
```bash
curl -X POST http://localhost:8000/api/departamentos \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Tecnologia"
  }'
```

### Listar grupos
```bash
curl -X GET http://localhost:8000/api/grupos
```

### Criar grupo (obrigatório informar departamento)
```bash
curl -X POST http://localhost:8000/api/grupos \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Backend",
    "departamento_id": 1
  }'
```

---

## Validações

| Recurso | Campo | Tipo | Validação |
|---------|-------|------|-----------|
| **PRODUTO** | CODIGO | string | Obrigatório, máx 14 chars, único |
| | NOME | string | Obrigatório, máx 255 chars |
| | PRECO | decimal | Obrigatório, mínimo 0.00 |
| | OFERTA | decimal | Opcional, mínimo 0.00 |
| | IMG | string | Opcional, URL válida, máx 500 chars |
| | departamento_id | integer | **Obrigatório**, deve existir |
| | grupo_id | integer | **Obrigatório**, deve existir e pertencer ao departamento |
| **DEPARTAMENTO** | nome | string | Obrigatório, máx 255 chars |
| **GRUPO** | nome | string | Obrigatório, máx 255 chars |
| | departamento_id | integer | **Obrigatório**, deve existir |

---

## Relações e Regras de Negócio

### Hierarquia
```
Departamento (1)
  └── Grupo (N) - cada grupo pertence a exatamente um departamento
      └── Produto (N) - cada produto pertence a um departamento e um grupo
```

### Cascata de Deletação
- **Deletar Departamento** → Remove todos os Grupos associados → Remove todos os Produtos
- **Deletar Grupo** → Remove todos os Produtos associados
- **Deletar Produto** → Sem impacto em outras entidades

### Validações Cruzadas
- Ao criar um **Produto**, o `grupo_id` informado **deve pertencer** ao `departamento_id` informado
- Violação retorna erro 422 com mensagem: `"O grupo selecionado não pertence ao departamento informado."`

---

## Filtros de Query

### Listar Produtos com Filtros
```bash
# Por departamento
curl http://localhost:8000/api/produtos?departamento_id=1

# Por grupo
curl http://localhost:8000/api/produtos?grupo_id=1

# Combinado
curl http://localhost:8000/api/produtos?departamento_id=1&grupo_id=1
```

---

## Fluxo de Migração

Para aplicar as mudanças no banco de dados, execute:

```bash
php artisan migrate
```

Isso irá:
1. Criar tabelas `departamentos` e `grupos` com relacionamentos
2. Adicionar colunas `departamento_id` e `grupo_id` à tabela `produto`
3. Garantir integridade referencial com chaves estrangeiras

---

## Estrutura do Banco de Dados

### Tabela: departamentos
```sql
CREATE TABLE departamentos (
  id bigint PRIMARY KEY auto_increment,
  nome varchar(255) NOT NULL,
  created_at timestamp,
  updated_at timestamp
)
```

### Tabela: grupos
```sql
CREATE TABLE grupos (
  id bigint PRIMARY KEY auto_increment,
  nome varchar(255) NOT NULL,
  departamento_id bigint NOT NULL,
  created_at timestamp,
  updated_at timestamp,
  FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
)
```

### Tabela: produto
```sql
CREATE TABLE produto (
  id bigint PRIMARY KEY auto_increment,
  CODIGO varchar(50) UNIQUE NOT NULL,
  NOME varchar(255) NOT NULL,
  PRECO decimal(10,2) NOT NULL,
  OFERTA decimal(10,2) DEFAULT 0,
  IMG varchar(500),
  departamento_id bigint NOT NULL,
  grupo_id bigint NOT NULL,
  created_at timestamp,
  updated_at timestamp,
  FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
  FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE
)
```

---

## Status de Respostas HTTP

| Código | Significado |
|--------|-------------|
| 200 | OK - Operação bem-sucedida |
| 201 | Created - Recurso criado com sucesso |
| 422 | Unprocessable Entity - Erro de validação |
| 500 | Internal Server Error - Erro no servidor |
