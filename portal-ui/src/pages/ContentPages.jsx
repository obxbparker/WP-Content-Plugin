import { useState, useEffect } from 'react';
import { getPages } from '../api/client';
import StatusBadge from '../components/StatusBadge';

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

    return (
        <div className="portal-pages">
            <h2>Content Pages</h2>
            <p className="portal-subtitle">Select a page to manage its content.</p>
            <div className="portal-page-grid">
                {pages.map((page) => (
                    <div key={page.id} className="portal-page-card" onClick={() => onEditPage(page)}>
                        <h3>{page.title}</h3>
                        <div className="portal-page-card-meta">
                            <span className="portal-page-card-type">{page.template_type || 'Unassigned'}</span>
                            <StatusBadge status={page.content_status} />
                        </div>
                        {page.content_source && (
                            <span className="portal-page-card-source">Source: {page.content_source}</span>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
