// Asks for consent to analytics cookies and loads Google Analytics only after the visitor accepts.
// The choice is kept in the cookie_consent cookie; buttons with data-cookie-settings show the banner again.
// Without JS the banner stays hidden and Google Analytics is never loaded.
const COOKIE = 'cookie_consent';
const MAX_AGE = 180 * 24 * 60 * 60; // ask again after 6 months

const banner = document.getElementById('cookie-consent');
const gaId = banner?.dataset.gaId;

function readConsent() {
    const match = document.cookie.match(new RegExp('(?:^|; )' + COOKIE + '=(granted|denied)'));
    return match ? match[1] : null;
}

function saveConsent(value) {
    const secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = COOKIE + '=' + value + '; Max-Age=' + MAX_AGE + '; Path=/; SameSite=Lax' + secure;
}

function loadAnalytics() {
    window['ga-disable-' + gaId] = false;
    if (window.gtag) {
        return;
    }
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', gaId);

    const script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
    document.head.append(script);
}

// Stops tracking on this page and deletes the GA cookies (_ga, _ga_<id>), which GA sets on the host or a parent domain
function removeAnalytics() {
    window['ga-disable-' + gaId] = true;
    const parts = location.hostname.split('.');
    const domains = [''];
    for (let i = 0; i < parts.length - 1; i++) {
        domains.push('; Domain=.' + parts.slice(i).join('.'));
    }
    document.cookie.split('; ').forEach((cookie) => {
        const name = cookie.split('=')[0];
        if (name === '_ga' || name.startsWith('_ga_')) {
            domains.forEach((domain) => {
                document.cookie = name + '=; Max-Age=0; Path=/' + domain;
            });
        }
    });
}

if (banner && gaId) {
    const consent = readConsent();
    if (consent === 'granted') {
        loadAnalytics();
    } else if (consent === null) {
        banner.hidden = false;
    }

    banner.querySelectorAll('[data-cookie-consent]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.dataset.cookieConsent;
            saveConsent(value);
            if (value === 'granted') {
                loadAnalytics();
            } else {
                removeAnalytics();
            }
            banner.hidden = true;
        });
    });

    document.querySelectorAll('[data-cookie-settings]').forEach((button) => {
        button.hidden = false;
        button.addEventListener('click', () => {
            banner.hidden = false;
            banner.querySelector('[data-cookie-consent]').focus();
        });
    });
}
