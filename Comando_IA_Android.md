# Integracao Android com API da TV

Este arquivo descreve o fluxo real que o app Android precisa seguir para funcionar com a API de TV deste projeto.

Escopo deste documento:

- app Android de TV, em modo kiosk, consumindo a mesma API usada pela tela web `/tv/totemweb`
- reaproveitamento maximo das configuracoes ja existentes no admin web
- indicacao clara do que ja existe pronto no backend e no admin
- indicacao do que o app Android nativo precisa reinterpretar localmente
- separacao entre app TV de exibicao e app administrativo interno

O objetivo e cobrir:

- como o app Android gera e persiste o identificador do dispositivo
- como solicitar o codigo de ativacao para mostrar na tela
- como consultar se a ativacao foi concluida no painel administrativo
- como receber e salvar o token definitivo da TV
- como consumir as rotas autenticadas da TV depois da ativacao
- quais respostas de erro o app precisa tratar

## Decisao principal para o projeto Android

O backend atual ja possui suporte real para TV Android e ja expoe varias configuracoes especificas para Android na mesma API usada pela tela web `/tv/totemweb`.

Isso significa:

- o app Android PODE reutilizar a mesma API de TV que a tela web ja usa
- o app Android PODE reutilizar a maior parte dos controles ja existentes no admin web
- NAO e obrigatorio criar um segundo painel de configuracao so para Android na versao inicial
- o que muda e a camada de renderizacao: no web a interpretacao das regras esta em JavaScript; no Android nativo essa mesma logica precisa ser implementada em Kotlin

Conclusao pratica:

- se o app Android for um WebView apontando para `/tv/totemweb`, o reaproveitamento e quase total
- se o app Android for nativo, ele deve consumir a mesma API e reinterpretar localmente as configuracoes visuais
- novos controles no admin so sao necessarios se o app nativo passar a exigir comportamentos que a API atual ainda nao consegue expressar

## Visao geral do fluxo

Fluxo completo da ativacao:

1. O app Android gera ou recupera um `device_uuid` persistente no aparelho.
2. O app chama `POST /api/tv/activation-code` enviando esse `device_uuid`.
3. A API devolve um `code` com validade de 300 segundos.
4. O app exibe esse codigo na tela da TV.
5. Um usuario no painel admin informa esse codigo e conclui a ativacao da TV.
6. O app faz polling em `POST /api/tv/check-activation` usando o mesmo `device_uuid`.
7. Quando a ativacao for concluida, a API responde `status=activated` e envia o `token` da TV.
8. O app salva esse token localmente.
9. A partir dai o app usa o token nas rotas protegidas da TV.

## Conceitos importantes

### 1. `device_uuid`

Esse identificador representa o dispositivo fisico.

Regras praticas para o Android:

- deve ser gerado uma unica vez e salvo localmente
- deve continuar o mesmo entre reinicializacoes do app
- nao deve ser recriado a cada abertura do aplicativo
- deve ter no maximo 100 caracteres
- pode ser um UUID v4 padrao

Exemplo valido:

```textSIM
9f23edc7-9a60-4f18-bf4b-29f5d71b7030
```

### 2. `code`

Esse e o codigo temporario mostrado na TV para o usuario ativar o dispositivo no painel administrativo.

Regras reais da API:

- tamanho de 10 caracteres
- alfanumerico em caixa alta
- validade de 300 segundos
- cada chamada de geracao cria um novo codigo

### 3. `token`

Esse e o token definitivo da TV.

Regras praticas:

- deve ser salvo localmente no Android assim que a API devolver
- deve ser enviado no header `Authorization: Bearer TOKEN_DA_TV`
- se a API responder que o dispositivo nao existe mais ou foi desativado, o app deve forcar reconfiguracao

## Base URL

Exemplo :

```text
https://app.tabeladeprecodigital.com.br/api
```

Em producao, substituir pelo dominio real da API.

## Headers padrao

### Rotas publicas de ativacao

```http
Accept: application/json
Content-Type: application/json
```

### Rotas autenticadas da TV

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer TOKEN_DA_TV
```

## Etapa 1. Gerar ou recuperar o `device_uuid`

Antes de chamar a API, o app Android precisa ter um identificador persistente do dispositivo.

Implementacao recomendada:

- ao abrir o app pela primeira vez, gerar um UUID
- salvar em `SharedPreferences`, banco local ou armazenamento seguro
- em todas as proximas execucoes, reutilizar o mesmo valor

Exemplo de valor salvo:

```text
9f23edc7-9a60-4f18-bf4b-29f5d71b7030
```

## Etapa 2. Solicitar codigo de ativacao

### Endpoint

```http
POST /api/tv/activation-code
```

### Body

```json
{
  "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
}
```

### Resposta de sucesso

Status: `200 OK`

```json
{
  "code": "AB12CD34EF",
  "expires_in": 300
}
```

### O que o app deve fazer

- mostrar o `code` para o usuario na tela
- iniciar contagem regressiva com base em `expires_in`
- iniciar polling em `check-activation`
- quando expirar, solicitar um novo codigo se ainda nao estiver ativado

### Erros esperados

Se faltar `device_uuid` ou vier invalido, a validacao do Laravel retorna `422`.

Exemplo:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "device_uuid": [
      "The device uuid field is required."
    ]
  }
}
```

### Exemplo curl

```bash
curl -X POST "http://localhost:8000/api/tv/activation-code" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
  }'
```

## Etapa 3. Exibir o codigo e aguardar ativacao no painel admin

O app Android nao ativa a TV sozinho.

O que acontece no sistema:

- o app mostra o codigo na tela
- um usuario acessa o painel admin
- o usuario informa esse codigo na tela administrativa de ativacao
- o admin define nome da TV, local e configuracoes do dispositivo
- o sistema gera o token definitivo da TV

Detalhe importante da API:

- a ativacao administrativa procura um registro em `device_activations` com:
  - `code` igual ao informado
  - `activated = false`
  - `expires_at > now()`

Se estiver tudo certo, o backend:

- cria ou atualiza o `Device`
- vincula a TV a empresa
- gera `token` unico da TV
- marca a ativacao como concluida

## Etapa 4. Fazer polling para saber se a TV foi ativada

### Endpoint

```http
POST /api/tv/check-activation
```

### Body

```json
{
  "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
}
```

### Resposta quando ainda nao ativou

Status: `200 OK`

```json
{
  "status": "pending"
}
```

### Resposta quando ativou

Status: `200 OK`

```json
{
  "status": "activated",
  "token": "TOKEN_DA_TV"
}
```

### O que o app deve fazer

- consultar a cada 3 a 5 segundos enquanto o codigo estiver valido
- se receber `pending`, continuar esperando
- se receber `activated`, salvar o `token` e sair do fluxo de ativacao
- se o codigo expirar antes da ativacao, gerar um novo codigo

### Regra importante

O app deve enviar sempre o mesmo `device_uuid` usado na geracao do codigo.

### Exemplo curl

```bash
curl -X POST "http://localhost:8000/api/tv/check-activation" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030"
  }'
```

## Etapa 5. Salvar o token da TV

Quando `check-activation` responder com `status=activated`, o app deve salvar o token.

Recomendacoes:

- salvar em `SharedPreferences` criptografado ou armazenamento seguro equivalente
- manter tambem o `device_uuid`
- nao apagar o token em falhas temporarias de rede
- so descartar o token quando a API indicar reconfiguracao forcada

Campos locais recomendados:

```json
{
  "device_uuid": "9f23edc7-9a60-4f18-bf4b-29f5d71b7030",
  "tv_device_token": "TOKEN_DA_TV",
  "tv_last_device_token": "TOKEN_DA_TV"
}
```

Observacao pratica importante para o app:

- preserve o ultimo token conhecido para facilitar reaproveitamento em fluxos de reconfiguracao
- nao remova automaticamente o token salvo em erros 401 transitorios sem confirmar o motivo da resposta

## Etapa 6. Enviar heartbeat da TV

Depois da ativacao, o app deve informar periodicamente que a TV esta online.

### Endpoint

```http
POST /api/tv/heartbeat
```

### Autenticacao aceita

Forma preferencial:

```http
Authorization: Bearer TOKEN_DA_TV
```

Forma alternativa aceita pela API:

```json
{
  "token": "TOKEN_DA_TV"
}
```

### Resposta de sucesso

```json
{
  "status": "ok",
  "last_seen_at": "2026-04-17T10:30:00Z"
}
```

### Resposta quando nao envia token

Status: `422 Unprocessable Entity`

```json
{
  "status": "error",
  "message": "Token nao informado."
}
```

### Resposta quando token nao pertence a dispositivo ativo

Status: `401 Unauthorized`

```json
{
  "status": "error",
  "message": "Dispositivo nao encontrado."
}
```

### Recomendacao de uso

- enviar heartbeat em intervalo fixo, por exemplo a cada 60 segundos
- nao bloquear a UI se o heartbeat falhar
- registrar logs locais para diagnostico

### Exemplo curl

```bash
curl -X POST "http://localhost:8000/api/tv/heartbeat" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_DA_TV"
```

## Etapa 7. Buscar bootstrap inicial da TV

Essa rota entrega dados basicos do dispositivo, empresa e configuracao.

### Endpoint

```http
GET /api/tv/bootstrap
```

### Autenticacao

Bearer token da TV.

### Resposta de sucesso

```json
{
  "status": "ok",
  "device": {
    "id": 1,
    "nome": "TV Acougue",
    "local": "Setor Acougue",
    "empresa_id": 1,
    "last_seen_at": "2026-04-17T10:30:00Z"
  },
  "empresa": {
    "id": 1,
    "nome": "Mercado Exemplo",
    "cnpj_cpf": "12345678000199"
  },
  "configuracao": {
    "id": 1,
    "atualizar_produtos_segundos": 30,
    "volume": 10,
    "orientacao": "landscape"
  }
}
```

### Quando usar

- logo apos a ativacao
- ao iniciar o app com token salvo
- ao retomar a sessao, se precisar sincronizar estado basico

## Etapa 8. Buscar lista de produtos da TV

### Endpoint

```http
GET /api/tv/produtos
```

### Autenticacao

Bearer token da TV.

### Resposta de sucesso

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

### Observacoes importantes do retorno

- `oferta` vira `false` quando nao houver oferta maior que zero
- a lista ja vem filtrada conforme configuracao do dispositivo no backend
- a lista pode vir sem imagem se a configuracao da tela desabilitar imagens

### Resposta quando o token nao foi enviado

Status: `401 Unauthorized`

```json
{
  "success": false,
  "reason": "token_missing",
  "forceReconfigure": false,
  "data": {
    "produtos": []
  },
  "meta": {
    "total_produtos": 0
  }
}
```

### Resposta quando o dispositivo nao existe mais

Status: `401 Unauthorized`

```json
{
  "success": false,
  "reason": "device_not_found",
  "forceReconfigure": true,
  "data": {
    "produtos": []
  },
  "meta": {
    "total_produtos": 0
  }
}
```

### Resposta quando o dispositivo esta desativado

Status: `401 Unauthorized`

```json
{
  "success": false,
  "reason": "device_inactive",
  "forceReconfigure": true,
  "data": {
    "produtos": []
  },
  "meta": {
    "total_produtos": 0
  }
}
```

### Regra de tratamento no app

- se `reason=token_missing`, provavelmente houve erro local no armazenamento do token
- se `forceReconfigure=true`, mandar o app voltar ao fluxo de ativacao/configuracao
- se for erro de rede, nao apagar token automaticamente

## Etapa 9. Buscar configuracao visual da tela web / Android

Se o app Android precisa montar layout dinamico com configuracoes vindas da API, use a rota abaixo.

### Endpoint

```http
GET /api/tv/totemweb/config
```

### Autenticacao

Bearer token da TV.

### O que essa rota entrega

Ela retorna uma estrutura extensa de configuracao visual, incluindo por exemplo:

- intervalo de refresh da API
- exibir ou nao painel de video
- exibir ou nao barra lateral
- logos e dimensoes especificas para Android
- cores, bordas e arredondamentos
- playlist de videos
- imagens da lateral direita
- configuracoes de slide em tela cheia
- configuracoes de carrossel de produtos
- fontes, tamanhos e titulos
- configuracoes de oferta e aparencia geral

### Resposta objetiva para a pergunta sobre o admin web

Sim. O dispositivo Android consegue pegar grande parte das configuracoes que ja existem no admin web, porque a API ja devolve campos especificos para Android.

Exemplos reais do que ja existe no contrato atual:

- posicao e tamanho de logo para Android
- largura e altura de logo vertical para Android
- habilitacao de fullscreen slide para Android
- largura e altura de imagem fullscreen para Android
- largura, altura e offsets da barra lateral para Android
- agendas de imagens com `enabledForAndroid`
- tamanhos, fontes, cores, badges, posicoes e offsets de nome/preco por plataforma

Portanto, para o app Android TV de exibicao, a base correta e:

- consumir `GET /api/tv/totemweb/config`
- interpretar os campos Android quando existirem
- usar fallback para campos genericos quando o campo Android especifico nao vier preenchido

### O que o app Android nativo ainda precisa implementar

A API entrega os dados, mas o comportamento visual ainda precisa ser codificado no app Android nativo.

O app Android precisa implementar localmente pelo menos estas regras:

- escolher versao Android ou generica de cada campo visual
- respeitar agendas de imagem por data
- respeitar agendas de imagem por plataforma
- aplicar fullscreen slide apenas quando habilitado para Android
- aplicar tamanhos, offsets e object fit equivalentes no renderer nativo
- alternar corretamente entre produtos, imagens e videos conforme a configuracao
- respeitar intervalos de refresh e de troca de midia
- tratar fallback quando algum campo vier vazio ou zerado

### Quando novos controles no admin seriam necessarios

So criar novos controles se surgir necessidade de comportamento que NAO esteja representado hoje na API, por exemplo:

- recursos exclusivos de player Android sem equivalente atual no web
- politicas de cache offline configuraveis por dispositivo
- comportamento de brilho, volume do hardware ou preferencias do sistema Android
- layouts novos que nao possam ser descritos pelos campos atuais da configuracao

Se o objetivo for apenas reproduzir no Android o que ja existe em `/tv/totemweb`, o caminho correto e reaproveitar o admin atual e nao abrir outro painel do zero.

### Campos importantes para Android

A API possui campos especificos para Android em varios pontos, por exemplo:

- `rightSidebarLogoPositionAndroid`
- `rightSidebarLogoWidthAndroid`
- `rightSidebarLogoHeightAndroid`
- `leftVerticalLogoWidthAndroid`
- `leftVerticalLogoHeightAndroid`
- `fullScreenSlideEnabledAndroid`
- `fullScreenSlideImageWidthAndroid`
- `fullScreenSlideImageHeightAndroid`
- `rightSidebarAndroidHeight`
- `rightSidebarAndroidWidth`
- `rightSidebarAndroidHorizontalOffset`
- `rightSidebarAndroidRightMargin`
- `rightSidebarAndroidVerticalOffset`

### Resposta de erro quando token nao enviado

```json
{
  "success": false,
  "message": "Token nao informado.",
  "reason": "token_missing",
  "forceReconfigure": false
}
```

### Resposta de erro quando dispositivo nao existe

```json
{
  "success": false,
  "message": "Dispositivo invalido.",
  "reason": "device_not_found",
  "forceReconfigure": true
}
```

### Resposta de erro quando dispositivo foi desativado

```json
{
  "success": false,
  "message": "Dispositivo desativado.",
  "reason": "device_inactive",
  "forceReconfigure": true
}
```

## Etapa 10. Buscar ofertas

### Endpoint

```http
GET /api/tv/ofertas
```

### Autenticacao

Bearer token da TV.

### Resposta de sucesso

```json
{
  "status": "ok",
  "empresa_id": 1,
  "device_id": 1,
  "total": 1,
  "dados": [
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
}
```

## Etapa 11. Buscar midias auxiliares

### Endpoint

```http
GET /api/tv/midias
```

### Autenticacao

Bearer token da TV.

### Resposta atual

No estado atual do controller, a API responde estrutura vazia:

```json
{
  "status": "ok",
  "videos": [],
  "imagens": [],
  "banners": []
}
```

## Fluxo recomendado de inicializacao do app Android

### Cenario A. Primeiro uso, sem token salvo

1. Gerar ou recuperar `device_uuid`.
2. Chamar `POST /api/tv/activation-code`.
3. Exibir o `code` e o tempo restante.
4. Fazer polling com `POST /api/tv/check-activation`.
5. Quando receber `token`, salvar localmente.
6. Chamar `GET /api/tv/bootstrap`.
7. Chamar `GET /api/tv/totemweb/config` se a tela depender de configuracao remota.
8. Chamar `GET /api/tv/produtos`.
9. Iniciar heartbeat em background.

### Cenario B. App ja tem token salvo

1. Carregar `tv_device_token` salvo.
2. Chamar `GET /api/tv/bootstrap` ou `GET /api/tv/produtos`.
3. Se responder sucesso, seguir normalmente.
4. Se `GET /api/tv/produtos` ou `GET /api/tv/totemweb/config` responder `forceReconfigure=true`, voltar para fluxo de ativacao.
5. Se `bootstrap`, `ofertas` ou `midias` responderem `401` generico, tratar como token invalido e redirecionar para reconfiguracao se a falha persistir ou se o dispositivo nao conseguir mais autenticar.
6. Se falhar so por rede, manter token e tentar novamente depois.

## Regras de tratamento de erro no Android

### Quando NAO apagar o token salvo

- timeout de rede
- falha momentanea de internet
- erro 5xx do servidor
- erro 401 sem `forceReconfigure=true` em cenarios nao definitivos

### Onde `forceReconfigure` existe hoje de forma explicita

- `GET /api/tv/produtos`
- `GET /api/tv/totemweb/config`

### Onde hoje o backend pode responder apenas `401` generico

- `GET /api/tv/bootstrap`
- `GET /api/tv/ofertas`
- `GET /api/tv/midias`

Nessas rotas autenticadas por middleware, o app Android deve tratar `401` como token invalido ou dispositivo desativado, mesmo sem campo `forceReconfigure`.

### Quando forcar reconfiguracao

- `GET /api/tv/produtos` com `forceReconfigure=true`
- `GET /api/tv/totemweb/config` com `forceReconfigure=true`
- quando o backend indicar `device_not_found`
- quando o backend indicar `device_inactive`

## Estruturas sugeridas para Retrofit ou serializacao

### ActivationCodeRequest

```json
{
  "device_uuid": "string"
}
```

### ActivationCodeResponse

```json
{
  "code": "string",
  "expires_in": 300
}
```

### CheckActivationRequest

```json
{
  "device_uuid": "string"
}
```

### CheckActivationPendingResponse

```json
{
  "status": "pending"
}
```

### CheckActivationActivatedResponse

```json
{
  "status": "activated",
  "token": "string"
}
```

### HeartbeatResponse

```json
{
  "status": "ok",
  "last_seen_at": "2026-04-17T10:30:00Z"
}
```

### TvProdutosUnauthorizedResponse

```json
{
  "success": false,
  "reason": "device_not_found",
  "forceReconfigure": true,
  "data": {
    "produtos": []
  },
  "meta": {
    "total_produtos": 0
  }
}
```

## Resumo final para a IA que vai implementar o Android

- o app precisa gerar e persistir um `device_uuid`
- a ativacao comeca em `POST /api/tv/activation-code`
- o codigo retornado precisa ser mostrado na tela da TV
- o app precisa consultar `POST /api/tv/check-activation` ate receber o token
- quando receber o token, deve salvar localmente e passar a usar Bearer token
- depois disso, o app usa `heartbeat`, `bootstrap`, `tv/produtos`, `tv/ofertas`, `tv/midias` e opcionalmente `tv/totemweb/config`
- a configuracao visual principal deve ser lida em `GET /api/tv/totemweb/config`
- o app nativo precisa reinterpretar localmente as mesmas regras visuais que hoje existem na tela web
- os controles atuais do admin web ja cobrem grande parte do comportamento Android
- nao criar um segundo painel Android do zero sem necessidade real
- se `tv/produtos` ou `tv/totemweb/config` responder `forceReconfigure=true`, o app deve voltar para o fluxo de ativacao
- se `bootstrap`, `ofertas` ou `midias` responderem `401` repetidamente, tratar como token invalido e voltar para reconfiguracao
- erros de rede nao devem limpar automaticamente o token salvo

## Escopo do app TV versus escopo administrativo

O app Android descrito neste documento e um app de EXIBICAO para TV.

Escopo esperado da TV Android:

- ativar dispositivo
- receber token da TV
- carregar configuracao visual
- carregar produtos, ofertas e midias
- renderizar a tela conforme configuracao recebida
- enviar heartbeat

Fora do escopo da TV Android, a menos que exista um segundo app administrativo:

- CRUD de produtos
- CRUD de departamentos
- CRUD de grupos
- autenticacao com token da empresa para operacoes administrativas

Se futuramente existir um app Android interno de administracao, ele pode usar essas rotas. Mas isso nao faz parte do app TV kiosk.

## Endpoints CRUD da API (somente para app administrativo, nao para a TV kiosk)

Esses endpoints existem no backend e ficam no grupo com middleware `identify.company`.

Headers obrigatorios:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer TOKEN_DA_EMPRESA
```

Observacao importante:

- o token da TV (dispositivo) nao substitui o token da empresa nessas rotas
- para app Android de painel interno/administrativo, usar autenticacao de empresa

### Produtos

```http
GET /api/produtos
POST /api/produtos
GET /api/produtos/{id}
PUT /api/produtos/{id}
PATCH /api/produtos/{id}
DELETE /api/produtos/{id}
```

### Departamentos

```http
GET /api/departamentos
POST /api/departamentos
GET /api/departamentos/{id}
PUT /api/departamentos/{id}
PATCH /api/departamentos/{id}
DELETE /api/departamentos/{id}
```

### Grupos

```http
GET /api/grupos
POST /api/grupos
GET /api/grupos/{id}
PUT /api/grupos/{id}
PATCH /api/grupos/{id}
DELETE /api/grupos/{id}
```

Exemplos de uso em um app administrativo:

- listar catalogo: `GET /api/produtos`
- cadastrar item: `POST /api/produtos`
- atualizar preco/descricao: `PUT` ou `PATCH /api/produtos/{id}`
- organizar estrutura: `GET/POST/PUT/DELETE` em `departamentos` e `grupos`

---

## Configuracoes do projeto Android

### Linguagem e SDK

- Linguagem: **Kotlin**
- Minimum SDK: **21** (Android 5.0)
- Target SDK: **34**
- Compile SDK: **34**

### Arquitetura recomendada

- **MVVM** com ViewModel + StateFlow
- Camada de repositorio isolando chamadas de rede
- Coroutines para operacoes assincronas
- Sem uso de RxJava

## Tipos de layout que o app Android deve suportar

O app Android deve ser capaz de montar os layouts abaixo usando a mesma configuracao recebida da API.

Esses layouts NAO sao telas administrativas. Sao variacoes da tela principal da TV.

### Layout 1. Lista principal com barra lateral direita

Esse e o layout padrao mais comum.

Caracteristicas:

- lista principal de produtos ocupando a area central ou esquerda
- barra lateral direita ativa
- titulo opcional no topo ou no rodape
- logo lateral opcional
- logo vertical esquerda opcional

Campos principais envolvidos:

- `showRightSidebarPanel`
- `showTitle`
- `titlePosition`
- `showRightSidebarLogo`
- `showLeftVerticalLogo`
- `productListType=1`

### Layout 2. Lista principal sem barra lateral direita

Esse layout ocupa praticamente toda a tela com a lista de produtos.

Caracteristicas:

- lista usa largura total
- sem painel lateral de video/imagem
- pode manter titulo e identidade visual

Campos principais envolvidos:

- `showRightSidebarPanel=false`
- `showTitle`
- `productListType=1`

### Layout 3. Lista dupla de produtos

Esse layout divide a listagem principal em duas colunas ou duas listas.

Caracteristicas:

- duas listas de produtos lado a lado
- pode separar por grupos configurados ou dividir automaticamente ao meio
- continua podendo usar barra lateral e titulo

Campos principais envolvidos:

- `productListType=2`
- `productListLeftGroupIds`
- `productListRightGroupIds`
- `showRightSidebarPanel`

### Layout 4. Barra lateral em modo video

Nesse modo a barra lateral exibe video ou playlist de videos.

Caracteristicas:

- video unico ou playlist
- area principal continua com lista de produtos
- logo pode aparecer acima da lateral, conforme configuracao

Campos principais envolvidos:

- `rightSidebarMediaType=video`
- `videoUrl`
- `videoPlaylist`
- `showVideoPanel`
- `showRightSidebarPanel`

### Layout 5. Barra lateral em modo imagem

Nesse modo a barra lateral exibe imagens estaticas ou slideshow de imagens.

Caracteristicas:

- usa lista de imagens configuradas
- pode aplicar agenda por data e por plataforma Android
- cada imagem pode ter tamanho, offset, fit, nome e preco especificos

Campos principais envolvidos:

- `rightSidebarMediaType=image`
- `rightSidebarImageUrls`
- `rightSidebarImageSchedules`
- `rightSidebarImageInterval`
- `rightSidebarAndroidHeight`
- `rightSidebarAndroidWidth`
- `rightSidebarAndroidHorizontalOffset`
- `rightSidebarAndroidVerticalOffset`

### Layout 6. Barra lateral em modo hibrido

Nesse modo a lateral alterna entre produtos, imagens e videos.

Caracteristicas:

- mistura midias na lateral
- pode alternar por sequencia configurada
- pode incluir carrossel de produtos na lateral

Campos principais envolvidos:

- `rightSidebarMediaType=hybrid`
- `rightSidebarPlaybackSequence`
- `rightSidebarHybridVideoDuration`
- `rightSidebarHybridImageDuration`
- `rightSidebarProductCarouselEnabled`
- `rightSidebarProductDisplayMode`
- `rightSidebarProductTransitionMode`

### Layout 7. Slide em tela cheia

Esse layout toma a tela toda temporariamente e depois retorna ao layout principal.

Caracteristicas:

- imagens fullscreen dedicadas
- habilitacao separada para Android
- intervalo, datas e delay de retorno configuraveis
- dimensoes Android especificas quando configuradas

Campos principais envolvidos:

- `fullScreenSlideEnabled`
- `fullScreenSlideEnabledAndroid`
- `fullScreenSlideImageUrls`
- `fullScreenSlideInterval`
- `fullScreenSlideReturnDelaySeconds`
- `fullScreenSlideStartDate`
- `fullScreenSlideEndDate`
- `fullScreenSlideImageWidthAndroid`
- `fullScreenSlideImageHeightAndroid`

### Layout 8. Slide de ofertas

Esse layout mostra ofertas em destaque periodicamente.

Caracteristicas:

- entra como modo especial ou transitorio
- busca dados em `GET /api/tv/ofertas`
- usa configuracao visual propria para fontes, fundo e imagem do produto

Campos principais envolvidos:

- `offerSlideEnabled`
- `offerSlideIntervalSeconds`
- configuracoes visuais de `offerSlide*`

### Regra geral para a IA do Android

O app nao deve criar apenas um layout fixo. Ele deve criar um renderer capaz de montar dinamicamente essas variacoes conforme os campos da API.

Em outras palavras:

- a API escolhe o layout efetivo
- o admin web altera o comportamento
- o Android obedece o contrato recebido

### Dependencias necessarias no `build.gradle`

```kotlin
// Retrofit + OkHttp
implementation("com.squareup.retrofit2:retrofit:2.9.0")
implementation("com.squareup.retrofit2:converter-gson:2.9.0")
implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")

// Gson para serializacao JSON
implementation("com.google.code.gson:gson:2.10.1")

// Coroutines
implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3")

// ViewModel e Lifecycle
implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0")
implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.7.0")

// DataStore (armazenamento local do token e uuid)
implementation("androidx.datastore:datastore-preferences:1.0.0")

// WorkManager (heartbeat em background)
implementation("androidx.work:work-runtime-ktx:2.9.0")

// Carregamento de imagens
implementation("io.coil-kt:coil:2.6.0")

// Jetpack Compose (se usar composable)
implementation(platform("androidx.compose:compose-bom:2024.02.00"))
implementation("androidx.compose.ui:ui")
implementation("androidx.compose.material3:material3")
implementation("androidx.activity:activity-compose:1.8.2")
```

---

## Telas do app e o que cada uma deve exibir

### Tela 1. Configuracao / Ativacao

Mostrada quando o app nao tem token salvo ou recebe `forceReconfigure=true`.

Elementos obrigatorios:
- campo exibindo o `code` recebido da API em fonte grande e legivel na TV
- contagem regressiva mostrando quantos segundos restam para o codigo expirar
- mensagem orientando o usuario a acessar o painel admin e digitar o codigo
- estado de loading enquanto faz polling
- botao ou comportamento automatico de renovar codigo quando expirar

Comportamento:
- ao abrir a tela, imediatamente chamar `POST /api/tv/activation-code`
- iniciar polling em `POST /api/tv/check-activation` a cada 4 segundos
- ao receber `status=activated`, salvar token e navegar para tela principal
- ao expirar sem ativacao, chamar novamente `activation-code` e renovar o codigo exibido

### Tela 2. Lista de Produtos (tela principal da TV)

Mostrada depois da ativacao e durante o uso normal.

Elementos obrigatorios:
- lista de produtos vinda de `GET /api/tv/produtos`
- para cada produto exibir: nome, preco, oferta (se `oferta != false`) e imagem (se existir)
- nome e preco em fonte grande para visualizacao a distancia
- separadores por grupo e departamento se o dado vier agrupado
- indicador visual de preco promocional quando `oferta` for maior que zero

Comportamento:
- atualizar automaticamente a lista em intervalo definido por `configuracao.atualizar_produtos_segundos` recebido no `bootstrap`
- se `total_produtos = 0`, exibir mensagem de lista vazia
- se resposta vier com `forceReconfigure=true`, navegar para tela de configuracao

### Tela 3. Slide de Ofertas (opcional, ativado pelo config)

Exibida em tela cheia periodicamente se `offerSlideEnabled=true` na config.

- buscar dados de `GET /api/tv/ofertas`
- exibir cada produto em oferta em slideshow com intervalo `offerSlideIntervalSeconds`
- retornar para lista de produtos apos o ciclo

---

## Data classes Kotlin

### Armazenamento local

```kotlin
// Chaves do DataStore
object PrefsKeys {
    val DEVICE_UUID = stringPreferencesKey("device_uuid")
    val TV_TOKEN = stringPreferencesKey("tv_device_token")
    val TV_LAST_TOKEN = stringPreferencesKey("tv_last_device_token")
}
```

### Requisicoes e respostas da API

```kotlin
data class ActivationCodeRequest(
    val device_uuid: String
)

data class ActivationCodeResponse(
    val code: String,
    val expires_in: Int
)

data class CheckActivationRequest(
    val device_uuid: String
)

data class CheckActivationResponse(
    val status: String,      // "pending" ou "activated"
    val token: String?       // presente somente quando status = "activated"
)

data class HeartbeatResponse(
    val status: String,
    val last_seen_at: String?
)

data class BootstrapResponse(
    val status: String,
    val device: DeviceInfo,
    val empresa: EmpresaInfo,
    val configuracao: ConfiguracaoInfo
)

data class DeviceInfo(
    val id: Int,
    val nome: String,
    val local: String?,
    val empresa_id: Int,
    val last_seen_at: String?
)

data class EmpresaInfo(
    val id: Int,
    val nome: String,
    val cnpj_cpf: String?
)

data class ConfiguracaoInfo(
    val id: Int,
    val atualizar_produtos_segundos: Int,
    val volume: Int,
    val orientacao: String
)
```

### Produto da TV

O campo `oferta` na rota `GET /api/tv/produtos` pode ser um numero float OU `false` (booleano).
Isso exige um deserializador customizado no Gson.

```kotlin
data class TvProduto(
    val id: Int,
    val codigo: String,
    val nome: String,
    val preco: Double,
    @JsonAdapter(OfertaDeserializer::class)
    val oferta: Double?,    // null quando a API enviar false
    val imagem: String?,
    val grupo: GrupoInfo?,
    val departamento: DepartamentoInfo?
)

data class GrupoInfo(
    val id: Int,
    val nome: String
)

data class DepartamentoInfo(
    val id: Int,
    val nome: String
)

data class TvProdutosResponse(
    val success: Boolean,
    val reason: String?,
    val forceReconfigure: Boolean?,
    val data: TvProdutosData?,
    val meta: TvProdutosMeta?
)

data class TvProdutosData(
    val produtos: List<TvProduto>
)

data class TvProdutosMeta(
    val total_produtos: Int
)
```

### Deserializador customizado para o campo `oferta`

Obrigatorio porque a API envia `false` (boolean) quando nao ha oferta, e um numero quando ha.

```kotlin
class OfertaDeserializer : JsonDeserializer<Double?> {
    override fun deserialize(
        json: JsonElement,
        typeOfT: Type,
        context: JsonDeserializationContext
    ): Double? {
        return if (json.isJsonPrimitive) {
            val primitive = json.asJsonPrimitive
            when {
                primitive.isBoolean -> null   // false vira null
                primitive.isNumber -> primitive.asDouble
                else -> null
            }
        } else {
            null
        }
    }
}
```

---

## Interface Retrofit completa

```kotlin
interface TvApiService {

    @POST("tv/activation-code")
    suspend fun activationCode(
        @Body body: ActivationCodeRequest
    ): ActivationCodeResponse

    @POST("tv/check-activation")
    suspend fun checkActivation(
        @Body body: CheckActivationRequest
    ): CheckActivationResponse

    @POST("tv/heartbeat")
    suspend fun heartbeat(
        @Header("Authorization") bearer: String
    ): HeartbeatResponse

    @GET("tv/bootstrap")
    suspend fun bootstrap(
        @Header("Authorization") bearer: String
    ): BootstrapResponse

    @GET("tv/produtos")
    suspend fun produtos(
        @Header("Authorization") bearer: String
    ): TvProdutosResponse

    @GET("tv/ofertas")
    suspend fun ofertas(
        @Header("Authorization") bearer: String
    ): OfertasResponse

    @GET("tv/midias")
    suspend fun midias(
        @Header("Authorization") bearer: String
    ): MidiasResponse

    @GET("tv/totemweb/config")
    suspend fun webConfig(
        @Header("Authorization") bearer: String
    ): Response<WebConfigResponse>
}
```

### Instancia do Retrofit

```kotlin
object RetrofitInstance {

    private const val BASE_URL = "https://app.tabeladeprecodigital.com.br/api/"

  private val gson = GsonBuilder().create()

    private val okHttpClient = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(20, TimeUnit.SECONDS)
        .writeTimeout(15, TimeUnit.SECONDS)
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        })
        .build()

    val api: TvApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(TvApiService::class.java)
    }
}
```

---

## Logica de refresh automatico de produtos

O app deve usar o campo `atualizar_produtos_segundos` recebido no `bootstrap` como intervalo de atualizacao da lista de produtos.

```kotlin
// No ViewModel, apos receber bootstrap
val intervalSegundos = bootstrapResponse.configuracao.atualizar_produtos_segundos

// Iniciar coroutine que atualiza produtos periodicamente
viewModelScope.launch {
    while (isActive) {
        fetchProdutos()
        delay(intervalSegundos * 1000L)
    }
}
```

Regras:
- o intervalo padrao quando o bootstrap nao estiver disponivel e 30 segundos
- nao cancelar o loop em erros de rede; continuar tentando
- ao receber `forceReconfigure=true`, cancelar o loop e navegar para tela de ativacao

---

## Heartbeat em background com WorkManager

O heartbeat precisa continuar funcionando enquanto o app estiver ativo e, se necessario, tambem quando estiver em background.

Observacao importante:

- `PeriodicWorkRequest` do WorkManager nao e adequado para intervalo real de 60 segundos
- para heartbeat frequente, o app deve preferir coroutine controlada pelo ciclo de vida, foreground service quando necessario, ou aceitar um intervalo maior no WorkManager
- se a exigencia for heartbeat estrito de 60 segundos, nao documentar isso como `PeriodicWorkRequest` simples

```kotlin
class HeartbeatWorker(
    context: Context,
    params: WorkerParameters
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val prefs = applicationContext.dataStore.data.first()
        val token = prefs[PrefsKeys.TV_TOKEN] ?: return Result.failure()

        return try {
            RetrofitInstance.api.heartbeat("Bearer $token")
            Result.success()
        } catch (e: Exception) {
            Result.retry()
        }
    }
}

// Agendamento (chamar na inicializacao do app apos ativacao)
fun scheduleHeartbeat(context: Context) {
    val request = PeriodicWorkRequestBuilder<HeartbeatWorker>(
    15, TimeUnit.MINUTES
    )
        .setConstraints(
            Constraints.Builder()
                .setRequiredNetworkType(NetworkType.CONNECTED)
                .build()
        )
        .build()

    WorkManager.getInstance(context).enqueueUniquePeriodicWork(
        "tv_heartbeat",
        ExistingPeriodicWorkPolicy.KEEP,
        request
    )
}
```

---

## AndroidManifest - permissoes e configuracoes necessarias

```xml
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
  xmlns:tools="http://schemas.android.com/tools">

    <!-- Acesso a internet -->
    <uses-permission android:name="android.permission.INTERNET" />

    <!-- Iniciar automaticamente com o dispositivo -->
    <uses-permission android:name="android.permission.RECEIVE_BOOT_COMPLETED" />

    <application
        android:name=".App"
        android:usesCleartextTraffic="true"
        android:theme="@style/Theme.AppCompat.NoActionBar">

        <activity
            android:name=".MainActivity"
            android:exported="true"
            android:screenOrientation="landscape"
            android:configChanges="orientation|screenSize|keyboardHidden"
            android:windowSoftInputMode="stateHidden|adjustNothing">

            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>

        <!-- Receiver para iniciar o app no boot -->
        <receiver
            android:name=".BootReceiver"
            android:enabled="true"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.BOOT_COMPLETED" />
            </intent-filter>
        </receiver>

        <!-- WorkManager para heartbeat -->
        <provider
            android:name="androidx.startup.InitializationProvider"
            android:authorities="${applicationId}.androidx-startup"
            android:exported="false"
            tools:node="merge">
            <meta-data
                android:name="androidx.work.WorkManagerInitializer"
                android:value="androidx.startup" />
        </provider>

    </application>
</manifest>
```

---

## Modo imersivo / kiosk na TV

O app deve rodar em tela cheia sem barra de status nem barra de navegacao.

```kotlin
// Aplicar no onCreate da MainActivity e ao retomar o app
fun Activity.enableImmersiveMode() {
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
        window.insetsController?.let {
            it.hide(WindowInsets.Type.statusBars() or WindowInsets.Type.navigationBars())
            it.systemBarsBehavior =
                WindowInsetsController.BEHAVIOR_SHOW_TRANSIENT_BARS_BY_SWIPE
        }
    } else {
        @Suppress("DEPRECATION")
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_FULLSCREEN
            or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
            or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
            or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
        )
    }
}
```

Chamar tambem em `onWindowFocusChanged`:

```kotlin
override fun onWindowFocusChanged(hasFocus: Boolean) {
    super.onWindowFocusChanged(hasFocus)
    if (hasFocus) enableImmersiveMode()
}
```

---

## Auto-start no boot do dispositivo

```kotlin
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == Intent.ACTION_BOOT_COMPLETED) {
            val appIntent = Intent(context, MainActivity::class.java).apply {
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(appIntent)
        }
    }
}
```

---

## Logica de entrada do app (MainActivity)

```kotlin
class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableImmersiveMode()
        setContent {
            AppNavigation()
        }
    }

    override fun onWindowFocusChanged(hasFocus: Boolean) {
        super.onWindowFocusChanged(hasFocus)
        if (hasFocus) enableImmersiveMode()
    }
}

// Navigacao inicial baseada no token salvo
@Composable
fun AppNavigation() {
    val context = LocalContext.current
    val token by context.dataStore.data
        .map { it[PrefsKeys.TV_TOKEN] }
        .collectAsState(initial = null)

    when {
        token == null -> ActivationScreen()
        else -> MainProductScreen(token = token!!)
    }
}
```

---

## Geracao do device_uuid

```kotlin
suspend fun getOrCreateDeviceUuid(context: Context): String {
    val prefs = context.dataStore.data.first()
    val existing = prefs[PrefsKeys.DEVICE_UUID]
    if (existing != null) return existing

    val newUuid = UUID.randomUUID().toString()
    context.dataStore.edit { it[PrefsKeys.DEVICE_UUID] = newUuid }
    return newUuid
}
```

---

## Orientacao da tela

- o app deve rodar **sempre em landscape** (horizontalmente)
- fixar `android:screenOrientation="landscape"` na Activity no manifest
- nao depender de rotacao automatica do dispositivo
- o campo `orientacao` retornado pelo `bootstrap` pode ser usado para ajustes futuros de layout

## Checklist final para a IA que vai construir o app Android nativo

Antes de escrever codigo, a IA deve assumir estas premissas como obrigatorias:

- o app Android deve reproduzir o comportamento da tela web `/tv/totemweb`, e nao inventar um fluxo novo
- a mesma API ja usada pelo web deve ser reutilizada
- a principal fonte de layout remoto e `GET /api/tv/totemweb/config`
- os campos Android devem ter prioridade sobre os campos genericos equivalentes
- quando o backend nao fornecer campo Android especifico, usar fallback do campo generico
- o app precisa tratar produtos, ofertas, videos, imagens, fullscreen e agenda por plataforma
- o app precisa manter `device_uuid`, `tv_device_token` e opcionalmente `tv_last_device_token`
- erros temporarios de rede nao devem destruir estado local
- reconfiguracao forcada deve voltar para a tela de ativacao
- o app TV kiosk nao deve implementar CRUD administrativo como parte do fluxo principal

Se a IA for construir renderer 100% nativo, ela precisa portar a logica visual do frontend web para Kotlin. Se for construir um shell Android com WebView kiosk, o reaproveitamento e maior, mas a dependencia do frontend web tambem aumenta.