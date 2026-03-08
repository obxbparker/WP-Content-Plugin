import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    TextControl,
    TextareaControl,
    SelectControl,
    CheckboxControl,
    Card,
    CardBody,
    CardHeader,
    Spinner,
    Notice,
} from '@wordpress/components';
import {
    getBusinessProfile,
    saveBusinessProfile,
    getShareToken,
    generateShareToken,
    revokeShareToken,
} from '../api/client';

export default function BusinessProfile() {
    const [profile, setProfile] = useState({});
    const [schema, setSchema] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [activeStep, setActiveStep] = useState(0);
    const [shareUrl, setShareUrl] = useState(null);
    const [shareActive, setShareActive] = useState(false);
    const [shareCopied, setShareCopied] = useState(false);

    useEffect(() => {
        (async () => {
            try {
                const [profileRes, tokenRes] = await Promise.all([
                    getBusinessProfile(),
                    getShareToken(),
                ]);
                setProfile(profileRes.data || {});
                setSchema(profileRes.schema || {});
                setShareActive(tokenRes.active || false);
                setShareUrl(tokenRes.url || null);
                setError(null);
            } catch (err) {
                setError(err.message);
            }
            setLoading(false);
        })();
    }, []);

    const handleSave = async () => {
        if (!profile.company_name?.trim()) {
            setError('Company Name is required before saving.');
            return;
        }
        setSaving(true);
        try {
            await saveBusinessProfile(profile);
            setSuccess('Business profile saved.');
            setTimeout(() => setSuccess(null), 3000);
            setError(null);
        } catch (err) {
            setError(err.message);
        }
        setSaving(false);
    };

    const updateField = (key, value) => {
        setProfile((prev) => ({ ...prev, [key]: value }));
    };

    if (loading) {
        return (
            <div className="contenthub-wp-loading">
                <Spinner /> Loading business profile...
            </div>
        );
    }

    const sections = Object.entries(schema);
    const currentSection = sections[activeStep];

    return (
        <div className="contenthub-wp-business-profile">
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

            <h2>Business Profile</h2>
            <p>
                This information helps the AI generate relevant, on-brand content
                for your website pages. Fill out as much as you can.
            </p>

            {/* Share link */}
            <div className="contenthub-wp-share-section">
                {shareActive && shareUrl ? (
                    <div className="contenthub-wp-share-active">
                        <div className="contenthub-wp-share-url-row">
                            <TextControl
                                value={shareUrl}
                                readOnly
                                __nextHasNoMarginBottom
                            />
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    navigator.clipboard.writeText(shareUrl);
                                    setShareCopied(true);
                                    setTimeout(() => setShareCopied(false), 2000);
                                }}
                            >
                                {shareCopied ? 'Copied!' : 'Copy Link'}
                            </Button>
                            <Button
                                variant="tertiary"
                                isDestructive
                                onClick={async () => {
                                    const res = await revokeShareToken();
                                    setShareActive(false);
                                    setShareUrl(null);
                                }}
                            >
                                Revoke
                            </Button>
                        </div>
                        <p className="contenthub-wp-share-hint">
                            Share this link with your client to access the content portal and business profile.
                        </p>
                    </div>
                ) : (
                    <Button
                        variant="secondary"
                        onClick={async () => {
                            const res = await generateShareToken();
                            setShareActive(true);
                            setShareUrl(res.url);
                        }}
                    >
                        Generate Share Link
                    </Button>
                )}
            </div>

            {/* Step indicator */}
            <div className="contenthub-wp-steps">
                {sections.map(([key, section], index) => (
                    <button
                        key={key}
                        className={`contenthub-wp-step ${index === activeStep ? 'is-active' : ''} ${index < activeStep ? 'is-completed' : ''}`}
                        onClick={() => { setActiveStep(index); setSuccess(null); setError(null); }}
                    >
                        <span className="contenthub-wp-step-number">
                            {index + 1}
                        </span>
                        {section.label}
                    </button>
                ))}
            </div>

            {currentSection && (
                <Card>
                    <CardHeader>
                        <h3>{currentSection[1].label}</h3>
                    </CardHeader>
                    <CardBody>
                        {Object.entries(currentSection[1].fields).map(
                            ([fieldKey, fieldConfig]) => {
                                const value = profile[fieldKey] || '';

                                if (fieldConfig.type === 'select') {
                                    return (
                                        <SelectControl
                                            key={fieldKey}
                                            label={fieldConfig.label}
                                            value={value}
                                            options={[
                                                {
                                                    label: '— Select —',
                                                    value: '',
                                                },
                                                ...fieldConfig.options.map(
                                                    (opt) => ({
                                                        label: opt,
                                                        value: opt,
                                                    })
                                                ),
                                            ]}
                                            onChange={(v) =>
                                                updateField(fieldKey, v)
                                            }
                                            __nextHasNoMarginBottom
                                        />
                                    );
                                }

                                if (fieldConfig.type === 'url') {
                                    const noneKey = fieldKey.replace('_url', '').replace('url', '') + (fieldKey.includes('_url') ? '' : '_') + 'no_website';
                                    const isNone = !!profile['no_website'];
                                    return (
                                        <div key={fieldKey} style={{ marginBottom: '16px' }}>
                                            {!isNone && (
                                                <TextControl
                                                    label={fieldConfig.label}
                                                    value={value}
                                                    onChange={(v) => updateField(fieldKey, v)}
                                                    type="url"
                                                    placeholder="https://example.com"
                                                    __nextHasNoMarginBottom
                                                />
                                            )}
                                            {fieldConfig.allow_none && (
                                                <CheckboxControl
                                                    label={fieldConfig.none_label || 'N/A'}
                                                    checked={isNone}
                                                    onChange={(checked) => {
                                                        updateField('no_website', checked);
                                                        if (checked) updateField(fieldKey, '');
                                                    }}
                                                    __nextHasNoMarginBottom
                                                />
                                            )}
                                        </div>
                                    );
                                }

                                if (fieldConfig.type === 'textarea') {
                                    return (
                                        <TextareaControl
                                            key={fieldKey}
                                            label={fieldConfig.label}
                                            value={value}
                                            onChange={(v) =>
                                                updateField(fieldKey, v)
                                            }
                                            rows={3}
                                        />
                                    );
                                }

                                return (
                                    <div
                                        key={fieldKey}
                                        style={{
                                            display: 'flex',
                                            gap: '8px',
                                            alignItems: 'flex-end',
                                        }}
                                    >
                                        <div style={{ flex: 1 }}>
                                            <TextControl
                                                label={fieldConfig.label}
                                                value={value}
                                                onChange={(v) =>
                                                    updateField(fieldKey, v)
                                                }
                                                __nextHasNoMarginBottom
                                            />
                                        </div>
                                        {fieldConfig.allow_na && (
                                            <Button
                                                variant="tertiary"
                                                size="small"
                                                onClick={() =>
                                                    updateField(
                                                        fieldKey,
                                                        value === 'N/A'
                                                            ? ''
                                                            : 'N/A'
                                                    )
                                                }
                                            >
                                                {value === 'N/A'
                                                    ? 'Clear'
                                                    : 'N/A'}
                                            </Button>
                                        )}
                                    </div>
                                );
                            }
                        )}
                    </CardBody>
                </Card>
            )}

            <div className="contenthub-wp-step-nav">
                <Button
                    variant="secondary"
                    disabled={activeStep === 0}
                    onClick={() => { setActiveStep((s) => s - 1); setSuccess(null); setError(null); }}
                >
                    Previous
                </Button>
                {activeStep < sections.length - 1 ? (
                    <Button
                        variant="primary"
                        onClick={() => { setActiveStep((s) => s + 1); setSuccess(null); setError(null); }}
                    >
                        Next
                    </Button>
                ) : (
                    <Button
                        variant="primary"
                        onClick={handleSave}
                        isBusy={saving}
                    >
                        Save Profile
                    </Button>
                )}
            </div>
        </div>
    );
}
