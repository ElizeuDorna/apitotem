# Auditoria de Schema no aaPanel

Este arquivo resume os comandos para atualizar o projeto no servidor aaPanel e rodar a auditoria de tabelas/colunas possivelmente sem uso.

## 1. Entrar na pasta do projeto

```bash
cd /caminho/do/projeto
```

Exemplo comum no aaPanel:

```bash
cd /www/wwwroot/api
```

## 2. Atualizar o código no servidor

```bash
git pull origin main
```

## 3. Limpar caches do Laravel

```bash
php artisan optimize:clear
```

## 4. Confirmar que o comando existe

```bash
php artisan list | grep audit:schema-usage
```

Se aparecer `audit:schema-usage`, o comando está disponível.

## 5. Rodar a auditoria no terminal

```bash
php artisan audit:schema-usage
```

## 6. Rodar mostrando mais referências por item

```bash
php artisan audit:schema-usage --limit=10
```

## 7. Salvar relatório em arquivo JSON

```bash
php artisan audit:schema-usage --save --limit=10
```

O relatório será salvo em:

```bash
storage/app/audits/
```

## 8. Mostrar saída em JSON no terminal

```bash
php artisan audit:schema-usage --json
```

## 9. Incluir tabelas internas do Laravel/framework

```bash
php artisan audit:schema-usage --include-framework --limit=10
```

## 10. Fluxo recomendado

Rodar nesta ordem:

```bash
cd /caminho/do/projeto
git pull origin main
php artisan optimize:clear
php artisan audit:schema-usage --save --limit=10
```

## 11. Atenção

Esse comando não apaga nada.

Ele apenas aponta:

- tabelas possivelmente órfãs
- colunas possivelmente sem uso
- arquivos onde encontrou referência

Antes de remover qualquer tabela ou coluna, confirme também:

- uso em integrações externas
- cron jobs do aaPanel
- scripts shell fora do Laravel
- views/triggers/procedures do MySQL
- relatórios manuais