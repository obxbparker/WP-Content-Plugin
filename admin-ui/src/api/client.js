/**
 * REST API client using wp.apiFetch.
 */
import apiFetch from '@wordpress/api-fetch';

const BASE = 'contenthub-wp/v1';

// Pages
export const getPages = () => apiFetch({ path: `${BASE}/pages` });

export const assignTemplate = (pageId, templateSlug) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/assign-template`,
        method: 'POST',
        data: { template_slug: templateSlug },
    });

export const getPageContent = (pageId) =>
    apiFetch({ path: `${BASE}/pages/${pageId}/content` });

export const savePageContent = (pageId, data, source = 'manual') =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/content`,
        method: 'POST',
        data: { data, source },
    });

export const scrapePage = (pageId, url) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/scrape`,
        method: 'POST',
        data: { url },
    });

export const generatePageContent = (pageId, pageContext) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/generate`,
        method: 'POST',
        data: pageContext ? { page_context: pageContext } : undefined,
    });

export const getPageContext = (pageId) =>
    apiFetch({ path: `${BASE}/pages/${pageId}/context` });

export const savePageContext = (pageId, context) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/context`,
        method: 'POST',
        data: { context },
    });

export const uploadFile = (pageId, file) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', `ContentHub - ${file.name}`);
    return apiFetch({
        path: 'wp/v2/media',
        method: 'POST',
        body: formData,
        headers: {},
    });
};

export const deployPage = (pageId) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/deploy`,
        method: 'POST',
    });

export const rollbackPage = (pageId) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/rollback`,
        method: 'POST',
    });

export const previewPage = (pageId, data) =>
    apiFetch({
        path: `${BASE}/pages/${pageId}/preview`,
        method: 'POST',
        data: { data },
    });

// Template Types
export const getTemplateTypes = () =>
    apiFetch({ path: `${BASE}/template-types` });

export const createTemplateType = (name) =>
    apiFetch({
        path: `${BASE}/template-types`,
        method: 'POST',
        data: { name },
    });

export const updateTemplateType = (slug, data) =>
    apiFetch({
        path: `${BASE}/template-types/${slug}`,
        method: 'PUT',
        data,
    });

export const deleteTemplateType = (slug) =>
    apiFetch({
        path: `${BASE}/template-types/${slug}`,
        method: 'DELETE',
    });

export const setElementorTemplate = (slug, templateId) =>
    apiFetch({
        path: `${BASE}/template-types/${slug}/set-template`,
        method: 'POST',
        data: { template_id: templateId },
    });

export const getElementorTemplates = () =>
    apiFetch({ path: `${BASE}/elementor-templates` });

export const getBlueprint = (slug) =>
    apiFetch({ path: `${BASE}/template-types/${slug}/blueprint` });

export const getMapping = (slug) =>
    apiFetch({ path: `${BASE}/template-types/${slug}/mapping` });

export const saveMapping = (slug, mapping) =>
    apiFetch({
        path: `${BASE}/template-types/${slug}/mapping`,
        method: 'POST',
        data: mapping,
    });

// Batch Deploy
export const deployBatch = (slug) =>
    apiFetch({
        path: `${BASE}/deploy/batch/${slug}`,
        method: 'POST',
    });

// Business Profile
export const getBusinessProfile = () =>
    apiFetch({ path: `${BASE}/business-profile` });

export const saveBusinessProfile = (data) =>
    apiFetch({
        path: `${BASE}/business-profile`,
        method: 'POST',
        data,
    });

// Share Token
export const getShareToken = () =>
    apiFetch({ path: `${BASE}/share-token` });

export const generateShareToken = () =>
    apiFetch({
        path: `${BASE}/share-token`,
        method: 'POST',
    });

export const revokeShareToken = () =>
    apiFetch({
        path: `${BASE}/share-token`,
        method: 'DELETE',
    });

// Settings
export const getSettings = () =>
    apiFetch({ path: `${BASE}/settings` });

export const saveSettings = (data) =>
    apiFetch({
        path: `${BASE}/settings`,
        method: 'POST',
        data,
    });

// Portal Visibility
export const getPortalVisibility = () =>
    apiFetch({ path: `${BASE}/portal-visibility` });

export const savePortalVisibility = (pageIds) =>
    apiFetch({
        path: `${BASE}/portal-visibility`,
        method: 'POST',
        data: { page_ids: pageIds },
    });
