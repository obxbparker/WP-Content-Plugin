/**
 * Portal API client using plain fetch with X-Share-Token header.
 */

const config = window.__CONTENTHUB_PORTAL__;

export async function apiFetch(path, options = {}) {
    const res = await fetch(config.restUrl + path, {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Share-Token': config.token,
        },
        body: options.data ? JSON.stringify(options.data) : undefined,
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || err.error || 'Request failed');
    }

    return res.json();
}

// Portal config
export const getPortalConfig = () => apiFetch('public/portal-config');

// Business profile
export const getBusinessProfile = () => apiFetch('public/business-profile');
export const saveBusinessProfile = (data) =>
    apiFetch('public/business-profile', { method: 'POST', data });

// Pages
export const getPages = () => apiFetch('public/pages');

export const getPageContent = (pageId) =>
    apiFetch(`public/pages/${pageId}/content`);

export const savePageContent = (pageId, data) =>
    apiFetch(`public/pages/${pageId}/content`, {
        method: 'POST',
        data: { data, source: 'manual' },
    });

export const scrapePage = (pageId, url) =>
    apiFetch(`public/pages/${pageId}/scrape`, {
        method: 'POST',
        data: { url },
    });

export const generatePageContent = (pageId, pageContext) =>
    apiFetch(`public/pages/${pageId}/generate`, {
        method: 'POST',
        data: pageContext ? { page_context: pageContext } : undefined,
    });

export const getPageContext = (pageId) =>
    apiFetch(`public/pages/${pageId}/context`);

export const savePageContext = (pageId, context) =>
    apiFetch(`public/pages/${pageId}/context`, {
        method: 'POST',
        data: { context },
    });

export const previewPage = (pageId, data) =>
    apiFetch(`public/pages/${pageId}/preview`, {
        method: 'POST',
        data: { data },
    });

// Template mapping
export const getMapping = (slug) =>
    apiFetch(`public/template-types/${slug}/mapping`);
