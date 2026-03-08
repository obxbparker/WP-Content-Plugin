import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { previewPage } from '../api/client';

const DESKTOP_WIDTH = 1280;

export default function LivePreview({ pageId, content, mapping }) {
    const [previewUrl, setPreviewUrl] = useState(null);
    const [loading, setLoading] = useState(false);
    const [scale, setScale] = useState(1);
    const [stale, setStale] = useState(false);
    const wrapRef = useRef(null);
    const initialLoadDone = useRef(false);
    const contentRef = useRef(content);

    const updateScale = useCallback(() => {
        if (wrapRef.current) {
            const containerWidth = wrapRef.current.offsetWidth;
            setScale(Math.min(1, containerWidth / DESKTOP_WIDTH));
        }
    }, []);

    useEffect(() => {
        updateScale();
        window.addEventListener('resize', updateScale);
        return () => window.removeEventListener('resize', updateScale);
    }, [updateScale]);

    const fetchPreview = useCallback(async () => {
        if (!pageId || !mapping?.length || !Object.keys(contentRef.current).length) {
            return;
        }
        setLoading(true);
        setStale(false);
        try {
            const res = await previewPage(pageId, contentRef.current);
            if (res.url) {
                const sep = res.url.includes('?') ? '&' : '?';
                setPreviewUrl(res.url + sep + '_t=' + Date.now());
            }
        } catch (err) {
            console.error('Preview failed:', err);
        }
        setLoading(false);
    }, [pageId, mapping]);

    // Auto-load preview once when content first becomes available.
    useEffect(() => {
        contentRef.current = content;
        if (!initialLoadDone.current && mapping?.length && Object.keys(content).length) {
            initialLoadDone.current = true;
            fetchPreview();
        } else if (initialLoadDone.current) {
            setStale(true);
        }
    }, [content, mapping, fetchPreview]);

    useEffect(() => {
        if (previewUrl) updateScale();
    }, [previewUrl, updateScale]);

    return (
        <div className="contenthub-wp-preview-panel">
            <div className="contenthub-wp-preview-toolbar">
                <Button
                    variant="secondary"
                    size="small"
                    onClick={fetchPreview}
                    disabled={loading || !Object.keys(content).length}
                >
                    {loading ? <><Spinner /> Updating...</> : 'Refresh Preview'}
                </Button>
                {stale && !loading && (
                    <span className="contenthub-wp-preview-stale">Content has changed — click to refresh</span>
                )}
            </div>
            {previewUrl ? (
                <div
                    ref={wrapRef}
                    className="contenthub-wp-preview-iframe-wrap"
                >
                    <iframe
                        src={previewUrl}
                        title="Page Preview"
                        className="contenthub-wp-preview-iframe"
                        style={{
                            transform: `scale(${scale})`,
                            height: `${100 / scale}%`,
                        }}
                    />
                </div>
            ) : (
                <div ref={wrapRef} className="contenthub-wp-preview-empty">
                    <p>Start adding content to see a live preview of your page.</p>
                </div>
            )}
        </div>
    );
}
