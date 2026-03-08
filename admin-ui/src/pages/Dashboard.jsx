import { useState, useEffect } from '@wordpress/element';
import { Button, SelectControl, Spinner, Notice, CheckboxControl } from '@wordpress/components';
import { getPages, getTemplateTypes, assignTemplate, getPortalVisibility, savePortalVisibility } from '../api/client';
import StatusBadge from '../components/StatusBadge';

export default function Dashboard({ onEditPage }) {
    const [pages, setPages] = useState([]);
    const [templateTypes, setTemplateTypes] = useState([]);
    const [portalPages, setPortalPages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [filter, setFilter] = useState('');

    const loadData = async () => {
        setLoading(true);
        try {
            const [pagesData, typesData, portalData] = await Promise.all([
                getPages(),
                getTemplateTypes(),
                getPortalVisibility(),
            ]);
            setPages(pagesData);
            setTemplateTypes(typesData);
            setPortalPages(portalData.page_ids || []);
            setError(null);
        } catch (err) {
            setError(err.message || 'Failed to load data.');
        }
        setLoading(false);
    };

    useEffect(() => { loadData(); }, []);

    const handleAssignTemplate = async (pageId, slug) => {
        try {
            await assignTemplate(pageId, slug);
            setPages((prev) =>
                prev.map((p) =>
                    p.id === pageId ? { ...p, template_type: slug } : p
                )
            );
        } catch (err) {
            setError(err.message);
        }
    };

    const handleTogglePortal = async (pageId, checked) => {
        const updated = checked
            ? [...portalPages, pageId]
            : portalPages.filter((id) => id !== pageId);
        setPortalPages(updated);
        try {
            await savePortalVisibility(updated);
        } catch (err) {
            setError(err.message);
            setPortalPages(portalPages); // revert
        }
    };

    const templateOptions = [
        { label: '— Unassigned —', value: '' },
        ...templateTypes.map((t) => ({ label: t.name, value: t.slug })),
    ];

    const filteredPages = filter
        ? pages.filter((p) => p.template_type === filter)
        : pages;

    if (loading) {
        return (
            <div className="contenthub-wp-loading">
                <Spinner /> Loading pages...
            </div>
        );
    }

    return (
        <div className="contenthub-wp-dashboard">
            {error && (
                <Notice status="error" isDismissible onDismiss={() => setError(null)}>
                    {error}
                </Notice>
            )}

            <div className="contenthub-wp-toolbar">
                <h2>All Pages ({filteredPages.length})</h2>
                <SelectControl
                    value={filter}
                    options={[
                        { label: 'All template types', value: '' },
                        ...templateTypes.map((t) => ({ label: t.name, value: t.slug })),
                    ]}
                    onChange={setFilter}
                    __nextHasNoMarginBottom
                />
                <Button variant="secondary" onClick={loadData}>
                    Refresh
                </Button>
            </div>

            <table className="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page Title</th>
                        <th>Template Type</th>
                        <th>Content Source</th>
                        <th>Status</th>
                        <th style={{ width: '90px', textAlign: 'center' }}>Client Portal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {filteredPages.map((page) => (
                        <tr key={page.id}>
                            <td>
                                <strong>{page.title}</strong>
                                <div className="row-actions">
                                    <a
                                        href={page.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        View
                                    </a>
                                </div>
                            </td>
                            <td>
                                <SelectControl
                                    value={page.template_type || ''}
                                    options={templateOptions}
                                    onChange={(val) =>
                                        handleAssignTemplate(page.id, val)
                                    }
                                    __nextHasNoMarginBottom
                                />
                            </td>
                            <td>{page.content_source || '—'}</td>
                            <td>
                                <StatusBadge status={page.content_status} />
                            </td>
                            <td style={{ textAlign: 'center' }}>
                                {page.template_type ? (
                                    <CheckboxControl
                                        checked={portalPages.includes(page.id)}
                                        onChange={(val) => handleTogglePortal(page.id, val)}
                                        __nextHasNoMarginBottom
                                    />
                                ) : (
                                    <span style={{ color: '#a7aaad' }}>—</span>
                                )}
                            </td>
                            <td>
                                <Button
                                    variant="primary"
                                    size="small"
                                    onClick={() => onEditPage(page.id)}
                                    disabled={!page.template_type}
                                >
                                    Edit Content
                                </Button>
                            </td>
                        </tr>
                    ))}
                    {filteredPages.length === 0 && (
                        <tr>
                            <td colSpan="6" style={{ textAlign: 'center' }}>
                                No pages found. Create pages in WordPress first, then
                                assign template types here.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    );
}
