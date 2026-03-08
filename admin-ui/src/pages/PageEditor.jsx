import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    TabPanel,
    TextControl,
    TextareaControl,
    Spinner,
    Notice,
} from '@wordpress/components';
import {
    getPageContent,
    savePageContent,
    scrapePage,
    generatePageContent,
    deployPage,
    rollbackPage,
    getMapping,
} from '../api/client';
import LivePreview from '../components/LivePreview';
import AIGenerateModal from '../components/AIGenerateModal';

export default function PageEditor({ pageId, onBack }) {
    const [page, setPage] = useState(null);
    const [content, setContent] = useState({});
    const [mapping, setMapping] = useState([]);
    const [source, setSource] = useState('');
    const [status, setStatus] = useState('');
    const [scrapeUrl, setScrapeUrl] = useState('');
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [showAIModal, setShowAIModal] = useState(false);
    const [uploadFile, setUploadFile] = useState(null);
    const [uploadLoading, setUploadLoading] = useState(false);

    const loadData = async () => {
        setLoading(true);
        try {
            // Get page info via the WP REST API.
            const pageRes = await wp.apiFetch({ path: `/wp/v2/pages/${pageId}` });
            setPage(pageRes);

            // Get existing content.
            const contentRes = await getPageContent(pageId);
            setContent(contentRes.data || {});
            setSource(contentRes.source || '');
            setStatus(contentRes.status || '');

            // Get field mapping for this page's template type.
            const templateSlug =
                contentRes.data?._template_slug ||
                (await getPageTemplateMeta(pageId));
            if (templateSlug) {
                const mappingRes = await getMapping(templateSlug);
                setMapping(mappingRes);
            }

            setError(null);
        } catch (err) {
            setError(err.message);
        }
        setLoading(false);
    };

    useEffect(() => { loadData(); }, [pageId]);

    const getPageTemplateMeta = async (id) => {
        try {
            const res = await wp.apiFetch({
                path: `contenthub-wp/v1/pages`,
            });
            const p = res.find((pg) => pg.id === id);
            return p?.template_type || '';
        } catch {
            return '';
        }
    };

    // Get unique content fields from mapping.
    const getContentFields = () => {
        const seen = new Set();
        return mapping.filter((m) => {
            if (seen.has(m.content_field_name)) return false;
            seen.add(m.content_field_name);
            return true;
        });
    };

    const handleScrape = async () => {
        if (!scrapeUrl.trim()) return;
        setActionLoading(true);
        setError(null);
        try {
            const result = await scrapePage(pageId, scrapeUrl.trim());
            setContent(result.data);
            setSource('scraped');
            setStatus('ready');
            setSuccess('Content scraped and extracted successfully.');
        } catch (err) {
            setError(err.message || 'Scraping failed.');
        }
        setActionLoading(false);
    };

    const handleGenerate = async (pageContext) => {
        setShowAIModal(false);
        setActionLoading(true);
        setError(null);
        try {
            const result = await generatePageContent(pageId, pageContext);
            setContent(result.data);
            setSource('ai_generated');
            setStatus('ready');
            setSuccess('Content generated successfully.');
        } catch (err) {
            setError(err.message || 'Generation failed.');
        }
        setActionLoading(false);
    };

    const handleSave = async () => {
        setActionLoading(true);
        try {
            await savePageContent(pageId, content, 'manual');
            setSource('manual');
            setStatus('ready');
            setSuccess('Content saved.');
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
            // Parse uploaded content — try JSON first, fall back to newline-separated key:value.
            let parsed = null;
            try {
                parsed = JSON.parse(text);
            } catch {
                // Try key: value format.
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
                await savePageContent(pageId, parsed, 'uploaded');
                setSuccess('Content uploaded and saved.');
            } else {
                setError('Could not parse the uploaded file. Use JSON or "Field Name: value" format.');
            }
        } catch (err) {
            setError(err.message || 'Upload failed.');
        }
        setUploadLoading(false);
    };

    const handleDeploy = async () => {
        setActionLoading(true);
        setError(null);
        try {
            await deployPage(pageId);
            setStatus('deployed');
            setSuccess('Content deployed to Elementor successfully!');
        } catch (err) {
            setError(err.message || 'Deployment failed.');
        }
        setActionLoading(false);
    };

    const handleRollback = async () => {
        setActionLoading(true);
        setError(null);
        try {
            await rollbackPage(pageId);
            setStatus('ready');
            setSuccess('Rolled back to previous Elementor data.');
        } catch (err) {
            setError(err.message || 'Rollback failed.');
        }
        setActionLoading(false);
    };

    const updateField = (fieldName, value) => {
        setContent((prev) => ({ ...prev, [fieldName]: value }));
    };

    if (loading) {
        return (
            <div className="contenthub-wp-loading">
                <Spinner /> Loading page editor...
            </div>
        );
    }

    const contentFields = getContentFields();

    return (
        <div className="contenthub-wp-page-editor">
            <div className="contenthub-wp-editor-header">
                <Button variant="tertiary" onClick={onBack}>
                    &larr; Back to Dashboard
                </Button>
                <h2>{page?.title?.rendered || `Page #${pageId}`}</h2>
                <div className="contenthub-wp-editor-actions">
                    {status === 'deployed' && (
                        <Button
                            variant="secondary"
                            isDestructive
                            onClick={handleRollback}
                            disabled={actionLoading}
                        >
                            Rollback
                        </Button>
                    )}
                    <Button
                        variant="primary"
                        onClick={handleDeploy}
                        disabled={actionLoading || !Object.keys(content).length}
                    >
                        Deploy to Elementor
                    </Button>
                </div>
            </div>

            {error && (
                <Notice status="error" isDismissible onDismiss={() => setError(null)}>
                    {error}
                </Notice>
            )}
            {success && (
                <Notice status="success" isDismissible onDismiss={() => setSuccess(null)}>
                    {success}
                </Notice>
            )}

            <TabPanel
                tabs={[
                    { name: 'scrape', title: 'Scrape' },
                    { name: 'write', title: 'Write' },
                    { name: 'generate', title: 'AI Generate' },
                    { name: 'upload', title: 'Upload' },
                ]}
            >
                {(tab) => (
                    <div className="contenthub-wp-tab-content">
                        {tab.name === 'scrape' && (
                            <div className="contenthub-wp-scrape-tab">
                                <p>
                                    Enter the URL of an existing page to scrape its
                                    content and map it to the template fields.
                                </p>
                                <div className="contenthub-wp-scrape-form">
                                    <TextControl
                                        label="URL to Scrape"
                                        value={scrapeUrl}
                                        onChange={setScrapeUrl}
                                        placeholder="https://example.com/page"
                                        __nextHasNoMarginBottom
                                    />
                                    <Button
                                        variant="primary"
                                        onClick={handleScrape}
                                        disabled={actionLoading || !scrapeUrl.trim()}
                                    >
                                        {actionLoading ? <Spinner /> : 'Scrape & Extract'}
                                    </Button>
                                </div>
                            </div>
                        )}

                        {tab.name === 'write' && (
                            <div className="contenthub-wp-write-tab">
                                <div className="contenthub-wp-write-fields">
                                    <p>Write content manually for each template field.</p>
                                    {contentFields.map((field) => (
                                        <div key={field.content_field_name} className="contenthub-wp-field">
                                            {field.content_field_type === 'repeater' ? (
                                                <RepeaterField
                                                    name={field.content_field_name}
                                                    widgetType={field.widget_type}
                                                    value={content[field.content_field_name] || []}
                                                    onChange={(val) =>
                                                        updateField(field.content_field_name, val)
                                                    }
                                                />
                                            ) : (field.content_field_name.includes('heading') ||
                                                field.content_field_name.includes('button') ||
                                                field.content_field_name.includes('cta')) ? (
                                                <TextControl
                                                    label={formatFieldLabel(field.content_field_name)}
                                                    value={content[field.content_field_name] || ''}
                                                    onChange={(val) =>
                                                        updateField(field.content_field_name, val)
                                                    }
                                                    __nextHasNoMarginBottom
                                                />
                                            ) : (
                                                <TextareaControl
                                                    label={formatFieldLabel(field.content_field_name)}
                                                    value={content[field.content_field_name] || ''}
                                                    onChange={(val) =>
                                                        updateField(field.content_field_name, val)
                                                    }
                                                    rows={4}
                                                />
                                            )}
                                        </div>
                                    ))}
                                    <Button
                                        variant="primary"
                                        onClick={handleSave}
                                        disabled={actionLoading}
                                    >
                                        Save Content
                                    </Button>
                                </div>
                                <LivePreview pageId={pageId} content={content} mapping={mapping} />
                            </div>
                        )}

                        {tab.name === 'generate' && (
                            <div className="contenthub-wp-generate-tab">
                                <p>
                                    Use AI to generate content for this page based on
                                    your business profile, page context, and template structure.
                                </p>
                                <Button
                                    variant="primary"
                                    onClick={() => setShowAIModal(true)}
                                    disabled={actionLoading}
                                >
                                    {actionLoading ? <Spinner /> : 'Configure & Generate'}
                                </Button>
                            </div>
                        )}

                        {tab.name === 'upload' && (
                            <div className="contenthub-wp-upload-tab">
                                <p>
                                    Upload a file containing your page content. Supported formats:
                                    JSON or plain text with "Field Name: value" per line.
                                </p>
                                <div className="contenthub-wp-upload-form">
                                    <input
                                        type="file"
                                        accept=".json,.txt,.csv,.md"
                                        onChange={(e) => setUploadFile(e.target.files?.[0] || null)}
                                    />
                                    <Button
                                        variant="primary"
                                        onClick={handleUpload}
                                        disabled={uploadLoading || !uploadFile}
                                    >
                                        {uploadLoading ? <Spinner /> : 'Upload & Apply'}
                                    </Button>
                                </div>
                            </div>
                        )}

                    </div>
                )}
            </TabPanel>

            {showAIModal && (
                <AIGenerateModal
                    pageId={pageId}
                    onGenerate={handleGenerate}
                    onClose={() => setShowAIModal(false)}
                />
            )}
        </div>
    );
}

function formatFieldLabel(name) {
    return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function RepeaterField({ name, widgetType, value, onChange }) {
    const isAccordion = ['accordion', 'toggle', 'tabs'].some((t) =>
        widgetType?.includes(t)
    ) || name.includes('faq') || name.includes('tab');

    const addItem = () => {
        const newItem = isAccordion ? { title: '', content: '' } : { text: '' };
        onChange([...value, newItem]);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    const updateItem = (index, field, val) => {
        onChange(
            value.map((item, i) =>
                i === index ? { ...item, [field]: val } : item
            )
        );
    };

    return (
        <div className="contenthub-wp-repeater">
            <label className="components-base-control__label">
                {formatFieldLabel(name)} ({value.length} items)
            </label>
            {value.map((item, i) => (
                <div key={i} className="contenthub-wp-repeater-item">
                    {isAccordion ? (
                        <>
                            <TextControl
                                label="Title"
                                value={item.title || ''}
                                onChange={(v) => updateItem(i, 'title', v)}
                                __nextHasNoMarginBottom
                            />
                            <TextareaControl
                                label="Content"
                                value={item.content || ''}
                                onChange={(v) => updateItem(i, 'content', v)}
                                rows={2}
                            />
                        </>
                    ) : (
                        <TextControl
                            label={`Item ${i + 1}`}
                            value={item.text || (typeof item === 'string' ? item : '')}
                            onChange={(v) =>
                                typeof item === 'string'
                                    ? onChange(value.map((x, j) => (j === i ? v : x)))
                                    : updateItem(i, 'text', v)
                            }
                            __nextHasNoMarginBottom
                        />
                    )}
                    <Button
                        variant="tertiary"
                        isDestructive
                        size="small"
                        onClick={() => removeItem(i)}
                    >
                        Remove
                    </Button>
                </div>
            ))}
            <Button variant="secondary" size="small" onClick={addItem}>
                + Add Item
            </Button>
        </div>
    );
}
