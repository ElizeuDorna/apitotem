const form = document.getElementById('tvConfigForm');
const tokenInput = document.getElementById('deviceToken');
const endpointInput = document.getElementById('apiEndpoint');
const refreshInput = document.getElementById('refreshSeconds');
const statusText = document.getElementById('configStatus');
const clearButton = document.getElementById('clearTvConfig');
const tokenHistoryList = document.getElementById('tokenHistoryList');

const TOKEN_HISTORY_KEY = 'tv_device_token_history_v1';
const TOKEN_BACKUP_KEY = 'tv_device_token_backup';
const TOKEN_PRIMARY_KEY = 'tv_device_token';
const TOKEN_LAST_KEY = 'tv_last_device_token';
const DEFAULT_PRODUCTS_ENDPOINT = '/api/tv/produtos';
const DEFAULT_REFRESH_SECONDS = '30';

function setStatus(message, isError = false) {
    if (!statusText) {
        return;
    }

    statusText.textContent = message;
    statusText.className = `text-xs ${isError ? 'text-red-400' : 'text-slate-400'}`;
}

function normalizeToken(value) {
    return String(value || '').trim();
}

function readTokenHistory() {
    try {
        const raw = localStorage.getItem(TOKEN_HISTORY_KEY);
        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map((item) => normalizeToken(item))
            .filter((item) => item !== '');
    } catch (_error) {
        return [];
    }
}

function saveTokenHistory(history) {
    try {
        localStorage.setItem(TOKEN_HISTORY_KEY, JSON.stringify(history));
    } catch (_error) {
    }
}

function rememberToken(token) {
    const normalized = normalizeToken(token);
    if (!normalized) {
        return;
    }

    const history = readTokenHistory().filter((item) => item !== normalized);
    history.unshift(normalized);
    saveTokenHistory(history.slice(0, 10));
}

function maskToken(token) {
    const normalized = normalizeToken(token);
    if (normalized.length <= 10) {
        return normalized;
    }

    return `${normalized.slice(0, 6)}...${normalized.slice(-4)}`;
}

function resolveStoredToken() {
    const fromPrimary = normalizeToken(localStorage.getItem(TOKEN_PRIMARY_KEY));
    const fromLast = normalizeToken(localStorage.getItem(TOKEN_LAST_KEY));
    const fromBackup = normalizeToken(localStorage.getItem(TOKEN_BACKUP_KEY));
    const fromHistory = readTokenHistory()[0] || '';

    return fromPrimary || fromLast || fromBackup || fromHistory;
}

function persistTokenAcrossKeys(token) {
    const normalized = normalizeToken(token);
    if (!normalized) {
        return;
    }

    localStorage.setItem(TOKEN_PRIMARY_KEY, normalized);
    localStorage.setItem(TOKEN_LAST_KEY, normalized);
    localStorage.setItem(TOKEN_BACKUP_KEY, normalized);
    rememberToken(normalized);
}

function renderTokenHistory() {
    if (!tokenHistoryList) {
        return;
    }

    const history = readTokenHistory();
    tokenHistoryList.innerHTML = '';

    if (history.length === 0) {
        const item = document.createElement('p');
        item.className = 'text-xs text-slate-500';
        item.textContent = 'Nenhum token salvo ainda.';
        tokenHistoryList.appendChild(item);
        return;
    }

    history.forEach((token) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rounded-md border border-slate-700 px-2 py-1 text-xs text-slate-200 hover:bg-slate-800';
        button.textContent = maskToken(token);
        button.title = token;
        button.addEventListener('click', () => {
            if (!tokenInput) {
                return;
            }

            tokenInput.value = token;
            persistTokenAcrossKeys(token);
            setStatus('Token restaurado do historico. Clique em Salvar para confirmar.');
        });
        tokenHistoryList.appendChild(button);
    });
}

function loadSettings() {
    if (!tokenInput || !endpointInput || !refreshInput) {
        return;
    }

    tokenInput.value = resolveStoredToken();
    endpointInput.value = localStorage.getItem('tv_api_endpoint') || DEFAULT_PRODUCTS_ENDPOINT;
    refreshInput.value = localStorage.getItem('tv_refresh_seconds') || DEFAULT_REFRESH_SECONDS;
    if (tokenInput.value) {
        persistTokenAcrossKeys(tokenInput.value);
    }
    renderTokenHistory();
}

function saveSettings(event) {
    event.preventDefault();

    const token = tokenInput.value.trim();
    if (!tokenInput || !endpointInput || !refreshInput) {
        return;
    }

    const endpoint = endpointInput.value.trim() || DEFAULT_PRODUCTS_ENDPOINT;
    const refresh = Number(refreshInput.value || 30);

    if (!token) {
        setStatus('Informe o token da TV.', true);
        return;
    }

    if (Number.isNaN(refresh) || refresh < 5 || refresh > 3600) {
        setStatus('Atualização deve ficar entre 5 e 3600 segundos.', true);
        return;
    }

    persistTokenAcrossKeys(token);
    localStorage.setItem('tv_api_endpoint', endpoint);
    localStorage.setItem('tv_refresh_seconds', String(refresh));

    setStatus('Configurações salvas com sucesso.');
    renderTokenHistory();
}

function clearSettings() {
    [
        TOKEN_PRIMARY_KEY,
        TOKEN_LAST_KEY,
        TOKEN_BACKUP_KEY,
        TOKEN_HISTORY_KEY,
        'tv_api_endpoint',
        'tv_refresh_seconds',
    ].forEach((key) => localStorage.removeItem(key));

    loadSettings();
    setStatus('Configurações limpas. Informe um novo token para usar a tela.');
}

if (form) {
    loadSettings();
    form.addEventListener('submit', saveSettings);
}

if (clearButton) {
    clearButton.addEventListener('click', clearSettings);
}

window.addEventListener('offline', () => {
    setStatus('Sem internet no momento. Historico e token local continuam salvos.', true);
});

window.addEventListener('online', () => {
    setStatus('Internet restabelecida. Voce pode voltar para a tela da TV.');
});
