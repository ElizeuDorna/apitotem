# Plano de Assinatura por Empresa para Bloqueio de TVs

## Objetivo

Implementar um modelo de expiracao comercial baseado na empresa, e nao no usuario, para controlar:

- periodo de trial, por exemplo 7 dias
- expiracao de acesso ao sistema
- bloqueio das TVs vinculadas a empresa
- futura renovacao de plano sem reconfigurar devices

Esse modelo deve servir tanto para:

- cadastro manual por admin ou revenda
- cadastro futuro self-service
- bloqueio comercial por falta de pagamento
- desbloqueio apos renovacao

## Decisao principal

A fonte da verdade do acesso deve ser a empresa.

Nao usar o usuario como gatilho principal para expirar o sistema.

Motivos:

- uma empresa pode ter varios usuarios
- uma empresa pode ter varias TVs
- o produto contratado pertence a empresa, nao a um usuario especifico
- expirar um usuario nao representa corretamente o estado comercial da empresa

## Regra de negocio proposta

1. A empresa possui um status comercial proprio.
2. As TVs da empresa so funcionam se a empresa estiver com acesso liberado.
3. Usuarios do painel podem ou nao continuar acessando partes administrativas quando a empresa expirar, conforme regra futura.
4. O bloqueio da TV deve ocorrer pelo status da empresa, nao apenas por `device.ativo`.

## Modelo recomendado

Criar uma camada propria de assinatura/licenca da empresa.

Opcao preferida:

- tabela dedicada, por exemplo `empresa_subscriptions`

Campos sugeridos:

- `id`
- `empresa_id`
- `status` com valores como `trial`, `active`, `expired`, `blocked`, `cancelled`
- `starts_at`
- `trial_ends_at`
- `access_expires_at`
- `grace_ends_at`
- `blocked_at`
- `blocked_reason`
- `plan_name`
- `created_at`
- `updated_at`

Observacao:

- manter `empresa.ativo` separado como kill switch operacional/manual
- nao misturar expiração comercial com bloqueio tecnico ou administrativo

## Fluxo desejado para trial

1. Usuario cria conta.
2. Empresa e criada ou vinculada.
3. Assinatura da empresa nasce em `trial`.
4. `trial_ends_at = now() + 7 dias`.
5. As TVs vinculadas a empresa funcionam normalmente durante o trial.
6. Ao vencer o trial, a API da TV passa a bloquear a empresa.
7. Ao renovar, a empresa volta ao estado `active` e as TVs retomam o funcionamento.

## Onde aplicar a regra

### 1. Painel administrativo

Criar um servico central, por exemplo:

- `EmpresaAccessService`
- ou `EmpresaSubscriptionService`

Esse servico deve responder perguntas como:

- a empresa pode usar o painel?
- a empresa pode usar as TVs?
- esta em trial?
- esta expirada?
- esta em carencia?
- qual o motivo do bloqueio?

### 2. API da TV

Hoje a validacao da TV depende principalmente de:

- token do device
- `device.ativo`

No futuro, acrescentar validacao comercial da empresa antes de liberar:

- heartbeat
- bootstrap
- produtos
- ofertas
- configuracao da tela web
- quaisquer endpoints autenticados da TV

## Comportamento recomendado da TV quando a empresa expirar

Nao usar erro de token invalido nem `forceReconfigure=true` para esse caso.

Melhor retorno:

- HTTP `403`
- `reason = subscription_expired`
- `message = assinatura expirada` ou equivalente

Com isso a TV pode exibir uma tela de bloqueio comercial, sem perder ativacao e sem exigir novo pareamento do device.

## Abordagem recomendada para os devices

### Melhor abordagem

Bloqueio dinamico por empresa na API.

Vantagens:

- nao precisa desligar device por device no banco
- reativacao apos renovacao e imediata
- menos risco de estado inconsistente

### Abordagem secundaria opcional

Job que sincroniza `device.ativo = false` para empresas expiradas.

Nao usar isso como regra principal.

## Usuario x Empresa

### Regra principal

- a empresa controla o acesso comercial

### Regra secundaria

- usuario pode ter regras proprias de login, mas isso nao substitui assinatura da empresa

### Recomendacao

- admin global pode continuar sem empresa vinculada
- usuarios operacionais devem continuar com `empresa_id` obrigatorio

## Fases sugeridas de implementacao

### Fase 1 - Base de dados

1. Criar migration para `empresa_subscriptions`.
2. Criar model da assinatura.
3. Criar relacionamento em `Empresa`.

### Fase 2 - Regra central

1. Criar servico central de acesso comercial da empresa.
2. Criar metodos como:
   - `canUseTv(Empresa $empresa)`
   - `canUseAdmin(Empresa $empresa)`
   - `currentStatus(Empresa $empresa)`
   - `isExpired(Empresa $empresa)`

### Fase 3 - Integracao com TV

1. Aplicar a validacao do servico nos endpoints da TV.
2. Retornar erro especifico de assinatura expirada.
3. Garantir que a TV mostre bloqueio comercial sem perder o device token.

### Fase 4 - Painel

1. Mostrar status comercial da empresa no painel.
2. Criar telas ou campos para trial, expiracao e renovacao.
3. Decidir se painel continua parcial ou totalmente bloqueado quando a empresa expira.

### Fase 5 - Self-service futuro

1. No cadastro publico ou onboarding, criar empresa + usuario + assinatura trial.
2. Definir trial automatico de 7 dias.
3. Preparar pontos de integracao com pagamento ou troca de plano.

## Testes que deverao existir

1. Empresa em trial com TVs funcionando.
2. Empresa expirada com TV bloqueada.
3. Empresa renovada com TVs voltando a funcionar.
4. Empresa bloqueada manualmente.
5. Usuario ativo em empresa expirada nao deve contornar o bloqueio da TV.
6. Trial de 7 dias criado automaticamente no fluxo self-service.

## Observacoes importantes

- nao usar o status do usuario como base principal do produto contratado
- nao forcar reativacao manual de device quando o problema for apenas comercial
- manter a mensagem da TV clara para o cliente final
- centralizar a regra para evitar inconsistencias entre painel, API e devices

## Recomendacao final

Quando implementar, priorizar nesta ordem:

1. camada de assinatura/licenca da empresa
2. servico central de decisao
3. validacao na API da TV
4. status no painel
5. onboarding self-service com trial automatico

Esse plano foi registrado para retomada futura sem depender da memoria da conversa.