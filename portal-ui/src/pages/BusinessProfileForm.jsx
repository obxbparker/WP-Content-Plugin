import { useState, useEffect } from 'react';
import { getBusinessProfile, saveBusinessProfile } from '../api/client';

export default function BusinessProfileForm() {
    const [profile, setProfile] = useState({});
    const [schema, setSchema] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [activeStep, setActiveStep] = useState(0);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        (async () => {
            try {
                const res = await getBusinessProfile();
                setProfile(res.data || {});
                setSchema(res.schema || {});
            } catch (err) {
                setError(err.message);
            }
            setLoading(false);
        })();
    }, []);

    const updateField = (key, value) => {
        setProfile((prev) => ({ ...prev, [key]: value }));
    };

    const handleSave = async () => {
        if (!profile.company_name?.trim()) {
            setError('Company Name is required before submitting.');
            return;
        }
        setSaving(true);
        setError(null);
        try {
            await saveBusinessProfile(profile);
            setSuccess('Business profile submitted successfully. Thank you!');
            setTimeout(() => setSuccess(null), 5000);
        } catch (err) {
            setError(err.message);
        }
        setSaving(false);
    };

    const changeStep = (step) => {
        setActiveStep(step);
        setSuccess(null);
        setError(null);
    };

    if (loading) {
        return <div className="portal-loading"><span className="portal-spinner"></span> Loading form...</div>;
    }

    const sections = Object.entries(schema);
    const currentSection = sections[activeStep];

    return (
        <div className="portal-profile">
            <h2>Business Profile</h2>
            <p className="portal-subtitle">
                Help us understand your business so we can create content that sounds like you.
                Fill out what you can — every bit helps.
            </p>

            {error && <div className="portal-notice portal-notice-error">{error}</div>}
            {success && <div className="portal-notice portal-notice-success">{success}</div>}

            <div className="portal-steps">
                {sections.map(([key, section], index) => {
                    let cls = 'portal-step';
                    if (index === activeStep) cls += ' is-active';
                    if (index < activeStep) cls += ' is-completed';
                    return (
                        <button key={key} className={cls} onClick={() => changeStep(index)}>
                            <span className="portal-step-num">
                                {index < activeStep ? '✓' : index + 1}
                            </span>
                            <span className="portal-step-label">{section.label}</span>
                        </button>
                    );
                })}
            </div>

            {currentSection && (
                <div className="portal-card">
                    <div className="portal-card-header">
                        <h3>{currentSection[1].label}</h3>
                    </div>
                    <div className="portal-card-body">
                        {Object.entries(currentSection[1].fields).map(([fieldKey, config]) => {
                            const value = profile[fieldKey] || '';

                            if (config.type === 'select') {
                                return (
                                    <div key={fieldKey} className="portal-field">
                                        <label className="portal-field-label">{config.label}</label>
                                        <select
                                            value={value}
                                            onChange={(e) => updateField(fieldKey, e.target.value)}
                                        >
                                            <option value="">— Select —</option>
                                            {config.options.map((opt) => (
                                                <option key={opt} value={opt}>{opt}</option>
                                            ))}
                                        </select>
                                    </div>
                                );
                            }

                            if (config.type === 'url') {
                                const isNone = !!profile['no_website'];
                                return (
                                    <div key={fieldKey} className="portal-field" style={{ marginBottom: '16px' }}>
                                        {!isNone && (
                                            <>
                                                <label className="portal-field-label">{config.label}</label>
                                                <input
                                                    type="url"
                                                    value={value}
                                                    onChange={(e) => updateField(fieldKey, e.target.value)}
                                                    placeholder="https://example.com"
                                                />
                                            </>
                                        )}
                                        {config.allow_none && (
                                            <label className="portal-checkbox-label" style={{ display: 'flex', alignItems: 'center', gap: '8px', marginTop: '8px' }}>
                                                <input
                                                    type="checkbox"
                                                    checked={isNone}
                                                    onChange={(e) => {
                                                        updateField('no_website', e.target.checked);
                                                        if (e.target.checked) updateField(fieldKey, '');
                                                    }}
                                                />
                                                {config.none_label || 'N/A'}
                                            </label>
                                        )}
                                    </div>
                                );
                            }

                            if (config.type === 'textarea') {
                                return (
                                    <div key={fieldKey} className="portal-field">
                                        <label className="portal-field-label">{config.label}</label>
                                        <textarea
                                            value={value}
                                            onChange={(e) => updateField(fieldKey, e.target.value)}
                                            rows={3}
                                        />
                                    </div>
                                );
                            }

                            return (
                                <div key={fieldKey} className="portal-field-row">
                                    <div className="portal-field" style={{ flex: 1 }}>
                                        <label className="portal-field-label">
                                            {config.label}
                                            {config.required && <span className="portal-required">*</span>}
                                        </label>
                                        <input
                                            type="text"
                                            value={value}
                                            onChange={(e) => updateField(fieldKey, e.target.value)}
                                        />
                                    </div>
                                    {config.allow_na && (
                                        <button
                                            type="button"
                                            className={`portal-na-btn ${value === 'N/A' ? 'is-active' : ''}`}
                                            onClick={() => updateField(fieldKey, value === 'N/A' ? '' : 'N/A')}
                                        >
                                            {value === 'N/A' ? 'Clear' : 'N/A'}
                                        </button>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            <div className="portal-step-nav">
                <button
                    className="portal-btn portal-btn-secondary"
                    disabled={activeStep === 0}
                    onClick={() => changeStep(activeStep - 1)}
                >
                    Previous
                </button>
                {activeStep < sections.length - 1 ? (
                    <button
                        className="portal-btn portal-btn-primary"
                        onClick={() => changeStep(activeStep + 1)}
                    >
                        Next
                    </button>
                ) : (
                    <button
                        className="portal-btn portal-btn-primary"
                        onClick={handleSave}
                        disabled={saving}
                    >
                        {saving ? 'Submitting...' : 'Submit Profile'}
                    </button>
                )}
            </div>
        </div>
    );
}
