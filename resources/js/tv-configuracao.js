const form = document.getElementById('tvConfigForm');
const tokenInput = document.getElementById('deviceToken');
const endpointInput = document.getElementById('apiEndpoint');
const refreshInput = document.getElementById('refreshSeconds');
const clientCodeInput = document.getElementById('clientCode');
const deviceUuidInput = document.getElementById('deviceUuid');
const statusText = document.getElementById('configStatus');
const clearButton = document.getElementById('clearTvConfig');
const generateClientCodeButton = document.getElementById('generateClientCode');
const tokenHistoryList = document.getElementById('tokenHistoryList');

const TOKEN_HISTORY_KEY = 'tv_device_token_history_v1';
const TOKEN_BACKUP_KEY = 'tv_device_token_backup';
const TOKEN_PRIMARY_KEY = 'tv_device_token';
const TOKEN_LAST_KEY = 'tv_last_device_token';
const DEFAULT_PRODUCTS_ENDPOINT = '/api/tv/produtos';
const DEFAULT_REFRESH_SECONDS = '30';
const CLIENT_CODE_KEY = 'tv_client_code';
const DEVICE_UUID_KEY = 'tv_device_uuid';
const TV_CONFIG_ENDPOINT = '/api/tv/totemweb/config';
const TV_PAGE_URL = '/tv/totemweb';

function createUuidV4() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = Math.floor(Math.random() * 16);
        const value = char === 'x' ? random : (random & 0x3) | 0x8;
        return value.toString(16);
    });
}

function getDeviceUuid() {
    const stored = String(localStorage.getItem(DEVICE_UUID_KEY) || '').trim();
    if (stored) {
        return stored;
    }

    const created = createUuidV4();
    localStorage.setItem(DEVICE_UUID_KEY, created);
    return created;
}

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
    if (deviceUuidInput) {
        deviceUuidInput.value = getDeviceUuid();
    }
    if (clientCodeInput) {
        clientCodeInput.value = String(localStorage.getItem(CLIENT_CODE_KEY) || '').trim();
    }
    if (tokenInput.value) {
        persistTokenAcrossKeys(tokenInput.value);
    }
    renderTokenHistory();
}

async function validateTokenAgainstApi(token) {
    const response = await fetch(TV_CONFIG_ENDPOINT, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (response.ok) {
        return { valid: true };
    }

    if (response.status === 401) {
        const reason = String(payload?.reason || '').toLowerCase();

        if (reason === 'device_inactive') {
            return { valid: false, message: 'Token encontrado, mas a TV esta desativada no admin.' };
        }

        if (reason === 'device_not_found') {
            return { valid: false, message: 'Token nao encontrado. Gere um novo codigo e ative novamente.' };
        }

        if (reason === 'token_missing') {
            return { valid: false, message: 'Token nao informado.' };
        }

        return { valid: false, message: 'Token invalido para esta TV.' };
    }

    return {
        valid: true,
        warning: 'Nao foi possivel validar o token agora. Redirecionando para a TV para nova tentativa.',
    };
}

async function saveSettings(event) {
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

    setStatus('Validando token e salvando configuracoes...');
    renderTokenHistory();

    try {
        const validation = await validateTokenAgainstApi(token);

        if (!validation.valid) {
            setStatus(validation.message || 'Token invalido para esta TV.', true);
            return;
        }

        if (validation.warning) {
            setStatus(validation.warning);
        } else {
            setStatus('Configuracoes salvas. Redirecionando para a TV...');
        }

        window.location.assign(TV_PAGE_URL);
    } catch (_error) {
        setStatus('Falha momentanea na validacao. Redirecionando para a TV...');
        window.location.assign(TV_PAGE_URL);
    }
}

function clearSettings() {
    [
        TOKEN_PRIMARY_KEY,
        TOKEN_LAST_KEY,
        TOKEN_BACKUP_KEY,
        TOKEN_HISTORY_KEY,
        'tv_api_endpoint',
        'tv_refresh_seconds',
        CLIENT_CODE_KEY,
    ].forEach((key) => localStorage.removeItem(key));

    loadSettings();
    setStatus('Configurações limpas. Informe um novo token para usar a tela.');
}

async function generateClientCode() {
    const button = generateClientCodeButton;
    const deviceUuid = getDeviceUuid();

    if (deviceUuidInput) {
        deviceUuidInput.value = deviceUuid;
    }

    if (!deviceUuid) {
        setStatus('Nao foi possivel identificar este dispositivo.', true);
        return;
    }

    try {
        if (button) {
            button.disabled = true;
            button.classList.add('opacity-60', 'cursor-not-allowed');
        }

        setStatus('Gerando codigo de cliente...');

        const response = await fetch('/api/tv/activation-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                device_uuid: deviceUuid,
            }),
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload?.message || 'Falha ao gerar codigo de cliente.');
        }

        const code = String(payload?.code || '').trim();
        if (!code) {
            throw new Error('Resposta da API sem codigo de cliente.');
        }

        localStorage.setItem(CLIENT_CODE_KEY, code);
        if (clientCodeInput) {
            clientCodeInput.value = code;
        }

        setStatus('Codigo de cliente gerado com sucesso.');
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Falha ao gerar codigo de cliente.';
        setStatus(message, true);
    } finally {
        if (button) {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    }
}

if (form) {
    loadSettings();
    form.addEventListener('submit', saveSettings);
}

if (clearButton) {
    clearButton.addEventListener('click', clearSettings);
}

if (generateClientCodeButton) {
    generateClientCodeButton.addEventListener('click', generateClientCode);
}

window.addEventListener('offline', () => {
    setStatus('Sem internet no momento. Historico e token local continuam salvos.', true);
});

window.addEventListener('online', () => {
    setStatus('Internet restabelecida. Voce pode voltar para a tela da TV.');
});
