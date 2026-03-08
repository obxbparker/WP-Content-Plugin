import { useState, useEffect, useRef } from 'react';
import {
    getPageContent,
    savePageContent,
    scrapePage,
    generatePageContent,
    previewPage,
    getMapping,
} from '../api/client';
import { formatFieldLabel, RepeaterField } from '../components/FieldRenderer';
import AIGenerateModal from '../components/AIGenerateModal';

export default function ContentEditor({ page, aiAvailable, onBack }) {
    const [content, setContent] = useState({});
    const [mapping, setMapping] = useState([]);
    const [source, setSource] = useState('');
    const [status, setStatus] = useState('');
    const [scrapeUrl, setScrapeUrl] = useState('');
    const [activeTab, setActiveTab] = useState('write');
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [showAIModal, setShowAIModal] = useState(false);
    const [uploadFile, setUploadFile] = useState(null);
    const [uploadLoading, setUploadLoading] = useState(false);
    const previewTimerRef = useRef(null);

    useEffect(() => {
        (async () => {
            try {
                const [contentRes, mappingRes] = await Promise.all([
                    getPageContent(page.id),
                    page.template_type ? getMapping(page.template_type) : Promise.resolve([]),
                ]);
                setContent(contentRes.data || {});
                setSource(contentRes.source || '');
                setStatus(contentRes.status || '');
                setMapping(mappingRes);
            } catch (err) {
                setError(err.message);
            }
            setLoading(false);
        })();
    }, [page.id]);

    // Debounced preview update
    useEffect(() => {
        if (!mapping.length || !Object.keys(content).length) return;

        if (previewTimerRef.current) clearTimeout(previewTimerRef.current);
        previewTimerRef.current = setTimeout(async () => {
            setPreviewLoading(true);
            try {
                const res = await previewPage(page.id, content);
                if (res.url) setPreviewUrl(res.url);
            } catch (err) {
                console.error('Preview failed:', err);
            }
            setPreviewLoading(false);
        }, 1000);

        return () => {
            if (previewTimerRef.current) clearTimeout(previewTimerRef.current);
        };
    }, [content, mapping]);

    const getContentFields = () => {
        const seen = new Set();
        return mapping.filter((m) => {
            if (seen.has(m.content_field_name)) return false;
            seen.add(m.content_field_name);
            return true;
        });
    };

    const updateField = (fieldName, value) => {
        setContent((prev) => ({ ...prev, [fieldName]: value }));
    };

    const handleScrape = async () => {
        if (!scrapeUrl.trim()) return;
        setActionLoading(true);
        setError(null);
        try {
            const result = await scrapePage(page.id, scrapeUrl.trim());
            setContent(result.data);
            setSource('scraped');
            setStatus('ready');
            setSuccess('Content scraped and extracted successfully.');
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError(err.message);
        }
        setActionLoading(false);
    };

    const handleGenerate = async (pageContext) => {
        setShowAIModal(false);
        setActionLoading(true);
        setError(null);
        try {
            const result = await generatePageContent(page.id, pageContext);
            setContent(result.data);
            setSource('ai_generated');
            setStatus('ready');
            setSuccess('Content generated successfully.');
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError(err.message);
        }
        setActionLoading(false);
    };

    const handleSave = async () => {
        setActionLoading(true);
        try {
            await savePageContent(page.id, content);
            setSource('manual');
            setStatus('ready');
            setSuccess('Content saved.');
            setTimeout(() => setSuccess(null), 3000);
        } catch (err) {
            setError(err.message);
        }
        setActionLoading(false);
    };

    const handleUpload = async () => {
        if (!uploadFile) return;
        setUploadLoading(true);
        setError(null);
        try {
            const text = await uploadFile.text();
            let parsed = null;
            try {
                parsed = JSON.parse(text);
            } catch {
                const lines = text.split('\n').filter((l) => l.trim());
                parsed = {};
                for (const line of lines) {
                    const colonIdx = line.indexOf(':');
                    if (colonIdx > 0) {
                        const key = line.slice(0, colonIdx).trim().toLowerCase().replace(/\s+/g, '_');
                        const val = line.slice(colonIdx + 1).trim();
                        parsed[key] = val;
                    }
                }
                if (!Object.keys(parsed).length) parsed = null;
            }

            if (parsed && typeof parsed === 'object') {
                setContent(parsed);
                setSource('uploaded');
                setStatus('ready');
                await savePageContent(page.id, parsed);
                setSuccess('Content uploaded and saved.');
                setTimeout(() => setSuccess(null), 3000);
            } else {
                setError('Could not parse the uploaded file. Use JSON or "Field Name: value" format.');
            }
        } catch (err) {
            setError(err.message || 'Upload failed.');
        }
        setUploadLoading(false);
    };

    if (loading) {
        return <div className="portal-loading"><span className="portal-spinner"></span> Loading editor...</div>;
    }

    const contentFields = getContentFields();
    const tabs = [
        { key: 'scrape', label: 'Scrape' },
        { key: 'write', label: 'Write' },
    ];
    if (aiAvailable) {
        tabs.push({ key: 'generate', label: 'AI Generate' });
    }
    tabs.push({ key: 'upload', label: 'Upload' });

    return (
        <div className="portal-editor">
            <div className="portal-editor-header">
                <button className="portal-btn-link" onClick={onBack}>&larr; Back to Pages</button>
                <h2>{page.title}</h2>
            </div>

            {error && <div className="portal-notice portal-notice-error">{error}</div>}
            {success && <div className="portal-notice portal-notice-success">{success}</div>}

            <div className="portal-tabs">
                {tabs.map((tab) => (
                    <button
                        key={tab.key}
                        className={`portal-tab ${activeTab === tab.key ? 'is-active' : ''}`}
                        onClick={() => { setActiveTab(tab.key); setError(null); setSuccess(null); }}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="portal-tab-content">
                {activeTab === 'scrape' && (
                    <div className="portal-scrape-tab">
                        <p>Enter the URL of an existing page to scrape its content and map it to the template fields.</p>
                        <div className="portal-scrape-form">
                            <div className="portal-field" style={{ flex: 1 }}>
                                <label className="portal-field-label">URL to Scrape</label>
                                <input
                                    type="url"
                                    value={scrapeUrl}
                                    onChange={(e) => setScrapeUrl(e.target.value)}
                                    placeholder="https://example.com/page"
                                />
                            </div>
                            <button
                                className="portal-btn portal-btn-primary"
                                onClick={handleScrape}
                                disabled={actionLoading || !scrapeUrl.trim()}
                            >
                                {actionLoading ? 'Scraping...' : 'Scrape & Extract'}
                            </button>
                        </div>
                    </div>
                )}

                {activeTab === 'write' && (
                    <div className="portal-write-tab">
                        <div className="portal-write-fields">
                            <p>Write content manually for each template field.</p>
                            {contentFields.map((field) => (
                                <div key={field.content_field_name} className="portal-field-wrap">
                                    {field.content_field_type === 'repeater' ? (
                                        <RepeaterField
                                            name={field.content_field_name}
                                            widgetType={field.widget_type}
                                            value={content[field.content_field_name] || []}
                                            onChange={(val) => updateField(field.content_field_name, val)}
                                        />
                                    ) : (
                                        <div className="portal-field">
                                            <label className="portal-field-label">
                                                {formatFieldLabel(field.content_field_name)}
                                            </label>
                                            <textarea
                                                value={content[field.content_field_name] || ''}
                                                onChange={(e) => updateField(field.content_field_name, e.target.value)}
                                                rows={
                                                    field.content_field_name.includes('heading') ||
                                                    field.content_field_name.includes('button') ||
                                                    field.content_field_name.includes('cta')
                                                        ? 2 : 4
                                                }
                                            />
                                        </div>
                                    )}
                                </div>
                            ))}
                            <button
                                className="portal-btn portal-btn-primary"
                                onClick={handleSave}
                                disabled={actionLoading}
                            >
                                {actionLoading ? 'Saving...' : 'Save Content'}
                            </button>
                        </div>
                        <div className="portal-preview-panel">
                            {previewLoading && (
                                <div className="portal-preview-loading">
                                    <span className="portal-spinner"></span> Updating preview...
                                </div>
                            )}
                            {previewUrl ? (
                                <iframe
                                    src={previewUrl}
                                    title="Page Preview"
                                    className="portal-preview-iframe"
                                />
                            ) : (
                                <div className="portal-preview-empty">
                                    <p>Start adding content to see a live preview.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {activeTab === 'generate' && aiAvailable && (
                    <div className="portal-generate-tab">
                        <p>Use AI to generate content for this page based on your business profile, page context, and template structure.</p>
                        <button
                            className="portal-btn portal-btn-primary"
                            onClick={() => setShowAIModal(true)}
                            disabled={actionLoading}
                        >
                            {actionLoading ? 'Generating...' : 'Configure & Generate'}
                        </button>
                    </div>
                )}

                {activeTab === 'upload' && (
                    <div className="portal-upload-tab">
                        <p>
                            Upload a file containing your page content. Supported formats:
                            JSON or plain text with "Field Name: value" per line.
                        </p>
                        <div className="portal-upload-form">
                            <input
                                type="file"
                                accept=".json,.txt,.csv,.md"
                                onChange={(e) => setUploadFile(e.target.files?.[0] || null)}
                            />
                            <button
                                className="portal-btn portal-btn-primary"
                                onClick={handleUpload}
                                disabled={uploadLoading || !uploadFile}
                            >
                                {uploadLoading ? 'Uploading...' : 'Upload & Apply'}
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {showAIModal && (
                <AIGenerateModal
                    pageId={page.id}
                    onGenerate={handleGenerate}
                    onClose={() => setShowAIModal(false)}
                />
            )}
        </div>
    );
}
