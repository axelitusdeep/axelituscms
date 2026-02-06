// Icons
const ICONS = {
    bolt: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
    layout: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
    clock: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    shield: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    chart: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    code: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    star: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    heart: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
    globe: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    users: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    settings: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    edit: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
    plus: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    trash: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>',
    copy: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
    eye: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    image: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    file: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    upload: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
    money: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
};

let draggedBlock = null;
let currentLinkCallback = null;
let previewTimeout = null;
let expandedBlockIndices = new Set();

// ========== MINI PREVIEW FUNCTIONS ==========

function setPreviewDevice(device) {
    const wrapper = document.getElementById('previewFrameWrapper');
    const buttons = document.querySelectorAll('.preview-device-btn');

    if (!wrapper) return;

    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.device === device) {
            btn.classList.add('active');
        }
    });

    wrapper.classList.remove('desktop', 'tablet', 'mobile');
    if (device !== 'desktop') {
        wrapper.classList.add(device);
    }
}

function refreshPreview() {
    const iframe = document.getElementById('previewFrame');
    if (iframe) {
        const wrapper = document.getElementById('previewFrameWrapper');
        if (wrapper) wrapper.classList.add('loading');

        iframe.src = iframe.src;

        iframe.onload = function () {
            if (wrapper) wrapper.classList.remove('loading');
        };
    }
}

function schedulePreviewUpdate() {
    if (previewTimeout) {
        clearTimeout(previewTimeout);
    }
    previewTimeout = setTimeout(function () {
        updateLivePreview();
    }, 1000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Render icon SVG
function renderPreviewIcon(icon) {
    return ICONS[icon] || ICONS['bolt'];
}

// Block renderers for live preview
function renderPreviewBlock(block) {
    const type = block.type || '';
    const data = block.data || {};
    const id = block.id ? ` id="${escapeHtml(block.id)}"` : '';

    switch (type) {
        case 'hero': return renderPreviewHero(data, id);
        case 'stats': return renderPreviewStats(data, id);
        case 'features': return renderPreviewFeatures(data, id);
        case 'testimonials': return renderPreviewTestimonials(data, id);
        case 'pricing': return renderPreviewPricing(data, id);
        case 'cta': return renderPreviewCta(data, id);
        case 'text': return renderPreviewText(data, id);
        case 'image': return renderPreviewImage(data, id);
        case 'image-text': return renderPreviewImageText(data, id);
        case 'product-cards': return renderPreviewProductCards(data, id);
        case 'video': return renderPreviewVideo(data, id);
        case 'gallery': return renderPreviewGallery(data, id);
        case 'faq': return renderPreviewFaq(data, id);
        case 'team': return renderPreviewTeam(data, id);
        case 'audio': return renderPreviewAudio(data, id);
        case 'countdown': return renderPreviewCountdown(data, id);
        case 'newsletter': return renderPreviewNewsletter(data, id);
        case 'html': return data.html || '';
        default: return '';
    }
}

function renderPreviewHero(d, id) {
    return `<section class="hero"${id}>
            <div class="hero-grid"></div>
            <div class="container hero-content">
                ${d.badge ? `<div class="badge"><span class="badge-dot"></span><span>${escapeHtml(d.badge)}</span></div>` : ''}
                <h1>${escapeHtml(d.title || '')}</h1>
                <p>${escapeHtml(d.subtitle || '')}</p>
                <div class="hero-buttons">
                    ${d.button_primary ? `<a href="${escapeHtml(d.button_primary_url || '#')}" class="btn btn-primary">${escapeHtml(d.button_primary)}</a>` : ''}
                    ${d.button_secondary ? `<a href="${escapeHtml(d.button_secondary_url || '#')}" class="btn btn-outline">${escapeHtml(d.button_secondary)}</a>` : ''}
                </div>
            </div>
        </section>`;
}

function renderPreviewStats(d, id) {
    const items = d.items || [];
    if (!items.length) return '';
    return `<section class="stats"${id}>
            <div class="container">
                <div class="stats-grid">
                    ${items.map(item => `<div class="stat-item"><h3>${escapeHtml(item.value || '')}</h3><p>${escapeHtml(item.label || '')}</p></div>`).join('')}
                </div>
            </div>
        </section>`;
}

function renderPreviewFeatures(d, id) {
    const items = d.items || [];
    return `<section class="features"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                ${items.length ? `<div class="features-grid">
                    ${items.map(item => `<div class="feature-card">
                        <div class="feature-icon">${renderPreviewIcon(item.icon || 'bolt')}</div>
                        <h3>${escapeHtml(item.title || '')}</h3>
                        <p>${escapeHtml(item.description || '')}</p>
                    </div>`).join('')}
                </div>` : ''}
            </div>
        </section>`;
}

function renderPreviewTestimonials(d, id) {
    const items = d.items || [];
    return `<section class="testimonials"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                ${items.length ? `<div class="testimonials-grid">
                    ${items.map(item => `<div class="testimonial-card">
                        <p>"${escapeHtml(item.quote || '')}"</p>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar">${escapeHtml(item.initials || '')}</div>
                            <div class="testimonial-info"><h4>${escapeHtml(item.name || '')}</h4><span>${escapeHtml(item.role || '')}</span></div>
                        </div>
                    </div>`).join('')}
                </div>` : ''}
            </div>
        </section>`;
}

function renderPreviewPricing(d, id) {
    const items = d.items || [];
    return `<section class="pricing"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                ${items.length ? `<div class="pricing-grid">
                    ${items.map(item => `<div class="pricing-card ${item.featured ? 'featured' : ''}">
                        ${item.featured ? '<span class="pricing-popular">Most Popular</span>' : ''}
                        <h3>${escapeHtml(item.name || '')}</h3>
                        <div class="price">${escapeHtml(item.price || '')}<span>${escapeHtml(item.period || '')}</span></div>
                        <p class="description">${escapeHtml(item.description || '')}</p>
                        ${item.features && item.features.length ? `<ul class="pricing-features">
                            ${item.features.map(f => `<li><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>${escapeHtml(f)}</li>`).join('')}
                        </ul>` : ''}
                        <a href="${escapeHtml(item.button_url || '#')}" class="btn ${item.featured ? 'btn-primary' : 'btn-outline'}">${escapeHtml(item.button_text || item.button || 'Get Started')}</a>
                    </div>`).join('')}
                </div>` : ''}
            </div>
        </section>`;
}

function renderPreviewCta(d, id) {
    return `<section class="cta"${id}>
            <div class="container">
                <div class="cta-inner">
                    <h2>${escapeHtml(d.title || '')}</h2>
                    <p>${escapeHtml(d.subtitle || '')}</p>
                    <div class="cta-buttons">
                        ${d.button_primary ? `<a href="${escapeHtml(d.button_primary_url || '#')}" class="btn btn-primary">${escapeHtml(d.button_primary)}</a>` : ''}
                        ${d.button_secondary ? `<a href="${escapeHtml(d.button_secondary_url || '#')}" class="btn btn-outline">${escapeHtml(d.button_secondary)}</a>` : ''}
                    </div>
                </div>
            </div>
        </section>`;
}

function renderPreviewText(d, id) {
    return `<section class="text-section"${id}>
            <div class="container">
                ${d.title ? `<h2>${escapeHtml(d.title)}</h2>` : ''}
                <div class="text-content">${escapeHtml(d.content || '').replace(/\n/g, '<br>')}</div>
            </div>
        </section>`;
}

function renderPreviewImage(d, id) {
    if (!d.url) return '';
    return `<section class="image-section"${id}>
            <div class="container">
                <figure>
                    <img src="${escapeHtml(d.url)}" alt="${escapeHtml(d.alt || '')}" loading="lazy">
                    ${d.caption ? `<figcaption>${escapeHtml(d.caption)}</figcaption>` : ''}
                </figure>
            </div>
        </section>`;
}

function renderPreviewImageText(d, id) {
    const imagePos = d.image_position || 'left';
    return `<section class="image-text"${id}>
            <div class="container">
                <div class="image-text-grid image-text-${escapeHtml(imagePos)}">
                    <div class="image-text-image">
                        <img src="${escapeHtml(d.image_url || '')}" alt="${escapeHtml(d.image_alt || '')}" loading="lazy">
                    </div>
                    <div class="image-text-content">
                        ${d.subtitle ? `<p class="section-subtitle">${escapeHtml(d.subtitle)}</p>` : ''}
                        ${d.title ? `<h2>${escapeHtml(d.title)}</h2>` : ''}
                        <div class="text-content">${escapeHtml(d.content || '').replace(/\n/g, '<br>')}</div>
                        ${d.button_text ? `<a href="${escapeHtml(d.button_url || '#')}" class="btn btn-primary">${escapeHtml(d.button_text)}</a>` : ''}
                    </div>
                </div>
            </div>
        </section>`;
}

function renderPreviewProductCards(d, id) {
    const products = d.products || [];
    if (!products.length) return '';
    return `<section class="product-cards"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="products-grid">
                    ${products.map(p => `<div class="product-card">
                        ${p.image ? `<div class="product-image"><img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.title || '')}" width="48" height="48" loading="lazy"></div>` : ''}
                        <h3>${escapeHtml(p.title || '')}</h3>
                        <p class="product-description">${escapeHtml(p.description || '')}</p>
                        ${p.features && p.features.length ? `<ul class="product-features">${p.features.map(f => `<li>${escapeHtml(f)}</li>`).join('')}</ul>` : ''}
                        ${p.button_text ? `<a href="${escapeHtml(p.button_url || '#')}" class="btn btn-primary btn-sm">${escapeHtml(p.button_text)}</a>` : ''}
                    </div>`).join('')}
                </div>
            </div>
        </section>`;
}

function renderPreviewVideo(d, id) {
    if (!d.url) return '';
    const url = d.url;
    const type = d.type || 'url';
    const poster = d.posterUrl ? escapeHtml(d.posterUrl) : '';
    let videoHtml = '';

    if (type === 'youtube' || url.match(/youtube\.com|youtu\.be/i)) {
        const videoId = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i);
        if (videoId && videoId[1]) {
            videoHtml = `<iframe src="https://www.youtube.com/embed/${escapeHtml(videoId[1])}?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>`;
        }
    } else if (type === 'facebook' || url.match(/facebook\.com|fb\.watch/i)) {
        videoHtml = `<iframe src="https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(url)}&show_text=false&autoplay=true" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>`;
    } else {
        videoHtml = `<video controls preload="metadata" ${poster ? `poster="${poster}"` : ''}><source src="${escapeHtml(url)}" type="video/mp4">Your browser does not support the video tag.</video>`;
    }

    const overlayHtml = (poster && type !== 'url')
        ? `<div class="video-overlay" style="background-image: url('${poster}');" onclick="this.remove()">
                <div class="play-button"></div>
            </div>`
        : '';

    return `<section class="video-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="video-wrapper" style="position: relative;">
                    ${overlayHtml}
                    ${videoHtml}
                </div>
                ${d.caption ? `<p class="video-caption">${escapeHtml(d.caption)}</p>` : ''}
            </div>
        </section>`;
}

function renderPreviewGallery(d, id) {
    const images = d.images || [];
    if (!images.length) return '';
    return `<section class="gallery-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="gallery-grid">
                    ${images.map(img => `<div class="gallery-item">
                        <img src="${escapeHtml(img.url || '')}" alt="${escapeHtml(img.alt || '')}" loading="lazy">
                        ${img.caption ? `<div class="gallery-caption">${escapeHtml(img.caption)}</div>` : ''}
                    </div>`).join('')}
                </div>
            </div>
        </section>`;
}

function renderPreviewFaq(d, id) {
    const items = d.items || [];
    if (!items.length) return '';
    return `<section class="faq-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="faq-list">
                    ${items.map((item, idx) => `<details class="faq-item" ${idx === 0 ? 'open' : ''}>
                        <summary class="faq-question">${escapeHtml(item.question || '')}</summary>
                        <div class="faq-answer">${escapeHtml(item.answer || '').replace(/\n/g, '<br>')}</div>
                    </details>`).join('')}
                </div>
            </div>
        </section>`;
}

function renderPreviewTeam(d, id) {
    const members = d.members || [];
    if (!members.length) return '';
    return `<section class="team-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="team-grid">
                    ${members.map(m => `<div class="team-member">
                        ${m.image ? `<img src="${escapeHtml(m.image)}" alt="${escapeHtml(m.name || '')}" class="team-photo" loading="lazy">` : `<div class="team-avatar">${escapeHtml(m.initials || (m.name || '?').substring(0, 2).toUpperCase())}</div>`}
                        <h3>${escapeHtml(m.name || '')}</h3>
                        <p class="team-role">${escapeHtml(m.role || '')}</p>
                        ${m.bio ? `<p class="team-bio">${escapeHtml(m.bio)}</p>` : ''}
                        ${m.social && m.social.length ? `<div class="team-social">
                            ${m.social.map(s => `<a href="${escapeHtml(s.url || '#')}" target="_blank" rel="noopener" class="team-social-link" title="${escapeHtml(s.platform || '')}">
                                ${s.platform === 'twitter' ? '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>' : ''}
                                ${s.platform === 'linkedin' ? '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>' : ''}
                                ${s.platform === 'github' ? '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>' : ''}
                            </a>`).join('')}
                        </div>` : ''}
                    </div>`).join('')}
                </div>
            </div>
        </section>`;
}

function renderPreviewAudio(d, id) {
    if (!d.url) return '';
    return `<section class="audio-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="audio-wrapper">
                    <audio controls preload="metadata" class="audio-player"><source src="${escapeHtml(d.url)}" type="audio/mpeg">Your browser does not support the audio element.</audio>
                    ${d.music_link ? `<a href="${escapeHtml(d.music_link)}" class="btn btn-outline" target="_blank" rel="noopener">${escapeHtml(d.music_link_text || 'Download')}</a>` : ''}
                </div>
            </div>
        </section>`;
}

function renderPreviewCountdown(d, id) {
    if (!d.target_date) return '';
    return `<section class="countdown-section"${id}>
            <div class="container">
                ${d.title ? `<div class="section-header"><h2>${escapeHtml(d.title)}</h2>${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}</div>` : ''}
                <div class="countdown-timer">
                    <div class="countdown-item"><div class="countdown-value">00</div><div class="countdown-label">Days</div></div>
                    <div class="countdown-item"><div class="countdown-value">00</div><div class="countdown-label">Hours</div></div>
                    <div class="countdown-item"><div class="countdown-value">00</div><div class="countdown-label">Minutes</div></div>
                    <div class="countdown-item"><div class="countdown-value">00</div><div class="countdown-label">Seconds</div></div>
                </div>
            </div>
        </section>`;
}

function renderPreviewNewsletter(d, id) {
    return `<section class="newsletter-section"${id}>
            <div class="container">
                <div class="newsletter-inner">
                    ${d.title ? `<h2>${escapeHtml(d.title)}</h2>` : ''}
                    ${d.subtitle ? `<p>${escapeHtml(d.subtitle)}</p>` : ''}
                    <form class="newsletter-form" onsubmit="event.preventDefault();">
                        <input type="email" class="newsletter-input" placeholder="${escapeHtml(d.placeholder || 'Enter your email')}" required>
                        <button type="submit" class="btn btn-primary">${escapeHtml(d.button_text || 'Subscribe')}</button>
                    </form>
                </div>
            </div>
        </section>`;
}

function updateLivePreview() {
    const iframe = document.getElementById('previewFrame');
    if (!iframe) return;

    const wrapper = document.getElementById('previewFrameWrapper');
    if (wrapper) wrapper.classList.add('loading');

    const blocksHtml = blocks.map(block => renderPreviewBlock(block)).join('');

    const websiteFont = siteSettings.website_font || 'Inter';
    const fontUrls = {
        'Inter': '/assets/fonts/inter/inter.css',
        'Arial': '',
        'Helvetica': '',
        'Times New Roman': '',
        'Courier New': '',
        'Verdana': '',
        'Trebuchet MS': ''
    };
    const fontUrl = fontUrls[websiteFont] || fontUrls['Inter'];

    const fullHtml = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview</title>
    <link rel="preload" href="/assets/fonts/inter/inter.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    ${fontUrl ? `<link href="${fontUrl}" rel="stylesheet">` : ''}
    <link rel="stylesheet" href="/css/styles.css?v=${Date.now()}">
    <link rel="stylesheet" href="/css/theme.css?v=${Date.now()}">
    <style>
        body { font-family: '${websiteFont}', -apple-system, BlinkMacSystemFont, sans-serif; }
        /* Disable links in preview */
        a { pointer-events: none; }
    </style>
    <script src="/assets/js/admin-theme.js"><\/script>
</head>
<body>
    <main>${blocksHtml}</main>
</body>
</html>`;

    iframe.srcdoc = fullHtml
        .replace(/\[u\](.*?)\[\/u\]/gi, '<u>$1</u>')
        .replace(/\[b\](.*?)\[\/b\]/gi, '<b>$1</b>')
        .replace(/\[i\](.*?)\[\/i\]/gi, '<i>$1</i>')
        .replace(/\[s\](.*?)\[\/s\]/gi, '<del>$1</del>')
        .replace(/\[quote\](.*?)\[\/quote\]/gi, '<blockquote>$1</blockquote>'
        );

    iframe.onload = function () {
        if (wrapper) wrapper.classList.remove('loading');
    };
}


if (!footerData.columns) footerData.columns = [];
if (!footerData.social_links) footerData.social_links = [];
if (!footerData.bottom_links) footerData.bottom_links = [];

// Initialize
document.addEventListener('DOMContentLoaded', function () {
    renderBlocks();

    setTimeout(updateLivePreview, 100);
    renderBlocks();
    renderBlocks();

    const previewFrameEdit = document.getElementById('previewFrame');
    if (previewFrameEdit) {
        const wrapperEdit = document.getElementById('previewFrameWrapper');
        if (wrapperEdit) wrapperEdit.classList.add('loading');
        previewFrameEdit.onload = function () {
            if (wrapperEdit) wrapperEdit.classList.remove('loading');
        };
    }
    renderNavLinks();
    renderNavButtons();
    renderFooterColumns();
    renderSocialLinks();
    renderBottomLinks();
    renderInternalPages();

    updateBlockCount();

    // Settings tabs
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.tab)?.classList.add('active');
        });
    });
});

document.getElementById('pageForm')?.addEventListener('submit', function (e) {
    document.getElementById('blocksData').value = JSON.stringify(blocks);
});

document.getElementById('settingsForm')?.addEventListener('submit', function (e) {
    document.getElementById('navLinksData').value = JSON.stringify(navLinks);
    document.getElementById('navButtonsData').value = JSON.stringify(navButtons);
    document.getElementById('footerData').value = JSON.stringify(footerData);
});

// ========== BLOCK EDITOR ==========

function generateId() {
    return 'block-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
}

function updateBlockCount() {
    const countEl = document.getElementById('blockCount');
    if (countEl) countEl.textContent = blocks.length;
}

function getUniqueBlockId(baseId, currentIndex) {
    if (!baseId || baseId.trim() === '') return '';

    const cleanId = baseId.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');

    let counter = 2;
    let finalId = cleanId;

    blocks.forEach((block, index) => {
        if (index !== currentIndex) {
            if (block.id === finalId) {

                while (blocks.some((b, i) => i !== currentIndex && b.id === `${cleanId}-${counter}`)) {
                    counter++;
                }
                finalId = `${cleanId}-${counter}`;
            } else if (block.id === cleanId && counter === 2) {

            }
        }
    });

    return finalId;
}

function renderBlocks() {
    const container = document.getElementById('blocksContainer');
    if (!container) return;

    const blockTypes = {};
    blocks.forEach((block) => {
        if (!block.id) {
            const type = block.type;
            if (!blockTypes[type]) {
                blockTypes[type] = [];
            }
            blockTypes[type].push(block);
        }
    });

    Object.keys(blockTypes).forEach(type => {
        const existingIds = blocks
            .filter(b => b.type === type && b.id)
            .map(b => b.id);

        blockTypes[type].forEach((block) => {
            let counter = 1;
            let newId = type;

            if (existingIds.includes(newId)) {
                counter = 2;
                while (existingIds.includes(`${type}-${counter}`)) {
                    counter++;
                }
                newId = `${type}-${counter}`;
            }

            block.id = newId;
            existingIds.push(newId);
        });
    });

    blocks.forEach((block, index) => {
        if (block.id) {
            block.id = getUniqueBlockId(block.id, index);
        }
    });

    const expandedItems = document.querySelectorAll('.block-item.expanded');
    expandedBlockIndices.clear();
    expandedItems.forEach(item => {
        const index = item.dataset.index;
        if (index !== undefined) expandedBlockIndices.add(parseInt(index));
    });

    container.innerHTML = '';

    blocks.forEach((block, index) => {
        const el = createBlockElement(block, index);
        container.appendChild(el);

        if (expandedBlockIndices.has(index)) {
            el.classList.add('expanded');
        }
    });

    updateBlockCount();
}

function createBlockElement(block, index) {
    const div = document.createElement('div');
    div.className = 'block-item';
    div.dataset.index = index;
    div.draggable = false;

    div.addEventListener('dragover', handleDragOver);
    div.addEventListener('drop', handleDrop);
    div.addEventListener('dragleave', handleDragLeave);

    const title = getBlockTitle(block);

    div.innerHTML = `
        <div class="block-header" onclick="toggleBlock(${index})">
            <div class="block-drag" draggable="true" onmousedown="event.stopPropagation()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
            </div>
            <span class="block-type">${block.type}</span>
            <span class="block-title">${escapeHtml(title)}</span>
            <input type="text" class="block-id-input" value="${escapeHtml(block.id || block.type)}" onmousedown="event.stopPropagation()" onclick="event.stopPropagation()" oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, ''); blocks[${index}].id = this.value;" onblur="renderBlocks();" style="max-width: 120px; font-size: 0.8125rem;">
            <div class="block-actions">
                <button type="button" class="block-toggle" onclick="event.stopPropagation(); moveBlockUp(${index})" title="Move Up">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
                </button>
                <button type="button" class="block-toggle" onclick="event.stopPropagation(); moveBlockDown(${index})" title="Move Down">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <button type="button" class="block-toggle" onclick="event.stopPropagation(); duplicateBlock(${index})" title="Duplicate">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </button>
                <button type="button" class="block-toggle" onclick="event.stopPropagation(); deleteBlock(${index})" title="Delete" style="color: var(--error);">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </button>
                <button type="button" class="block-toggle block-toggle-arrow">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>
        </div>
        <div class="block-content">
            ${renderBlockFields(block, index)}
        </div>
    `;

    setTimeout(() => {
        const dragHandle = div.querySelector('.block-drag');
        if (dragHandle) {
            dragHandle.addEventListener('dragstart', function (e) {
                e.stopPropagation();
                draggedBlock = div;
                div.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            dragHandle.addEventListener('dragend', function (e) {
                e.stopPropagation();
                div.classList.remove('dragging');
                document.querySelectorAll('.block-item').forEach(el => el.classList.remove('drag-over'));
                draggedBlock = null;
            });
        }
    }, 0);

    return div;
}


function getBlockTitle(block) {
    switch (block.type) {
        case 'hero': return block.data?.title || 'Hero Section';
        case 'stats': return 'Statistics';
        case 'features': return block.data?.title || 'Features';
        case 'testimonials': return block.data?.title || 'Testimonials';
        case 'pricing': return block.data?.title || 'Pricing';
        case 'cta': return block.data?.title || 'Call to Action';
        case 'text': return block.data?.title || 'Text Block';
        case 'image': return block.data?.alt || 'Image';
        case 'image-text': return block.data?.title || 'Image + Text';
        case 'product-cards': return block.data?.title || 'Product Cards';
        case 'video': return block.data?.title || 'Video';
        case 'gallery': return block.data?.title || 'Gallery';
        case 'faq': return block.data?.title || 'FAQ';
        case 'team': return block.data?.title || 'Team';
        case 'audio': return block.data?.title || 'Audio Player';
        case 'countdown': return block.data?.title || 'Countdown Timer';
        case 'newsletter': return block.data?.title || 'Newsletter';
        case 'html': return 'Custom HTML';
        default: return block.type;
    }
}

function renderBlockFields(block, blockIndex) {
    switch (block.type) {
        case 'hero': {
            const badgeVal = escapeHtml(block.data?.badge || '');
            const titleVal = escapeHtml(block.data?.title || '');
            const subtitleVal = escapeHtml(block.data?.subtitle || '');
            const btn1Val = escapeHtml(block.data?.button_primary || '');
            const btn1Url = block.data?.button_primary_url || '';
            const btn2Val = escapeHtml(block.data?.button_secondary || '');
            const btn2Url = block.data?.button_secondary_url || '';
            return '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Badge Text</label>' +
                '<input type="text" class="block-field-input" value="' + badgeVal + '" oninput="updateBlockData(' + blockIndex + ', \'badge\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Title</label>' +
                '<input type="text" class="block-field-input" value="' + titleVal + '" oninput="updateBlockData(' + blockIndex + ', \'title\', this.value)">' +
                '</div>' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Subtitle</label>' +
                '<textarea class="block-field-input block-field-textarea" oninput="updateBlockData(' + blockIndex + ', \'subtitle\', this.value)">' + subtitleVal + '</textarea>' +
                '</div>' +
                '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Primary Button Text</label>' +
                '<input type="text" class="block-field-input" value="' + btn1Val + '" oninput="updateBlockData(' + blockIndex + ', \'button_primary\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Primary Button URL</label>' +
                renderLinkInput(btn1Url, 'updateBlockData(' + blockIndex + ', \'button_primary_url\', VALUE)') +
                '</div>' +
                '</div>' +
                '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Secondary Button Text</label>' +
                '<input type="text" class="block-field-input" value="' + btn2Val + '" oninput="updateBlockData(' + blockIndex + ', \'button_secondary\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Secondary Button URL</label>' +
                renderLinkInput(btn2Url, 'updateBlockData(' + blockIndex + ', \'button_secondary_url\', VALUE)') +
                '</div>' +
                '</div>';
        }

        case 'stats':
            return `
                    <div class="block-field">
                        <label class="block-field-label">Stats Items</label>
                        <div class="repeater-items" id="stats-items-${blockIndex}">
                            ${(block.data?.items || []).map((item, i) => `
                                <div class="repeater-item expanded">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.value || 'New Stat')} - ${escapeHtml(item.label || '')}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'items', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Value</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.value || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'value', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Label</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.label || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'label', this.value)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'items', {value: '', label: ''})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Stat
                        </button>
                    </div>
                `;

        case 'features':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Feature Items</label>
                        <div class="repeater-items" id="features-items-${blockIndex}">
                            ${(block.data?.items || []).map((item, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.title || 'New Feature')}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'items', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="block-field">
                                            <label class="block-field-label">Icon</label>
                                            <div class="icon-select">
                                                ${Object.keys(ICONS).map(icon => `
                                                    <div class="icon-option ${item.icon === icon ? 'selected' : ''}" onclick="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'icon', '${icon}'); renderBlocks();">
                                                        ${ICONS[icon]}
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Title</label>
                                            <input type="text" class="block-field-input" value="${escapeHtml(item.title || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'title', this.value)">
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Description</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'description', this.value)">${escapeHtml(item.description || '')}</textarea>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'items', {icon: 'bolt', title: '', description: ''})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Feature
                        </button>
                    </div>
                `;

        case 'testimonials':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Testimonials</label>
                        <div class="repeater-items" id="testimonials-items-${blockIndex}">
                            ${(block.data?.items || []).map((item, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.name || 'New Testimonial')}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'items', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="block-field">
                                            <label class="block-field-label">Quote</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'quote', this.value)">${escapeHtml(item.quote || '')}</textarea>
                                        </div>
                                        <div class="form-row-3">
                                            <div class="block-field">
                                                <label class="block-field-label">Name</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.name || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'name', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Role</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.role || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'role', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Initials</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.initials || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'initials', this.value)" maxlength="2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'items', {quote: '', name: '', role: '', initials: ''})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Testimonial
                        </button>
                    </div>
                `;

        case 'pricing': {
            const titleVal = escapeHtml(block.data?.title || '');
            const subtitleVal = escapeHtml(block.data?.subtitle || '');
            let itemsHtml = '';
            (block.data?.items || []).forEach((item, i) => {
                const nameVal = escapeHtml(item.name || 'New Plan');
                const priceVal = escapeHtml(item.price || '');
                const descVal = escapeHtml(item.description || '');
                const periodVal = escapeHtml(item.period || '');
                const btnTextVal = escapeHtml(item.button_text || item.button || '');
                const btnUrlVal = item.button_url || '#';
                const featVal = item.featured ? 'checked' : '';
                itemsHtml += '<div class="repeater-item">' +
                    '<div class="repeater-item-header" onclick="this.parentElement.classList.toggle(\'expanded\')">' +
                    '<span class="repeater-item-title">' + nameVal + ' - ' + priceVal + '</span>' +
                    '<div class="repeater-item-actions">' +
                    '<button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(' + blockIndex + ', \'items\', ' + i + ')" style="color: var(--error);">Remove</button>' +
                    '</div>' +
                    '</div>' +
                    '<div class="repeater-item-content">' +
                    '<div class="form-row-3">' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Plan Name</label>' +
                    '<input type="text" class="block-field-input" value="' + nameVal + '" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'name\', this.value)">' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Price</label>' +
                    '<input type="text" class="block-field-input" value="' + priceVal + '" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'price\', this.value)">' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Period</label>' +
                    '<input type="text" class="block-field-input" value="' + periodVal + '" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'period\', this.value)" placeholder="/month">' +
                    '</div>' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Description</label>' +
                    '<input type="text" class="block-field-input" value="' + descVal + '" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'description\', this.value)">' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Features (one per line)</label>' +
                    '<textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'features\', this.value.split(\'\\n\').filter(f => f.trim()))">' + (item.features || []).join('\n') + '</textarea>' +
                    '</div>' +
                    '<div class="form-row">' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Button Text</label>' +
                    '<input type="text" class="block-field-input" value="' + btnTextVal + '" oninput="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'button_text\', this.value)">' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label">Button URL</label>' +
                    renderLinkInput(btnUrlVal, 'updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'button_url\', VALUE)') +
                    '</div>' +
                    '</div>' +
                    '<div class="block-field">' +
                    '<label class="block-field-label" style="display: flex; align-items: center; gap: 8px;">' +
                    '<input type="checkbox" ' + featVal + ' onchange="updateRepeaterItem(' + blockIndex + ', \'items\', ' + i + ', \'featured\', this.checked)">' +
                    'Featured Plan (highlighted)' +
                    '</label>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            });
            return '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Section Title</label>' +
                '<input type="text" class="block-field-input" value="' + titleVal + '" oninput="updateBlockData(' + blockIndex + ', \'title\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Section Subtitle</label>' +
                '<input type="text" class="block-field-input" value="' + subtitleVal + '" oninput="updateBlockData(' + blockIndex + ', \'subtitle\', this.value)">' +
                '</div>' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Pricing Plans</label>' +
                '<div class="repeater-items" id="pricing-items-' + blockIndex + '">' +
                itemsHtml +
                '</div>' +
                '<button type="button" class="repeater-add" onclick="addRepeaterItem(' + blockIndex + ', \'items\', {name: \'\', price: \'\', period: \'/month\', description: \'\', features: [], button_text: \'Get Started\', button_url: \'#\', featured: false})">' +
                '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>' +
                'Add Plan' +
                '</button>' +
                '</div>';
        }

        case 'cta': {
            const titleVal = escapeHtml(block.data?.title || '');
            const subtitleVal = escapeHtml(block.data?.subtitle || '');
            const btn1Val = escapeHtml(block.data?.button_primary || '');
            const btn1Url = block.data?.button_primary_url || '';
            const btn2Val = escapeHtml(block.data?.button_secondary || '');
            const btn2Url = block.data?.button_secondary_url || '';
            return '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Title</label>' +
                '<input type="text" class="block-field-input" value="' + titleVal + '" oninput="updateBlockData(' + blockIndex + ', \'title\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Subtitle</label>' +
                '<input type="text" class="block-field-input" value="' + subtitleVal + '" oninput="updateBlockData(' + blockIndex + ', \'subtitle\', this.value)">' +
                '</div>' +
                '</div>' +
                '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Primary Button Text</label>' +
                '<input type="text" class="block-field-input" value="' + btn1Val + '" oninput="updateBlockData(' + blockIndex + ', \'button_primary\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Primary Button URL</label>' +
                renderLinkInput(btn1Url, 'updateBlockData(' + blockIndex + ', \'button_primary_url\', VALUE)') +
                '</div>' +
                '</div>' +
                '<div class="form-row">' +
                '<div class="block-field">' +
                '<label class="block-field-label">Secondary Button Text</label>' +
                '<input type="text" class="block-field-input" value="' + btn2Val + '" oninput="updateBlockData(' + blockIndex + ', \'button_secondary\', this.value)">' +
                '</div>' +
                '<div class="block-field">' +
                '<label class="block-field-label">Secondary Button URL</label>' +
                renderLinkInput(btn2Url, 'updateBlockData(' + blockIndex + ', \'button_secondary_url\', VALUE)') +
                '</div>' +
                '</div>';
        }

        case 'text':
            return `
                    <div class="block-field">
                        <label class="block-field-label">Title (optional)</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Content</label>
                        <textarea class="block-field-input block-field-textarea" style="min-height: 200px;" oninput="updateBlockData(${blockIndex}, 'content', this.value)">${escapeHtml(block.data?.content || '')}</textarea>
                    </div>
                `;

        case 'image':
            return `
                    <div class="block-field">
                        <label class="block-field-label">Image URL</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.url || '')}" oninput="updateBlockData(${blockIndex}, 'url', this.value)" placeholder="https://...">
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Alt Text</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.alt || '')}" oninput="updateBlockData(${blockIndex}, 'alt', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Caption (optional)</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.caption || '')}" oninput="updateBlockData(${blockIndex}, 'caption', this.value)">
                        </div>
                    </div>
                `;

        case 'audio':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Audio File URL</label>
                        <input type="url" class="block-field-input" value="${escapeHtml(block.data?.url || '')}" oninput="updateBlockData(${blockIndex}, 'url', this.value)" placeholder="https://example.com/audio.mp3">
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Music Link URL (Optional)</label>
                            <input type="url" class="block-field-input" value="${escapeHtml(block.data?.music_link || '')}" oninput="updateBlockData(${blockIndex}, 'music_link', this.value)" placeholder="https://example.com/download">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Music Link Text</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.music_link_text || 'Download')}" oninput="updateBlockData(${blockIndex}, 'music_link_text', this.value)">
                        </div>
                    </div>
                `;

        case 'countdown':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Target Date</label>
                            <input type="date" class="block-field-input" value="${escapeHtml(block.data?.target_date || '')}" oninput="updateBlockData(${blockIndex}, 'target_date', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Target Time</label>
                            <input type="time" class="block-field-input" value="${escapeHtml(block.data?.target_time || '00:00')}" oninput="updateBlockData(${blockIndex}, 'target_time', this.value)">
                        </div>
                    </div>
                `;

        case 'newsletter':
            return `
                    <div class="block-field">
                        <label class="block-field-label">Title</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Subtitle</label>
                        <textarea class="block-field-input block-field-textarea" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">${escapeHtml(block.data?.subtitle || '')}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Input Placeholder</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.placeholder || '')}" oninput="updateBlockData(${blockIndex}, 'placeholder', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Button Text</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.button_text || '')}" oninput="updateBlockData(${blockIndex}, 'button_text', this.value)">
                        </div>
                    </div>
                `;

        case 'html':
            return `
                    <div class="block-field">
                        <label class="block-field-label">Custom HTML Code</label>
                        <textarea class="block-field-input block-field-textarea" style="min-height: 300px; font-family: ui-monospace, monospace; font-size: 0.8125rem;" oninput="updateBlockData(${blockIndex}, 'html', this.value)">${escapeHtml(block.data?.html || '')}</textarea>
                        <p style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 8px;">You can use any HTML, CSS (in style tags), and JavaScript (in script tags).</p>
                    </div>
                `;

        case 'image-text':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Image URL</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.image_url || '')}" oninput="updateBlockData(${blockIndex}, 'image_url', this.value)" placeholder="https://...">
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Image Alt Text</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.image_alt || '')}" oninput="updateBlockData(${blockIndex}, 'image_alt', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Image Position</label>
                            <select class="block-field-input" onchange="updateBlockData(${blockIndex}, 'image_position', this.value)">
                                <option value="left" ${block.data?.image_position === 'left' ? 'selected' : ''}>Left</option>
                                <option value="right" ${block.data?.image_position === 'right' ? 'selected' : ''}>Right</option>
                            </select>
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Content Text</label>
                        <textarea class="block-field-input block-field-textarea" style="min-height: 150px;" oninput="updateBlockData(${blockIndex}, 'content', this.value)">${escapeHtml(block.data?.content || '')}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Button Text (optional)</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.button_text || '')}" oninput="updateBlockData(${blockIndex}, 'button_text', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Button URL</label>
                            ${renderLinkInput(block.data?.button_url || '#', 'updateBlockData(' + blockIndex + ', \'button_url\', VALUE)')}
                        </div>
                    </div>
                `;

        case 'product-cards':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Products</label>
                        <div class="repeater-items" id="products-items-${blockIndex}">
                            ${(block.data?.products || []).map((item, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.title || 'New Product')}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'products', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Product Title</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.title || '')}" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'title', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Product Image (48x48)</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.image || '')}" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'image', this.value)" placeholder="https://...">
                                            </div>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Description</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'description', this.value)">${escapeHtml(item.description || '')}</textarea>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Features (one per line)</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'features', this.value.split('\\n').filter(f => f.trim()))">${(item.features || []).join('\\n')}</textarea>
                                        </div>
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Button Text</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.button_text || '')}" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'button_text', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Button URL</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.button_url || '#')}" oninput="updateRepeaterItem(${blockIndex}, 'products', ${i}, 'button_url', this.value)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'products', {title: '', image: '', description: '', features: [], button_text: 'View', button_url: '#'})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Product
                        </button>
                    </div>
                `;

        case 'video':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Video URL</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.url || '')}" oninput="updateBlockData(${blockIndex}, 'url', this.value)" placeholder="YouTube, Facebook, or direct video URL">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Video Type</label>
                            <select class="block-field-input" onchange="updateBlockData(${blockIndex}, 'type', this.value)">
                                <option value="url" ${block.data?.type === 'url' ? 'selected' : ''}>Direct URL</option>
                                <option value="youtube" ${block.data?.type === 'youtube' ? 'selected' : ''}>YouTube</option>
                                <option value="facebook" ${block.data?.type === 'facebook' ? 'selected' : ''}>Facebook</option>
                            </select>
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Poster Image URL (Thumbnail)</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.posterUrl || '')}" oninput="updateBlockData(${blockIndex}, 'posterUrl', this.value)" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Caption</label>
                        <input type="text" class="block-field-input" value="${escapeHtml(block.data?.caption || '')}" oninput="updateBlockData(${blockIndex}, 'caption', this.value)">
                    </div>
                `;

        case 'gallery':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Images</label>
                        <div class="repeater-items" id="images-items-${blockIndex}">
                            ${(block.data?.images || []).map((item, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.alt || 'Image ' + (i + 1))}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'images', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Image URL</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.url || '')}" oninput="updateRepeaterItem(${blockIndex}, 'images', ${i}, 'url', this.value)" placeholder="https://...">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Alt Text</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(item.alt || '')}" oninput="updateRepeaterItem(${blockIndex}, 'images', ${i}, 'alt', this.value)">
                                            </div>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Caption</label>
                                            <input type="text" class="block-field-input" value="${escapeHtml(item.caption || '')}" oninput="updateRepeaterItem(${blockIndex}, 'images', ${i}, 'caption', this.value)">
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'images', {url: '', alt: '', caption: ''})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Image
                        </button>
                    </div>
                `;

        case 'faq':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">FAQ Items</label>
                        <div class="repeater-items" id="items-items-${blockIndex}">
                            ${(block.data?.items || []).map((item, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(item.question || 'Question ' + (i + 1))}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'items', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="block-field">
                                            <label class="block-field-label">Question</label>
                                            <input type="text" class="block-field-input" value="${escapeHtml(item.question || '')}" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'question', this.value)">
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Answer</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'items', ${i}, 'answer', this.value)">${escapeHtml(item.answer || '')}</textarea>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'items', {question: '', answer: ''})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Question
                        </button>
                    </div>
                `;

        case 'team':
            return `
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Section Title</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.title || '')}" oninput="updateBlockData(${blockIndex}, 'title', this.value)">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Section Subtitle</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(block.data?.subtitle || '')}" oninput="updateBlockData(${blockIndex}, 'subtitle', this.value)">
                        </div>
                    </div>
                    <div class="block-field">
                        <label class="block-field-label">Team Members</label>
                        <div class="repeater-items" id="members-items-${blockIndex}">
                            ${(block.data?.members || []).map((member, i) => `
                                <div class="repeater-item">
                                    <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                                        <span class="repeater-item-title">${escapeHtml(member.name || 'Team Member ' + (i + 1))}</span>
                                        <div class="repeater-item-actions">
                                            <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeRepeaterItem(${blockIndex}, 'members', ${i})" style="color: var(--error);">Remove</button>
                                        </div>
                                    </div>
                                    <div class="repeater-item-content">
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Name</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(member.name || '')}" oninput="updateRepeaterItem(${blockIndex}, 'members', ${i}, 'name', this.value)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Role</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(member.role || '')}" oninput="updateRepeaterItem(${blockIndex}, 'members', ${i}, 'role', this.value)">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="block-field">
                                                <label class="block-field-label">Photo URL</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(member.image || '')}" oninput="updateRepeaterItem(${blockIndex}, 'members', ${i}, 'image', this.value)" placeholder="https://... (optional)">
                                            </div>
                                            <div class="block-field">
                                                <label class="block-field-label">Initials (if no photo)</label>
                                                <input type="text" class="block-field-input" value="${escapeHtml(member.initials || '')}" oninput="updateRepeaterItem(${blockIndex}, 'members', ${i}, 'initials', this.value)" maxlength="2">
                                            </div>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Bio</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="updateRepeaterItem(${blockIndex}, 'members', ${i}, 'bio', this.value)">${escapeHtml(member.bio || '')}</textarea>
                                        </div>
                                        <div class="block-field">
                                            <label class="block-field-label">Social Links (JSON)</label>
                                            <textarea class="block-field-input block-field-textarea" oninput="try { updateRepeaterItem(${blockIndex}, 'members', ${i}, 'social', JSON.parse(this.value)); } catch(e) {}" placeholder='[{"platform": "twitter", "url": "#"}]'>${escapeHtml(JSON.stringify(member.social || []))}</textarea>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <button type="button" class="repeater-add" onclick="addRepeaterItem(${blockIndex}, 'members', {name: '', role: '', initials: '', image: '', bio: '', social: []})">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Team Member
                        </button>
                    </div>
                `;

        default:
            return '<p style="color: var(--text-muted);">Unknown block type</p>';
    }
}

function renderLinkInput(value, callback) {
    const id = 'link-' + Math.random().toString(36).substr(2, 9);
    return '<div class="link-input-wrapper">' +
        '<input type="text" class="block-field-input" id="' + id + '" value="' + escapeHtml(value) + '" oninput="' + callback.replace('VALUE', 'this.value') + '">' +
        '<button type="button" class="link-page-btn" onclick="showLinkSelector(\'' + id + '\', \'' + callback.replace(/'/g, "\\'") + '\')" title="Select page">' +
        '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="16" height="16"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' +
        '</button>' +
        '</div>';
}

function updateBlockData(blockIndex, key, value) {
    if (!blocks[blockIndex].data) blocks[blockIndex].data = {};
    blocks[blockIndex].data[key] = value;
    schedulePreviewUpdate();
}

function updateRepeaterItem(blockIndex, key, itemIndex, field, value) {
    if (!blocks[blockIndex].data) blocks[blockIndex].data = {};
    if (!blocks[blockIndex].data[key]) blocks[blockIndex].data[key] = [];
    if (!blocks[blockIndex].data[key][itemIndex]) blocks[blockIndex].data[key][itemIndex] = {};
    blocks[blockIndex].data[key][itemIndex][field] = value;
    schedulePreviewUpdate();
}

function addRepeaterItem(blockIndex, key, defaultItem) {
    if (!blocks[blockIndex].data) blocks[blockIndex].data = {};
    if (!blocks[blockIndex].data[key]) blocks[blockIndex].data[key] = [];
    blocks[blockIndex].data[key].push({ ...defaultItem });
    schedulePreviewUpdate();
    renderBlocks();
}

function removeRepeaterItem(blockIndex, key, itemIndex) {
    blocks[blockIndex].data[key].splice(itemIndex, 1);
    renderBlocks();
    schedulePreviewUpdate();
}

function toggleBlock(index) {
    const items = document.querySelectorAll('.block-item');
    items[index]?.classList.toggle('expanded');
}

function expandAllBlocks() {
    document.querySelectorAll('.block-item').forEach(el => el.classList.add('expanded'));
}

function collapseAllBlocks() {
    document.querySelectorAll('.block-item').forEach(el => el.classList.remove('expanded'));
}

function addBlock(type) {
    const defaultId = getDefaultBlockId(type);
    const newBlock = {
        id: defaultId,
        type: type,
        data: getDefaultBlockData(type)
    };
    blocks.push(newBlock);
    renderBlocks();
    hideAddBlockModal();
    schedulePreviewUpdate();

    setTimeout(() => {
        const items = document.querySelectorAll('.block-item');
        items[items.length - 1]?.classList.add('expanded');
        items[items.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 50);
}

function getDefaultBlockId(type) {
    const baseName = type;
    const existingIds = blocks
        .filter(b => b.type === type && b.id)
        .map(b => b.id);

    if (existingIds.length === 0) {
        return baseName;
    }

    let counter = 2;
    while (existingIds.includes(baseName + '-' + counter)) {
        counter++;
    }
    return baseName + '-' + counter;
}

function getDefaultBlockData(type) {
    switch (type) {
        case 'hero':
            return { badge: '', title: '', subtitle: '', button_primary: '', button_primary_url: '#', button_secondary: '', button_secondary_url: '#' };
        case 'stats':
            return { items: [] };
        case 'features':
            return { title: '', subtitle: '', items: [] };
        case 'testimonials':
            return { title: '', subtitle: '', items: [] };
        case 'pricing':
            return { title: '', subtitle: '', items: [] };
        case 'cta':
            return { title: '', subtitle: '', button_primary: '', button_primary_url: '#', button_secondary: '', button_secondary_url: '#' };
        case 'text':
            return { title: '', content: '' };
        case 'image':
            return { url: '', alt: '', caption: '' };
        case 'image-text':
            return { image_url: '', image_alt: '', title: '', subtitle: '', content: '', image_position: 'left', button_text: '', button_url: '#' };
        case 'product-cards':
            return { title: '', subtitle: '', products: [] };
        case 'video':
            return { title: 'Watch Our Video', subtitle: '', url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', type: 'youtube', caption: '' };
        case 'gallery':
            return {
                title: 'Photo Gallery', subtitle: '', images: [
                    { url: 'https://placehold.co/800x600', alt: 'Gallery image 1', caption: '' },
                    { url: 'https://placehold.co/800x600', alt: 'Gallery image 2', caption: '' },
                    { url: 'https://placehold.co/800x600', alt: 'Gallery image 3', caption: '' }
                ]
            };
        case 'faq':
            return {
                title: 'Frequently Asked Questions', subtitle: '', items: [
                    { question: 'What is this?', answer: 'This is a great product that helps you achieve your goals.' },
                    { question: 'How does it work?', answer: 'It works by combining cutting-edge technology with user-friendly design.' },
                    { question: 'How much does it cost?', answer: 'We offer both free and premium plans to suit your needs.' }
                ]
            };
        case 'team':
            return {
                title: 'Meet Our Team', subtitle: '', members: [
                    { name: 'John Doe', role: 'CEO & Founder', initials: 'JD', image: '', bio: '', social: [{ platform: 'twitter', url: '#' }, { platform: 'linkedin', url: '#' }] },
                    { name: 'Jane Smith', role: 'Head of Design', initials: 'JS', image: '', bio: '', social: [{ platform: 'twitter', url: '#' }, { platform: 'linkedin', url: '#' }] }
                ]
            };
        case 'audio':
            return { title: 'Listen Now', subtitle: '', url: '', music_link: '', music_link_text: 'Download' };
        case 'countdown':
            return { title: 'Coming Soon', subtitle: '', target_date: '', target_time: '00:00' };
        case 'newsletter':
            return { title: 'Subscribe to Our Newsletter', subtitle: 'Get the latest updates delivered to your inbox.', button_text: 'Subscribe', placeholder: 'Enter your email' };
        case 'html':
            return { html: '' };
        default:
            return {};
    }
}

function duplicateBlock(index) {
    const newBlock = JSON.parse(JSON.stringify(blocks[index]));
    const baseType = blocks[index].type;
    newBlock.id = getDefaultBlockId(baseType);
    blocks.splice(index + 1, 0, newBlock);
    renderBlocks();
}

function deleteBlock(index) {
    if (confirm('Are you sure you want to delete this block?')) {
        blocks.splice(index, 1);
        renderBlocks();
        schedulePreviewUpdate();
    }
}

function moveBlockUp(index) {
    if (index > 0) {
        [blocks[index], blocks[index - 1]] = [blocks[index - 1], blocks[index]];
        renderBlocks();
    }
}

function moveBlockDown(index) {
    if (index < blocks.length - 1) {
        [blocks[index], blocks[index + 1]] = [blocks[index + 1], blocks[index]];
        renderBlocks();
    }
}

// Drag and Drop
function handleDragStart(e) {
    draggedBlock = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.block-item').forEach(el => el.classList.remove('drag-over'));
    draggedBlock = null;
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');

    if (draggedBlock && draggedBlock !== this) {
        const fromIndex = parseInt(draggedBlock.dataset.index);
        const toIndex = parseInt(this.dataset.index);

        const [movedBlock] = blocks.splice(fromIndex, 1);
        blocks.splice(toIndex, 0, movedBlock);
        renderBlocks();
        schedulePreviewUpdate();
    }
}

// ========== SETTINGS FUNCTIONS ==========

function renderNavLinks() {
    const container = document.getElementById('navLinksContainer');
    if (!container) return;

    container.innerHTML = navLinks.map((link, i) => `
            <div class="repeater-item expanded">
                <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                    <span class="repeater-item-title">${escapeHtml(link.label || 'New Link')}</span>
                    <div class="repeater-item-actions">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeNavLink(${i})" style="color: var(--error);">Remove</button>
                    </div>
                </div>
                <div class="repeater-item-content">
                    <div class="form-row">
                        <div class="block-field">
                            <label class="block-field-label">Label</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(link.label || '')}" oninput="navLinks[${i}].label = this.value">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">URL</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(link.url || '')}" oninput="navLinks[${i}].url = this.value">
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
}

function addNavLink() {
    navLinks.push({ label: '', url: '#' });
    renderNavLinks();
}

function removeNavLink(index) {
    navLinks.splice(index, 1);
    renderNavLinks();
}

function renderNavButtons() {
    const container = document.getElementById('navButtonsContainer');
    if (!container) return;

    container.innerHTML = navButtons.map((btn, i) => `
            <div class="repeater-item expanded">
                <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                    <span class="repeater-item-title">${escapeHtml(btn.label || 'New Button')}</span>
                    <div class="repeater-item-actions">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeNavButton(${i})" style="color: var(--error);">Remove</button>
                    </div>
                </div>
                <div class="repeater-item-content">
                    <div class="form-row-3">
                        <div class="block-field">
                            <label class="block-field-label">Label</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(btn.label || '')}" oninput="navButtons[${i}].label = this.value">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">URL</label>
                            <input type="text" class="block-field-input" value="${escapeHtml(btn.url || '')}" oninput="navButtons[${i}].url = this.value">
                        </div>
                        <div class="block-field">
                            <label class="block-field-label">Style</label>
                            <select class="block-field-input" onchange="navButtons[${i}].style = this.value">
                                <option value="ghost" ${btn.style === 'ghost' ? 'selected' : ''}>Ghost</option>
                                <option value="primary" ${btn.style === 'primary' ? 'selected' : ''}>Primary</option>
                                <option value="outline" ${btn.style === 'outline' ? 'selected' : ''}>Outline</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
}

function addNavButton() {
    navButtons.push({ label: '', url: '#', style: 'ghost' });
    renderNavButtons();
}

function removeNavButton(index) {
    navButtons.splice(index, 1);
    renderNavButtons();
}

function updateFooterColumnTitle(colIndex, value) {
    if (!footerData.columns) footerData.columns = [];
    if (!footerData.columns[colIndex]) footerData.columns[colIndex] = { title: '', links: [] };
    footerData.columns[colIndex].title = value;
    updateFooterData();
}

function updateFooterColumnLinkLabel(colIndex, linkIndex, value) {
    if (!footerData.columns) footerData.columns = [];
    if (!footerData.columns[colIndex]) footerData.columns[colIndex] = { title: '', links: [] };
    if (!footerData.columns[colIndex].links) footerData.columns[colIndex].links = [];
    if (!footerData.columns[colIndex].links[linkIndex]) footerData.columns[colIndex].links[linkIndex] = { label: '', url: '' };
    footerData.columns[colIndex].links[linkIndex].label = value;
    updateFooterData();
}

function updateFooterColumnLinkUrl(colIndex, linkIndex, value) {
    if (!footerData.columns) footerData.columns = [];
    if (!footerData.columns[colIndex]) footerData.columns[colIndex] = { title: '', links: [] };
    if (!footerData.columns[colIndex].links) footerData.columns[colIndex].links = [];
    if (!footerData.columns[colIndex].links[linkIndex]) footerData.columns[colIndex].links[linkIndex] = { label: '', url: '' };
    footerData.columns[colIndex].links[linkIndex].url = value;
    updateFooterData();
}

function renderFooterColumns() {
    const container = document.getElementById('footerColumnsContainer');
    if (!container) return;
    if (!footerData.columns) footerData.columns = [];

    container.innerHTML = footerData.columns.map((col, i) => {
        if (!col) col = { title: '', links: [] };
        if (!col.links) col.links = [];
        return `
        <div class="repeater-item">
            <div class="repeater-item-header" onclick="this.parentElement.classList.toggle('expanded')">
                <span class="repeater-item-title">${escapeHtml(col.title || 'Column')}</span>
                <div class="repeater-item-actions">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="event.stopPropagation(); removeFooterColumn(${i})" style="color: var(--error);">Remove</button>
                </div>
            </div>
            <div class="repeater-item-content">
                <div class="block-field">
                    <label class="block-field-label">Column Title</label>
                    <input type="text" class="block-field-input" value="${escapeHtml(col.title || '')}" oninput="updateFooterColumnTitle(${i}, this.value)">
                </div>
                <div class="block-field">
                    <label class="block-field-label">Links</label>
                    <div id="footer-col-links-${i}">
                        ${(col.links || []).map((link, j) => `
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="block-field-input" value="${escapeHtml(link.label || '')}" placeholder="Label" oninput="updateFooterColumnLinkLabel(${i}, ${j}, this.value)" style="flex: 1;">
                                <input type="text" class="block-field-input" value="${escapeHtml(link.url || '')}" placeholder="URL" oninput="updateFooterColumnLinkUrl(${i}, ${j}, this.value)" style="flex: 1;">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="removeFooterColumnLink(${i}, ${j})" style="color: var(--error);">X</button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="repeater-add" onclick="addFooterColumnLink(${i})">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Link
                    </button>
                </div>
            </div>
        </div>
    `}).join('');
}

function addFooterColumn() {
    if (!footerData.columns) footerData.columns = [];
    footerData.columns.push({ title: '', links: [] });
    renderFooterColumns();
    updateFooterData();
}

function removeFooterColumn(index) {
    footerData.columns.splice(index, 1);
    renderFooterColumns();
    updateFooterData();
}

function addFooterColumnLink(colIndex) {
    if (!footerData.columns) footerData.columns = [];
    if (!footerData.columns[colIndex]) footerData.columns[colIndex] = { title: '', links: [] };
    if (!footerData.columns[colIndex].links) footerData.columns[colIndex].links = [];
    footerData.columns[colIndex].links.push({ label: '', url: '#' });
    renderFooterColumns();
    updateFooterData();
}

function removeFooterColumnLink(colIndex, linkIndex) {
    footerData.columns[colIndex].links.splice(linkIndex, 1);
    renderFooterColumns();
    updateFooterData();
}

function renderSocialLinks() {
    const container = document.getElementById('socialLinksContainer');
    if (!container) return;
    if (!footerData.social_links) footerData.social_links = [];

    container.innerHTML = footerData.social_links.map((link, i) => `
        <div style="display: flex; gap: 8px; margin-bottom: 8px;" data-social-index="${i}">
            <select class="block-field-input social-platform" data-index="${i}" style="flex: 1;">
                <option value="twitter" ${link.platform === 'twitter' ? 'selected' : ''}>Twitter/X</option>
                <option value="github" ${link.platform === 'github' ? 'selected' : ''}>GitHub</option>
                <option value="linkedin" ${link.platform === 'linkedin' ? 'selected' : ''}>LinkedIn</option>
                <option value="facebook" ${link.platform === 'facebook' ? 'selected' : ''}>Facebook</option>
                <option value="instagram" ${link.platform === 'instagram' ? 'selected' : ''}>Instagram</option>
                <option value="youtube" ${link.platform === 'youtube' ? 'selected' : ''}>YouTube</option>
                <option value="tiktok" ${link.platform === 'tiktok' ? 'selected' : ''}>TikTok</option>
                <option value="pinterest" ${link.platform === 'pinterest' ? 'selected' : ''}>Pinterest</option>
                <option value="reddit" ${link.platform === 'reddit' ? 'selected' : ''}>Reddit</option>
                <option value="discord" ${link.platform === 'discord' ? 'selected' : ''}>Discord</option>
                <option value="snapchat" ${link.platform === 'snapchat' ? 'selected' : ''}>Snapchat</option>
                <option value="whatsapp" ${link.platform === 'whatsapp' ? 'selected' : ''}>Whatsapp</option>
                <option value="telegram" ${link.platform === 'telegram' ? 'selected' : ''}>Telegram</option>
                <option value="twitch" ${link.platform === 'twitch' ? 'selected' : ''}>Twitch</option>
                <option value="spotify" ${link.platform === 'spotify' ? 'selected' : ''}>Spotify</option>
                <option value="medium" ${link.platform === 'medium' ? 'selected' : ''}>Medium</option>
                <option value="slack" ${link.platform === 'slack' ? 'selected' : ''}>Slack</option>
                <option value="dribbble" ${link.platform === 'dribbble' ? 'selected' : ''}>Dribbble</option>
                <option value="mastodon" ${link.platform === 'mastodon' ? 'selected' : ''}>Mastodon</option>
                <option value="patreon" ${link.platform === 'patreon' ? 'selected' : ''}>Patreon</option>
                <option value="kofi" ${link.platform === 'kofi' ? 'selected' : ''}>Ko-Fi</option>
                <option value="vimeo" ${link.platform === 'vimeo' ? 'selected' : ''}>Vimeo</option>
                <option value="tumblr" ${link.platform === 'tumblr' ? 'selected' : ''}>Tumblr</option>
                <option value="stackoverflow" ${link.platform === 'stackoverflow' ? 'selected' : ''}>Stack Overflow</option>
                <option value="gitlab" ${link.platform === 'gitlab' ? 'selected' : ''}>GitLab</option>
                <option value="bluesky" ${link.platform === 'bluesky' ? 'selected' : ''}>Bluesky</option>
                <option value="line" ${link.platform === 'line' ? 'selected' : ''}>Line</option>
                <option value="wechat" ${link.platform === 'wechat' ? 'selected' : ''}>WeChat</option>
                <option value="flatlypage" ${link.platform === 'flatlypage' ? 'selected' : ''}>AxElitus CMS</option>
                <option value="xing" ${link.platform === 'xing' ? 'selected' : ''}>Xing</option>
            </select>
            <input type="text" class="block-field-input social-url" data-index="${i}" value="${escapeHtml(link.url || '')}" placeholder="URL" style="flex: 2;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="removeSocialLink(${i})" style="color: var(--error);">X</button>
        </div>
    `).join('');

    document.querySelectorAll('.social-platform').forEach(select => {
        select.addEventListener('change', function () {
            const index = parseInt(this.getAttribute('data-index'));
            if (footerData.social_links[index]) {
                footerData.social_links[index].platform = this.value;
                updateFooterData();
            }
        });
    });

    document.querySelectorAll('.social-url').forEach(input => {
        input.addEventListener('input', function () {
            const index = parseInt(this.getAttribute('data-index'));
            if (footerData.social_links[index]) {
                footerData.social_links[index].url = this.value;
                updateFooterData();
            }
        });
    });
}

function addSocialLink() {
    if (!footerData.social_links) footerData.social_links = [];
    footerData.social_links.push({ platform: 'twitter', url: '#' });
    renderSocialLinks();
    updateFooterData();
}

function removeSocialLink(index) {
    footerData.social_links.splice(index, 1);
    renderSocialLinks();
    updateFooterData();
}

function renderBottomLinks() {
    const container = document.getElementById('bottomLinksContainer');
    if (!container) return;
    if (!footerData.bottom_links) footerData.bottom_links = [];

    container.innerHTML = footerData.bottom_links.map((link, i) => `
        <div style="display: flex; gap: 8px; margin-bottom: 8px;" data-bottom-index="${i}">
            <input type="text" class="block-field-input bottom-label" data-index="${i}" value="${escapeHtml(link.label || '')}" placeholder="Label" style="flex: 1;">
            <input type="text" class="block-field-input bottom-url" data-index="${i}" value="${escapeHtml(link.url || '')}" placeholder="URL" style="flex: 1;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="removeBottomLink(${i})" style="color: var(--error);">X</button>
        </div>
    `).join('');

    document.querySelectorAll('.bottom-label').forEach(input => {
        input.addEventListener('input', function () {
            const index = parseInt(this.getAttribute('data-index'));
            if (footerData.bottom_links[index]) {
                footerData.bottom_links[index].label = this.value;
                updateFooterData();
            }
        });
    });

    document.querySelectorAll('.bottom-url').forEach(input => {
        input.addEventListener('input', function () {
            const index = parseInt(this.getAttribute('data-index'));
            if (footerData.bottom_links[index]) {
                footerData.bottom_links[index].url = this.value;
                updateFooterData();
            }
        });
    });
}

function addBottomLink() {
    if (!footerData.bottom_links) footerData.bottom_links = [];
    footerData.bottom_links.push({ label: '', url: '#' });
    renderBottomLinks();
    updateFooterData();
}

function removeBottomLink(index) {
    footerData.bottom_links.splice(index, 1);
    renderBottomLinks();
    updateFooterData();
}

function updateFooterData() {
    footerData.brand_description = document.getElementById('footerBrandDesc')?.value || '';
    footerData.copyright = document.getElementById('footerCopyright')?.value || '';

    if (!footerData.columns) footerData.columns = [];
    if (!footerData.social_links) footerData.social_links = [];
    if (!footerData.bottom_links) footerData.bottom_links = [];
}

// ========== LINK SELECTOR ==========
function updateAnchorsList() {
    const anchorsList = document.getElementById('anchorsList');
    if (!anchorsList) return;

    const anchors = new Set(['#']); // Always include top

    // Scan blocks for common section types that can be anchored
    blocks.forEach(block => {
        if (block.id && ['features', 'testimonials', 'pricing', 'faq', 'team', 'stats', 'cta', 'video', 'gallery', 'audio', 'countdown', 'newsletter'].includes(block.type)) {
            anchors.add(`#${block.id}`);
        }
    });

    anchorsList.innerHTML = '';
    anchors.forEach(anchor => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-secondary btn-sm';
        btn.onclick = () => selectLink(anchor);
        btn.textContent = anchor === '#' ? '#top' : anchor;
        btn.style.margin = '0 8px 8px 0';
        anchorsList.appendChild(btn);
    });
}

function renderInternalPages() {
    const container = document.getElementById('internalPages');
    if (!container) return;

    container.innerHTML = AVAILABLE_PAGES.map(page => `
            <button type="button" class="btn btn-secondary btn-sm" style="margin: 0 8px 8px 0;" onclick="selectLink('${escapeHtml(page.url)}')">${escapeHtml(page.label)}</button>
        `).join('');
}

function showLinkSelector(inputId, callback) {
    currentLinkCallback = { inputId, callback };
    updateAnchorsList();
    document.getElementById('linkSelectorModal').classList.add('active');
}

function hideLinkSelector() {
    document.getElementById('linkSelectorModal').classList.remove('active');
    currentLinkCallback = null;
}

function selectLink(url) {
    if (currentLinkCallback) {
        const input = document.getElementById(currentLinkCallback.inputId);
        if (input) {
            input.value = url;
            input.dispatchEvent(new Event('input'));
        }
    }
    hideLinkSelector();
}

// ========== MODALS ==========

function showAddBlockModal() {
    document.getElementById('addBlockModal').classList.add('active');
}

function hideAddBlockModal() {
    document.getElementById('addBlockModal').classList.remove('active');
}

function confirmDelete(productId, productName) {
    document.getElementById('deleteProductId').value = productId;
    document.getElementById('deleteProductName').textContent = productName;
    document.getElementById('deleteModal').classList.add('active');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

// ========== UTILITY ==========

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        hideAddBlockModal();
        hideDeleteModal();
        hideLinkSelector();
    }
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
