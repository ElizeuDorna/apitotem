export function createRightSidebarProductCardRendererModule(deps) {
    const api = {};

    api.renderSidebarProductItem = function renderSidebarProductItem(item) {
        const card = deps.ensureSidebarProductCard();
        if (!card || !item) {
            return;
        }

        const showName = deps.toBoolean(deps.visualConfig.rightSidebarProductShowName, true);
        const showPrice = deps.toBoolean(deps.visualConfig.rightSidebarProductShowPrice, true);
        const showImage = deps.toBoolean(deps.visualConfig.rightSidebarProductShowImage, true);
        const namePosition = String(deps.visualConfig.rightSidebarProductNamePosition || 'top').toLowerCase() === 'bottom' ? 'bottom' : 'top';
        const pricePosition = String(deps.visualConfig.rightSidebarProductPricePosition || 'bottom').toLowerCase() === 'top' ? 'top' : 'bottom';

        const nome = String(item?.nome || 'Produto').trim() || 'Produto';
        const precoNormal = Number(item?.preco || 0);
        const oferta = Number(item?.oferta || 0);
        const hasOferta = Number.isFinite(oferta) && oferta > 0;
        const valorFinal = hasOferta ? oferta : precoNormal;
        const precoTexto = Number.isFinite(valorFinal)
            ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(valorFinal)
            : '-';
        const imagem = String(item?.imagem || '').trim();

        const nameBadgeBackground = deps.toBoolean(deps.visualConfig.rightSidebarProductNameBadgeEnabled, true)
            ? String(deps.visualConfig.rightSidebarProductNameBadgeColor || '#0F172A')
            : 'transparent';
        const priceBadgeBackground = deps.toBoolean(deps.visualConfig.rightSidebarProductPriceBadgeEnabled, true)
            ? String(deps.visualConfig.rightSidebarProductPriceBadgeColor || '#0F172A')
            : 'transparent';

        const nameBlock = showName
            ? `<div style="padding:6px 8px;border-radius:8px;background:${nameBadgeBackground};color:${String(deps.visualConfig.rightSidebarProductNameColor || '#FFFFFF')};font-weight:700;text-align:center;line-height:1.2;word-break:break-word;">${nome}</div>`
            : '';

        const priceBlock = showPrice
            ? `<div style="padding:6px 8px;border-radius:8px;background:${priceBadgeBackground};color:${String(deps.visualConfig.rightSidebarProductPriceColor || '#FDE68A')};font-weight:800;text-align:center;line-height:1.2;">${precoTexto}</div>`
            : '';

        const imagemSrc = deps.normalizeImageSrcCandidate(deps.resolveRenderableImageUrl(imagem) || imagem);
        const imagemFallbackCandidates = deps.buildImageFallbackCandidates(imagem, imagemSrc);
        const imagemFallbackPrimary = String(imagemFallbackCandidates[0] || '');
        const imagemFallbackSecondary = String(imagemFallbackCandidates[1] || '');

        const imageBlock = showImage && imagem !== ''
            ? `<div style="flex:1;display:flex;align-items:center;justify-content:center;min-height:96px;"><img src="${deps.escapeHtmlAttribute(imagemSrc || imagem)}" data-fallback-src="${deps.escapeHtmlAttribute(imagemFallbackPrimary)}" data-fallback-src-2="${deps.escapeHtmlAttribute(imagemFallbackSecondary)}" alt="${deps.escapeHtmlAttribute(nome)}" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;" loading="lazy" onerror="if(this.dataset.fallbackSrc&&this.dataset.fallbackTried!=='1'){this.dataset.fallbackTried='1';this.src=this.dataset.fallbackSrc;return;}if(this.dataset.fallbackSrc2&&this.dataset.fallbackTried2!=='1'){this.dataset.fallbackTried2='1';this.src=this.dataset.fallbackSrc2;return;}this.style.display='none'"/></div>`
            : '<div style="flex:1"></div>';

        const topBlock = namePosition === 'top' ? nameBlock : (pricePosition === 'top' ? priceBlock : '');
        const bottomBlock = pricePosition === 'bottom' ? priceBlock : (namePosition === 'bottom' ? nameBlock : '');

        card.innerHTML = `${topBlock}${imageBlock}${bottomBlock}`;
        card.style.display = 'flex';
        card.classList.remove('hidden');
    };

    return api;
}
