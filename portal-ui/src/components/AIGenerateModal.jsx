import { useState, useEffect } from 'react';
import { getPageContext, savePageContext } from '../api/client';

export default function AIGenerateModal({ pageId, onGenerate, onClose }) {
    const [context, setContext] = useState({
        page_topic: '',
        target_keywords: '',
        page_goal: '',
        target_audience: '',
        reference_url: '',
        ai_notes: '',
    });
    const [loading, setLoading] = useState(true);
    const [generating, setGenerating] = useState(false);

    useEffect(() => {
        (async () => {
            try {
                const res = await getPageContext(pageId);
                if (res.context && Object.keys(res.context).length) {
                    setContext((prev) => ({ ...prev, ...res.context }));
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

    const handleGenerate = async () => {
        setGenerating(true);
        try {
            await savePageContext(pageId, context);
            await onGenerate(context);
        } catch (err) {
            // Error handling in parent.
        }
        setGenerating(false);
    };

    return (
        <div className="portal-modal-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className="portal-modal">
                <div className="portal-modal-header">
                    <h3>AI Content Generation</h3>
                    <button className="portal-modal-close" onClick={onClose}>&times;</button>
                </div>
                <div className="portal-modal-body">
                    {loading ? (
                        <div className="portal-loading">
                            <span className="portal-spinner"></span> Loading page context...
                        </div>
                    ) : (
                        <>
                            <p className="portal-modal-intro">
                                Help the AI understand this specific page. The more details you provide,
                                the better the generated content will be.
                            </p>

                            <div className="portal-field">
                                <label className="portal-field-label">What is this page about?</label>
                                <textarea
                                    value={context.page_topic}
                                    onChange={(e) => updateField('page_topic', e.target.value)}
                                    placeholder="e.g., This page is about our commercial roofing services for businesses in the Midwest."
                                    rows={2}
                                />
                            </div>

                            <div className="portal-field">
                                <label className="portal-field-label">Target Keywords</label>
                                <input
                                    type="text"
                                    value={context.target_keywords}
                                    onChange={(e) => updateField('target_keywords', e.target.value)}
                                    placeholder="e.g., commercial roofing, flat roof repair, industrial roofing contractor"
                                />
                            </div>

                            <div className="portal-field">
                                <label className="portal-field-label">Primary Goal of This Page</label>
                                <select
                                    value={context.page_goal}
                                    onChange={(e) => updateField('page_goal', e.target.value)}
                                >
                                    <option value="">— Select —</option>
                                    <option value="inform">Inform visitors about a service or product</option>
                                    <option value="convert">Convert visitors into leads or customers</option>
                                    <option value="educate">Educate visitors on a topic</option>
                                    <option value="sell">Sell a specific product or service</option>
                                    <option value="support">Provide support or answer questions</option>
                                </select>
                            </div>

                            <div className="portal-field">
                                <label className="portal-field-label">Target Audience for This Page</label>
                                <input
                                    type="text"
                                    value={context.target_audience}
                                    onChange={(e) => updateField('target_audience', e.target.value)}
                                    placeholder="e.g., Property managers and building owners looking for roof replacement"
                                />
                            </div>

                            <div className="portal-field">
                                <label className="portal-field-label">Reference URL</label>
                                <input
                                    type="url"
                                    value={context.reference_url}
                                    onChange={(e) => updateField('reference_url', e.target.value)}
                                    placeholder="https://example.com/similar-page"
                                />
                            </div>

                            <div className="portal-field">
                                <label className="portal-field-label">Notes to AI</label>
                                <textarea
                                    value={context.ai_notes}
                                    onChange={(e) => updateField('ai_notes', e.target.value)}
                                    placeholder="e.g., Only look at the services section of the reference page I gave you. Don't mention competitor brands. Focus on our 25 years of experience."
                                    rows={3}
                                />
                            </div>

                            <div className="portal-modal-actions">
                                <button
                                    className="portal-btn portal-btn-secondary"
                                    onClick={onClose}
                                    disabled={generating}
                                >
                                    Cancel
                                </button>
                                <button
                                    className="portal-btn portal-btn-primary"
                                    onClick={handleGenerate}
                                    disabled={generating}
                                >
                                    {generating ? 'Generating...' : 'Generate Content'}
                                </button>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
