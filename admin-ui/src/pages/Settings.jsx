import { useState, useEffect } from '@wordpress/element';
import {
    Button,
    TextControl,
    ToggleControl,
    Card,
    CardBody,
    CardHeader,
    Spinner,
    Notice,
} from '@wordpress/components';
import { getSettings, saveSettings } from '../api/client';

export default function Settings() {
    const [apiKey, setApiKey] = useState('');
    const [hasApiKey, setHasApiKey] = useState(false);
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    useEffect(() => {
        (async () => {
            try {
                const res = await getSettings();
                setHasApiKey(res.has_api_key);
                setSettings(res.settings || {});
                setError(null);
            } catch (err) {
                setError(err.message);
            }
            setLoading(false);
        })();
    }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const data = { ...settings };
            if (apiKey) {
                data.api_key = apiKey;
            }
            const res = await saveSettings(data);
            setHasApiKey(res.has_api_key);
            setApiKey('');
            setSuccess('Settings saved.');
            setError(null);
        } catch (err) {
            setError(err.message);
        }
        setSaving(false);
    };

    if (loading) {
        return (
            <div className="contenthub-wp-loading">
                <Spinner /> Loading settings...
            </div>
        );
    }

    return (
        <div className="contenthub-wp-settings">
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

            <h2>Settings</h2>

            <Card>
                <CardHeader>
                    <h3>Claude API Key</h3>
                </CardHeader>
                <CardBody>
                    <p>
                        Enter your Anthropic API key to enable AI content
                        generation and scrape extraction. Get a key from{' '}
                        <a
                            href="https://console.anthropic.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            console.anthropic.com
                        </a>
                        .
                    </p>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '12px',
                            marginBottom: '8px',
                        }}
                    >
                        <span>
                            Status:{' '}
                            {hasApiKey ? (
                                <strong style={{ color: '#00a32a' }}>
                                    Configured
                                </strong>
                            ) : (
                                <strong style={{ color: '#d63638' }}>
                                    Not set
                                </strong>
                            )}
                        </span>
                    </div>
                    <TextControl
                        label={
                            hasApiKey
                                ? 'Replace API Key'
                                : 'API Key'
                        }
                        value={apiKey}
                        onChange={setApiKey}
                        placeholder="sk-ant-..."
                        type="password"
                        help="Your key is stored encrypted and never exposed to the browser."
                        __nextHasNoMarginBottom
                    />
                </CardBody>
            </Card>

            <Card style={{ marginTop: '16px' }}>
                <CardHeader>
                    <h3>Deployment Options</h3>
                </CardHeader>
                <CardBody>
                    <ToggleControl
                        label="Backup Elementor data before deployment"
                        help="Saves a copy of the current page data so you can rollback."
                        checked={settings.backup_before_deploy !== false}
                        onChange={(val) =>
                            setSettings((prev) => ({
                                ...prev,
                                backup_before_deploy: val,
                            }))
                        }
                    />
                    <ToggleControl
                        label="Clear Elementor cache after deployment"
                        help="Ensures the page displays the new content immediately."
                        checked={settings.clear_cache_after_deploy !== false}
                        onChange={(val) =>
                            setSettings((prev) => ({
                                ...prev,
                                clear_cache_after_deploy: val,
                            }))
                        }
                    />
                </CardBody>
            </Card>

            <div style={{ marginTop: '16px' }}>
                <Button variant="primary" onClick={handleSave} isBusy={saving}>
                    Save Settings
                </Button>
            </div>
        </div>
    );
}
