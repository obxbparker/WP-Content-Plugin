import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { previewPage } from '../api/client';

const DESKTOP_WIDTH = 1280;

export default function LivePreview({ pageId, content, mapping }) {
    const [previewUrl, setPreviewUrl] = useState(null);
    const [loading, setLoading] = useState(false);
    const [scale, setScale] = useState(1);
    const timerRef = useRef(null);
    const wrapRef = useRef(null);

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

    useEffect(() => {
        if (!pageId || !mapping?.length || !Object.keys(content).length) {
            return;
        }

        if (timerRef.current) {
            clearTimeout(timerRef.current);
        }

        timerRef.current = setTimeout(async () => {
            setLoading(true);
            try {
                const res = await previewPage(pageId, content);
                if (res.url) {
                    // Add cache-buster to force iframe reload.
                    const sep = res.url.includes('?') ? '&' : '?';
                    setPreviewUrl(res.url + sep + '_t=' + Date.now());
                }
            } catch (err) {
                console.error('Preview failed:', err);
            }
            setLoading(false);
        }, 1000);

        return () => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }
        };
    }, [pageId, content, mapping]);

    useEffect(() => {
        if (previewUrl) updateScale();
    }, [previewUrl, updateScale]);

    return (
        <div className="contenthub-wp-preview-panel">
            {loading && (
                <div className="contenthub-wp-preview-loading">
                    <Spinner /> Updating preview...
                </div>
            )}
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
                <div className="contenthub-wp-preview-empty">
                    <p>Start adding content to see a live preview of your page.</p>
                </div>
            )}
        </div>
    );
}
