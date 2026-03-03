#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8000/api}"
CNPJ_A="${CNPJ_A:-}"
SENHA_A="${SENHA_A:-}"
CNPJ_B="${CNPJ_B:-}"
SENHA_B="${SENHA_B:-}"

if [[ -z "$CNPJ_A" || -z "$SENHA_A" || -z "$CNPJ_B" || -z "$SENHA_B" ]]; then
  cat <<'EOF'
Uso:
  export BASE_URL="http://localhost:8000/api"
  export CNPJ_A="11111111000111"
  export SENHA_A="senhaA"
  export CNPJ_B="22222222000122"
  export SENHA_B="senhaB"
  ./scripts/test_multiempresa_api.sh
EOF
  exit 1
fi

API_STATUS=""
API_BODY=""

call_api() {
  local method="$1"
  local endpoint="$2"
  local token="${3:-}"
  local data="${4:-}"

  local -a args
  args=( -sS -X "$method" -H "Accept: application/json" )

  if [[ -n "$token" ]]; then
    args+=( -H "Authorization: Bearer $token" )
  fi

  if [[ -n "$data" ]]; then
    args+=( -H "Content-Type: application/json" -d "$data" )
  fi

  local response
  response=$(curl "${args[@]}" "$BASE_URL$endpoint" -w $'\n%{http_code}')
  API_BODY="${response%$'\n'*}"
  API_STATUS="${response##*$'\n'}"
}

json_key() {
  local key="$1"
  printf '%s' "$API_BODY" | php -r '$d=json_decode(stream_get_contents(STDIN), true); $k=$argv[1]; echo (is_array($d) && array_key_exists($k,$d)) ? $d[$k] : "";' "$key"
}

json_path() {
  local path="$1"
  printf '%s' "$API_BODY" | php -r '
    $d=json_decode(stream_get_contents(STDIN), true);
    $path=explode(".", $argv[1]);
    $cur=$d;
    foreach($path as $p){
      if(is_array($cur) && array_key_exists($p,$cur)) { $cur=$cur[$p]; }
      else { $cur=""; break; }
    }
    if (is_array($cur)) echo json_encode($cur);
    else echo (string)$cur;
  ' "$path"
}

fail() {
  echo "❌ $1"
  echo "Status: $API_STATUS"
  echo "Body: $API_BODY"
  exit 1
}

expect_status() {
  local expected="$1"
  local ctx="$2"
  if [[ "$API_STATUS" != "$expected" ]]; then
    fail "$ctx"
  fi
}

login_empresa() {
  local cnpj="$1"
  local senha="$2"
  local label="$3"

  call_api "POST" "/login" "" "{\"cnpj\":\"$cnpj\",\"senha\":\"$senha\"}"
  expect_status "200" "Falha no login da empresa $label"

  local token
  token="$(json_key token)"
  if [[ -z "$token" ]]; then
    fail "Token vazio no login da empresa $label"
  fi

  echo "$token"
}

echo "==> Login empresas"
TOKEN_A="$(login_empresa "$CNPJ_A" "$SENHA_A" "A")"
TOKEN_B="$(login_empresa "$CNPJ_B" "$SENHA_B" "B")"

echo "==> Criando dados empresa A"
STAMP_A="A$(date +%H%M%S)"
DEPT_A="DEP_${STAMP_A}"
GRP_A="GRP_${STAMP_A}"
COD_A="A$(date +%m%d%H%M%S)"

call_api "POST" "/departamentos" "$TOKEN_A" "{\"nome\":\"$DEPT_A\"}"
expect_status "201" "Falha ao criar departamento da empresa A"
DEP_A_ID="$(json_path dados.id)"

call_api "POST" "/grupos" "$TOKEN_A" "{\"nome\":\"$GRP_A\",\"departamento_id\":$DEP_A_ID}"
expect_status "201" "Falha ao criar grupo da empresa A"
GRP_A_ID="$(json_path dados.id)"

call_api "POST" "/produtos" "$TOKEN_A" "{\"CODIGO\":\"$COD_A\",\"NOME\":\"Produto $STAMP_A\",\"PRECO\":10.5,\"OFERTA\":9.9,\"departamento_id\":$DEP_A_ID,\"grupo_id\":$GRP_A_ID}"
expect_status "201" "Falha ao criar produto da empresa A"

echo "==> Criando dados empresa B"
STAMP_B="B$(date +%H%M%S)"
DEPT_B="DEP_${STAMP_B}"
GRP_B="GRP_${STAMP_B}"
COD_B="B$(date +%m%d%H%M%S)"

call_api "POST" "/departamentos" "$TOKEN_B" "{\"nome\":\"$DEPT_B\"}"
expect_status "201" "Falha ao criar departamento da empresa B"
DEP_B_ID="$(json_path dados.id)"

call_api "POST" "/grupos" "$TOKEN_B" "{\"nome\":\"$GRP_B\",\"departamento_id\":$DEP_B_ID}"
expect_status "201" "Falha ao criar grupo da empresa B"
GRP_B_ID="$(json_path dados.id)"

call_api "POST" "/produtos" "$TOKEN_B" "{\"CODIGO\":\"$COD_B\",\"NOME\":\"Produto $STAMP_B\",\"PRECO\":20.5,\"OFERTA\":19.9,\"departamento_id\":$DEP_B_ID,\"grupo_id\":$GRP_B_ID}"
expect_status "201" "Falha ao criar produto da empresa B"

echo "==> Validando isolamento por empresa_id"
call_api "GET" "/produtos" "$TOKEN_A"
expect_status "200" "Falha ao listar produtos da empresa A"
A_LIST="$API_BODY"

call_api "GET" "/produtos" "$TOKEN_B"
expect_status "200" "Falha ao listar produtos da empresa B"
B_LIST="$API_BODY"

if ! printf '%s' "$A_LIST" | grep -q "\"codigo\":\"$COD_A\""; then
  API_BODY="$A_LIST"; API_STATUS="200"
  fail "Produto da empresa A não encontrado na própria listagem"
fi

if printf '%s' "$A_LIST" | grep -q "\"codigo\":\"$COD_B\""; then
  API_BODY="$A_LIST"; API_STATUS="200"
  fail "Vazamento: produto da empresa B apareceu na listagem da empresa A"
fi

if ! printf '%s' "$B_LIST" | grep -q "\"codigo\":\"$COD_B\""; then
  API_BODY="$B_LIST"; API_STATUS="200"
  fail "Produto da empresa B não encontrado na própria listagem"
fi

if printf '%s' "$B_LIST" | grep -q "\"codigo\":\"$COD_A\""; then
  API_BODY="$B_LIST"; API_STATUS="200"
  fail "Vazamento: produto da empresa A apareceu na listagem da empresa B"
fi

echo "✅ Teste concluído com sucesso"
echo "- Empresa A vê apenas seus dados"
echo "- Empresa B vê apenas seus dados"
echo "- Isolamento multiempresa por token + empresa_id validado"
