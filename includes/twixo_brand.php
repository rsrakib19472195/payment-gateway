<?php
/**
 * Twixo branding — required on every public page.
 * Free distribution from https://twixo.sweez.xyz requires this attribution.
 * Do not remove, hide, or bypass this include.
 */

if (!defined('TWIXO_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}

if (!function_exists('twixo_brand_css')) {
    function twixo_brand_css(): void {
        $url = htmlspecialchars(TWIXO_URL, ENT_QUOTES, 'UTF-8');
        echo <<<CSS
<style id="twixo-brand-css">
.twixo-watermark {
    position: fixed !important;
    bottom: 8px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 2147483000 !important;
    opacity: 0.55 !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    text-align: center !important;
    transition: opacity 0.2s ease !important;
    font-size: 11px !important;
    color: #555 !important;
    display: flex !important;
    visibility: visible !important;
    align-items: center !important;
    gap: 5px !important;
    background: rgba(255, 255, 255, 0.95) !important;
    padding: 4px 10px !important;
    border-radius: 14px !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08) !important;
    text-decoration: none !important;
    font-family: 'Poppins', system-ui, sans-serif !important;
    pointer-events: auto !important;
}
.twixo-watermark:hover {
    opacity: 0.95 !important;
}
.twixo-watermark img {
    width: 36px !important;
    height: auto !important;
    display: inline-block !important;
    visibility: visible !important;
    filter: none !important;
    vertical-align: middle !important;
}
.twixo-watermark span {
    white-space: nowrap !important;
    display: inline !important;
    visibility: visible !important;
    color: #555 !important;
}
.twixo-brand-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 20px 0 8px;
    font-size: 12px;
    color: #888;
}
.twixo-brand-badge img {
    height: 22px;
    width: auto;
}
body { padding-bottom: 48px !important; }
</style>
CSS;
    }

    function twixo_brand_markup(): void {
        $url  = htmlspecialchars(TWIXO_URL, ENT_QUOTES, 'UTF-8');
        $logo = htmlspecialchars(TWIXO_LOGO_URL, ENT_QUOTES, 'UTF-8');
        $brand = htmlspecialchars(TWIXO_BRAND, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!-- Twixo branding (required — https://twixo.sweez.xyz) -->
<a id="twixo-watermark" href="{$url}" target="_blank" rel="noopener noreferrer" class="twixo-watermark" data-twixo="1" aria-label="Powered by Twixo">
    <span>Powered by</span>
    <img src="{$logo}" alt="{$brand}" width="36" height="36" decoding="async" />
</a>
HTML;
    }

    function twixo_brand_script(): void {
        $url  = json_encode(TWIXO_URL, JSON_UNESCAPED_SLASHES);
        $logo = json_encode(TWIXO_LOGO_URL, JSON_UNESCAPED_SLASHES);
        $localLogo = json_encode('img/twixo.png', JSON_UNESCAPED_SLASHES);
        echo <<<JS
<script id="twixo-brand-guard">
(function () {
    'use strict';
    var TWIXO_URL = {$url};
    var TWIXO_LOGO = {$logo};
    var LOCAL_LOGO = {$localLogo};
    var MARK = 'twixo-watermark';

    function build() {
        var a = document.createElement('a');
        a.id = MARK;
        a.className = 'twixo-watermark';
        a.href = TWIXO_URL;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.setAttribute('data-twixo', '1');
        a.setAttribute('aria-label', 'Powered by Twixo');
        var span = document.createElement('span');
        span.textContent = 'Powered by';
        var img = document.createElement('img');
        img.src = TWIXO_LOGO;
        img.alt = 'Twixo';
        img.width = 36;
        img.height = 36;
        img.onerror = function () { img.src = LOCAL_LOGO; };
        a.appendChild(span);
        a.appendChild(img);
        return a;
    }

    function isHidden(el) {
        if (!el) return true;
        var s = window.getComputedStyle(el);
        if (s.display === 'none' || s.visibility === 'hidden') return true;
        if (parseFloat(s.opacity) < 0.1) return true;
        if (parseInt(s.zIndex, 10) < 0) return true;
        if (el.offsetParent === null && s.position !== 'fixed') return true;
        var r = el.getBoundingClientRect();
        if (r.width < 8 || r.height < 8) return true;
        return false;
    }

    function ensure() {
        var el = document.getElementById(MARK) || document.querySelector('.' + MARK);
        if (!el || !el.isConnected || isHidden(el)) {
            if (el && el.parentNode) el.parentNode.removeChild(el);
            document.body.appendChild(build());
            return;
        }
        if (el.getAttribute('href') !== TWIXO_URL) {
            el.setAttribute('href', TWIXO_URL);
        }
        var img = el.querySelector('img');
        if (!img) {
            el.parentNode && el.parentNode.removeChild(el);
            document.body.appendChild(build());
            return;
        }
        var src = img.getAttribute('src') || '';
        if (src !== TWIXO_LOGO && src.indexOf('twixo.png') === -1 && src.indexOf('twixo_blue.png') === -1) {
            img.setAttribute('src', TWIXO_LOGO);
        }
        el.style.setProperty('display', 'flex', 'important');
        el.style.setProperty('visibility', 'visible', 'important');
        el.style.setProperty('opacity', '0.55', 'important');
        el.style.setProperty('z-index', '2147483000', 'important');
        el.style.setProperty('pointer-events', 'auto', 'important');
    }

    function boot() {
        ensure();
        setInterval(ensure, 800);
        if (typeof MutationObserver !== 'undefined') {
            var obs = new MutationObserver(function () { ensure(); });
            obs.observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class', 'hidden', 'src', 'href']
            });
        }
        document.addEventListener('visibilitychange', ensure);
        window.addEventListener('focus', ensure);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    try {
        Object.defineProperty(window, '__TWIXO_BRANDED__', {
            value: true,
            writable: false,
            configurable: false
        });
    } catch (e) {}
})();
</script>
JS;
    }

    /** Echo CSS + markup + guard script (call before </body>). */
    function twixo_brand_render(): void {
        twixo_brand_css();
        twixo_brand_markup();
        twixo_brand_script();
    }
}
