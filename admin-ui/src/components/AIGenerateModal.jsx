import { useState, useEffect } from '@wordpress/element';
import {
    Modal,
    Button,
    TextControl,
    TextareaControl,
    SelectControl,
    Spinner,
} from '@wordpress/components';
import { getPageContext, savePageContext, uploadFile } from '../api/client';

export default function AIGenerateModal({ pageId, onGenerate, onClose }) {
    const [context, setContext] = useState({
        page_topic: '',
        target_keywords: '',
        page_goal: '',
        target_audience: '',
        reference_url: '',
        ai_notes: '',
        uploaded_file_id: 0,
    });
    const [loading, setLoading] = useState(true);
    const [generating, setGenerating] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [fileName, setFileName] = useState('');

    useEffect(() => {
        (async () => {
            try {
                const res = await getPageContext(pageId);
                if (res.context && Object.keys(res.context).length) {
                    setContext((prev) => ({ ...prev, ...res.context }));
                    if (res.context.uploaded_file_id) {
                        setFileName('Previously uploaded file');
                    }
                }
            } catch (err) {
                console.error('Failed to load page context:', err);
            }
            setLoading(false);
        })();
    }, [pageId]);

    const updateField = (key, value) => {
        setContext((prev) => ({ ...prev, [key]: value }));
    };

    const handleFileUpload = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true);
        try {
            const media = await uploadFile(pageId, file);
            updateField('uploaded_file_id', media.id);
            setFileName(file.name);
        } catch (err) {
            console.error('Upload failed:', err);
        }
        setUploading(false);
    };

    const handleGenerate = async () => {
        setGenerating(true);
        // Save context first, then trigger generation.
        try {
            await savePageContext(pageId, context);
            await onGenerate(context);
        } catch (err) {
            // Error handling is in the parent.
        }
        setGenerating(false);
    };

    return (
        <Modal
            title="AI Content Generation"
            onRequestClose={onClose}
            className="contenthub-wp-ai-modal"
            shouldCloseOnClickOutside={false}
        >
            {loading ? (
                <div style={{ textAlign: 'center', padding: '24px' }}>
                    <Spinner /> Loading page context...
                </div>
            ) : (
                <div className="contenthub-wp-ai-modal-body">
                    <p className="contenthub-wp-ai-modal-intro">
                        Help the AI understand this specific page. The more details you provide,
                        the better the generated content will be.
                    </p>

                    <TextareaControl
                        label="What is this page about?"
                        value={context.page_topic}
                        onChange={(v) => updateField('page_topic', v)}
                        placeholder="e.g., This page is about our commercial roofing services for businesses in the Midwest."
                        rows={2}
                    />

                    <TextControl
                        label="Target Keywords"
                        value={context.target_keywords}
                        onChange={(v) => updateField('target_keywords', v)}
                        placeholder="e.g., commercial roofing, flat roof repair, industrial roofing contractor"
                        __nextHasNoMarginBottom
                    />

                    <SelectControl
                        label="Primary Goal of This Page"
                        value={context.page_goal}
                        options={[
                            { label: '— Select —', value: '' },
                            { label: 'Inform visitors about a service or product', value: 'inform' },
                            { label: 'Convert visitors into leads or customers', value: 'convert' },
                            { label: 'Educate visitors on a topic', value: 'educate' },
                            { label: 'Sell a specific product or service', value: 'sell' },
                            { label: 'Provide support or answer questions', value: 'support' },
                        ]}
                        onChange={(v) => updateField('page_goal', v)}
                        __nextHasNoMarginBottom
                    />

                    <TextControl
                        label="Target Audience for This Page"
                        value={context.target_audience}
                        onChange={(v) => updateField('target_audience', v)}
                        placeholder="e.g., Property managers and building owners looking for roof replacement"
                        __nextHasNoMarginBottom
                    />

                    <TextControl
                        label="Reference URL"
                        value={context.reference_url}
                        onChange={(v) => updateField('reference_url', v)}
                        type="url"
                        placeholder="https://example.com/similar-page"
                        __nextHasNoMarginBottom
                    />

                    <TextareaControl
                        label="Notes to AI"
                        value={context.ai_notes}
                        onChange={(v) => updateField('ai_notes', v)}
                        placeholder="e.g., Only look at the services section of the reference page I gave you. Don't mention competitor brands. Focus on our 25 years of experience."
                        rows={3}
                    />

                    <div className="contenthub-wp-ai-modal-upload">
                        <label className="components-base-control__label">
                            Reference File
                        </label>
                        <div className="contenthub-wp-ai-modal-upload-row">
                            <input
                                type="file"
                                accept=".txt,.pdf,.doc,.docx,.csv,.md"
                                onChange={handleFileUpload}
                                disabled={uploading}
                            />
                            {uploading && <Spinner />}
                            {fileName && !uploading && (
                                <span className="contenthub-wp-ai-modal-filename">
                                    {fileName}
                                </span>
                            )}
                        </div>
                        <p className="contenthub-wp-ai-modal-hint">
                            Upload a text, PDF, or document file for the AI to reference.
                        </p>
                    </div>

                    <div className="contenthub-wp-ai-modal-actions">
                        <Button variant="secondary" onClick={onClose} disabled={generating}>
                            Cancel
                        </Button>
                        <Button
                            variant="primary"
                            onClick={handleGenerate}
                            isBusy={generating}
                            disabled={generating}
                        >
                            {generating ? 'Generating...' : 'Generate Content'}
                        </Button>
                    </div>
                </div>
            )}
        </Modal>
    );
}
