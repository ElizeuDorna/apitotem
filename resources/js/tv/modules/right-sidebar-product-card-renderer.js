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
        const configuredImageWidth = Math.max(0, Math.min(1000, Number(deps.visualConfig.rightSidebarProductImageWidth || 0) || 0));
        const configuredImageHeight = Math.max(0, Math.min(1000, Number(deps.visualConfig.rightSidebarProductImageHeight || 0) || 0));
        const configuredNameFontSize = Math.max(8, Math.min(120, Number(deps.visualConfig.rightSidebarProductNameFontSize || 16) || 16));
        const configuredPriceFontSize = Math.max(8, Math.min(120, Number(deps.visualConfig.rightSidebarProductPriceFontSize || 16) || 16));
        const configuredNameFontFamily = deps.resolveTitleFontFamily(String(deps.visualConfig.rightSidebarProductNameFontFamily || 'arial'));
        const configuredPriceFontFamily = deps.resolveTitleFontFamily(String(deps.visualConfig.rightSidebarProductPriceFontFamily || 'arial'));

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
            ? `<div style="padding:6px 8px;border-radius:8px;background:${nameBadgeBackground};color:${String(deps.visualConfig.rightSidebarProductNameColor || '#FFFFFF')};font-family:${deps.escapeHtmlAttribute(configuredNameFontFamily)};font-weight:700;font-size:${configuredNameFontSize}px;text-align:center;line-height:1.2;word-break:break-word;">${nome}</div>`
            : '';

        const priceBlock = showPrice
            ? `<div style="padding:6px 8px;border-radius:8px;background:${priceBadgeBackground};color:${String(deps.visualConfig.rightSidebarProductPriceColor || '#FDE68A')};font-family:${deps.escapeHtmlAttribute(configuredPriceFontFamily)};font-weight:800;font-size:${configuredPriceFontSize}px;text-align:center;line-height:1.2;">${precoTexto}</div>`
            : '';

        const imagemSrc = deps.normalizeImageSrcCandidate(deps.resolveRenderableImageUrl(imagem) || imagem);
        const imagemFallbackCandidates = deps.buildImageFallbackCandidates(imagem, imagemSrc);
        const imagemFallbackPrimary = String(imagemFallbackCandidates[0] || '');
        const imagemFallbackSecondary = String(imagemFallbackCandidates[1] || '');
        const imageStyle = [
            'object-fit:contain',
            'border-radius:8px',
            configuredImageWidth > 0 ? `width:${configuredImageWidth}px` : 'width:auto',
            configuredImageHeight > 0 ? `height:${configuredImageHeight}px` : 'height:auto',
            configuredImageWidth > 0 ? `max-width:${configuredImageWidth}px` : 'max-width:100%',
            configuredImageHeight > 0 ? `max-height:${configuredImageHeight}px` : 'max-height:100%',
        ].join(';');
        const imageWrapMinHeight = configuredImageHeight > 0 ? Math.max(96, configuredImageHeight) : 96;

        const imageBlock = showImage && imagem !== ''
            ? `<div style="flex:1;display:flex;align-items:center;justify-content:center;min-height:${imageWrapMinHeight}px;"><img src="${deps.escapeHtmlAttribute(imagemSrc || imagem)}" data-fallback-src="${deps.escapeHtmlAttribute(imagemFallbackPrimary)}" data-fallback-src-2="${deps.escapeHtmlAttribute(imagemFallbackSecondary)}" alt="${deps.escapeHtmlAttribute(nome)}" style="${imageStyle}" loading="lazy" onerror="if(this.dataset.fallbackSrc&&this.dataset.fallbackTried!=='1'){this.dataset.fallbackTried='1';this.src=this.dataset.fallbackSrc;return;}if(this.dataset.fallbackSrc2&&this.dataset.fallbackTried2!=='1'){this.dataset.fallbackTried2='1';this.src=this.dataset.fallbackSrc2;return;}this.style.display='none'"/></div>`
            : '<div style="flex:1"></div>';

        const topBlock = namePosition === 'top' ? nameBlock : (pricePosition === 'top' ? priceBlock : '');
        const bottomBlock = pricePosition === 'bottom' ? priceBlock : (namePosition === 'bottom' ? nameBlock : '');

        card.innerHTML = `${topBlock}${imageBlock}${bottomBlock}`;
        card.style.display = 'flex';
        card.classList.remove('hidden');
    };

    return api;
}
