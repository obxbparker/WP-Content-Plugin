import { useState, useEffect } from 'react';
import { getPortalConfig } from './api/client';
import PortalHeader from './components/PortalHeader';
import BusinessProfileForm from './pages/BusinessProfileForm';
import ContentPages from './pages/ContentPages';
import ContentEditor from './pages/ContentEditor';

export default function PortalApp() {
    const config = window.__CONTENTHUB_PORTAL__;
    const [activeTab, setActiveTab] = useState('content');
    const [editingPage, setEditingPage] = useState(null);
    const [portalConfig, setPortalConfig] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        (async () => {
            try {
                const cfg = await getPortalConfig();
                setPortalConfig(cfg);
            } catch (err) {
                console.error('Failed to load portal config:', err);
            }
            setLoading(false);
        })();
    }, []);

    const siteName = portalConfig?.site_name || config.siteName || 'ContentHub';
    const siteIconUrl = portalConfig?.site_icon_url || config.siteIconUrl || '';
    const aiAvailable = portalConfig?.ai_available || false;

    if (loading) {
        return (
            <div className="portal-app">
                <div className="portal-loading"><span className="portal-spinner"></span> Loading...</div>
            </div>
        );
    }

    return (
        <div className="portal-app">
            <PortalHeader
                siteName={siteName}
                siteIconUrl={siteIconUrl}
                activeTab={activeTab}
                onTabChange={(tab) => { setActiveTab(tab); setEditingPage(null); }}
            />
            <main className="portal-main">
                {activeTab === 'profile' && (
                    <BusinessProfileForm />
                )}
                {activeTab === 'content' && !editingPage && (
                    <ContentPages onEditPage={(page) => setEditingPage(page)} />
                )}
                {activeTab === 'content' && editingPage && (
                    <ContentEditor
                        page={editingPage}
                        aiAvailable={aiAvailable}
                        onBack={() => setEditingPage(null)}
                    />
                )}
            </main>
            <footer className="portal-footer">
                Powered by OBX ContentHub &middot; OuterBox
            </footer>
        </div>
    );
}
