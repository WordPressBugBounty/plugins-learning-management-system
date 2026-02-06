class LanguageHashPreserver {
    constructor(options = {}) {
        this.storageKey = options.storageKey || 'tp_react_hash';
        this.linkSelectors = options.linkSelectors || [
            '.trp-language-switcher a',
            '.trp-ls-shortcode-language a',
            '.trp-language-switcher-container a'
        ];
        this.selectSelector = options.selectSelector || '#trp-language-select';
        this.hash = this.getReactHash();
        this.bind();
        this.restoreHashWithRetries();
    }

    isReactHash(hash) {
        return typeof hash === 'string' && hash.startsWith('#/');
    }

    getReactHash() {
        const h = window.location.hash || '';
        return this.isReactHash(h) ? h : '';
    }

    saveHash(hash) {
        const h = hash || this.getReactHash() || sessionStorage.getItem(this.storageKey) || '';
        if (this.isReactHash(h)) sessionStorage.setItem(this.storageKey, h);
    }

    restoreHashOnce() {
        const saved = sessionStorage.getItem(this.storageKey);
        if (!this.isReactHash(saved)) return false;
        if (this.isReactHash(window.location.hash)) return true;

        const base = window.location.href.replace(/#.*$/, '');
        history.replaceState(null, '', base + saved);
        return this.isReactHash(window.location.hash);
    }

    restoreHashWithRetries() {
        const tries = [0, 150, 400, 900, 1800, 3000];
        tries.forEach((ms) => {
            setTimeout(() => this.restoreHashOnce(), ms);
        });

        let guard = 0;
        const iv = setInterval(() => {
            if (this.restoreHashOnce() || guard++ > 10) clearInterval(iv);
        }, 500);
    }

    bind() {
        jQuery(window).on('hashchange', () => {
            this.hash = this.getReactHash();
            this.saveHash(this.hash);
        });

        jQuery(window).on('beforeunload pagehide', () => {
            this.saveHash();
        });

        jQuery(document).on('click', this.linkSelectors.join(', '), (e) => {
            const liveHash = this.getReactHash();
            if (!liveHash) return;

            const $link = jQuery(e.currentTarget);
            const href = $link.attr('href') || '';
            if (!href) return;
            if (href.includes('#/')) return;

            const cleanHref = href.replace(/#.*$/, '');
            $link.attr('href', cleanHref + liveHash);
            this.saveHash(liveHash);
        });

        jQuery(document).on('mousedown touchstart', '.select2-selection, .select2-results__option', () => {
            this.saveHash();
        });

        jQuery(document).on('change', this.selectSelector, () => {
            this.saveHash();
        });

        jQuery(document).on('select2:select', this.selectSelector, () => {
            this.saveHash();
        });
    }
}

new LanguageHashPreserver({
    linkSelectors: [
        '.trp-language-switcher a',
        '.trp-ls-shortcode-language a',
        '.trp-language-switcher-container a'
    ],
    selectSelector: '#trp-language-select',
    storageKey: 'tp_react_hash'
});
