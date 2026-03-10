import { useState, useEffect } from 'react';
import { getPages } from '../api/client';
import StatusBadge from '../components/StatusBadge';

function PageRow({ page, isChild, onEditPage }) {
    return (
        <div
            className={`portal-page-row ${isChild ? 'portal-page-child' : ''}`}
            onClick={() => onEditPage(page)}
        >
            <div className="portal-page-row-title">
                {isChild && <span className="portal-page-indent">&mdash;</span>}
                <span>{page.title}</span>
            </div>
            <div className="portal-page-row-meta">
                <span className="portal-page-card-type">{page.template_type || 'Unassigned'}</span>
                <StatusBadge status={page.content_status} />
            </div>
        </div>
    );
}

export default function ContentPages({ onEditPage }) {
    const [pages, setPages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        (async () => {
            try {
                const data = await getPages();
                setPages(data);
            } catch (err) {
                setError(err.message);
            }
            setLoading(false);
        })();
    }, []);

    if (loading) {
        return <div className="portal-loading"><span className="portal-spinner"></span> Loading pages...</div>;
    }

    if (error) {
        return <div className="portal-notice portal-notice-error">{error}</div>;
    }

    if (pages.length === 0) {
        return (
            <div className="portal-empty">
                <p>No pages are available yet. Please check back later.</p>
            </div>
        );
    }

    // Build hierarchy: top-level pages and their children
    const pageIds = new Set(pages.map((p) => p.id));
    const topLevel = pages.filter(
        (p) => p.parent_id === 0 || !pageIds.has(p.parent_id)
    );
    const childrenOf = (parentId) =>
        pages.filter((p) => p.parent_id === parentId);

    return (
        <div className="portal-pages">
            <h2>Content Pages</h2>
            <p className="portal-subtitle">Select a page to manage its content.</p>
            <div className="portal-page-list">
                {topLevel.map((parent) => {
                    const children = childrenOf(parent.id);
                    return (
                        <div key={parent.id} className="portal-page-group">
                            <PageRow page={parent} isChild={false} onEditPage={onEditPage} />
                            {children.map((child) => (
                                <PageRow key={child.id} page={child} isChild={true} onEditPage={onEditPage} />
                            ))}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
