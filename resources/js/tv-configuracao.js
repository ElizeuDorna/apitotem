const form = document.getElementById('tvConfigForm');
const tokenInput = document.getElementById('deviceToken');
const endpointInput = document.getElementById('apiEndpoint');
const refreshInput = document.getElementById('refreshSeconds');
const statusText = document.getElementById('configStatus');
const clearButton = document.getElementById('clearTvConfig');

function setStatus(message, isError = false) {
    if (!statusText) {
        return;
    }

    statusText.textContent = message;
    statusText.className = `text-xs ${isError ? 'text-red-400' : 'text-slate-400'}`;
}

function loadSettings() {
    tokenInput.value = localStorage.getItem('tv_device_token') || '';
    endpointInput.value = localStorage.getItem('tv_api_endpoint') || '/api/tv/produtos';
    refreshInput.value = localStorage.getItem('tv_refresh_seconds') || '30';
}

function saveSettings(event) {
    event.preventDefault();

    const token = tokenInput.value.trim();
    const endpoint = endpointInput.value.trim() || '/api/tv/produtos';
    const refresh = Number(refreshInput.value || 30);

    if (!token) {
        setStatus('Informe o token da TV.', true);
        return;
    }

    if (Number.isNaN(refresh) || refresh < 5 || refresh > 3600) {
        setStatus('Atualização deve ficar entre 5 e 3600 segundos.', true);
        return;
    }

    localStorage.setItem('tv_device_token', token);
    localStorage.setItem('tv_api_endpoint', endpoint);
    localStorage.setItem('tv_refresh_seconds', String(refresh));

    setStatus('Configurações salvas com sucesso.');
}

function clearSettings() {
    localStorage.removeItem('tv_device_token');
    localStorage.removeItem('tv_api_endpoint');
    localStorage.removeItem('tv_refresh_seconds');

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
