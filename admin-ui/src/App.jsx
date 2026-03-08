import { useState } from '@wordpress/element';
import Dashboard from './pages/Dashboard';
import TemplateSetup from './pages/TemplateSetup';
import PageEditor from './pages/PageEditor';
import BusinessProfile from './pages/BusinessProfile';
import Settings from './pages/Settings';

const TABS = [
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'templates', label: 'Templates' },
    { id: 'business-profile', label: 'Business Profile' },
    { id: 'settings', label: 'Settings' },
];

export default function App() {
    const [activeTab, setActiveTab] = useState('dashboard');
    const [editingPageId, setEditingPageId] = useState(null);

    const handleEditPage = (pageId) => {
        setEditingPageId(pageId);
        setActiveTab('page-editor');
    };

    const handleBackToDashboard = () => {
        setEditingPageId(null);
        setActiveTab('dashboard');
    };

    return (
        <div className="contenthub-wp-app">
            <div className="contenthub-wp-header">
                <h1>OBX ContentHub</h1>
                <nav className="contenthub-wp-nav">
                    {TABS.map((tab) => (
                        <button
                            key={tab.id}
                            className={`contenthub-wp-nav-tab ${activeTab === tab.id ? 'is-active' : ''}`}
                            onClick={() => {
                                setActiveTab(tab.id);
                                setEditingPageId(null);
                            }}
                        >
                            {tab.label}
                        </button>
                    ))}
                </nav>
            </div>

            <div className="contenthub-wp-content">
                {activeTab === 'dashboard' && (
                    <Dashboard onEditPage={handleEditPage} />
                )}
                {activeTab === 'templates' && <TemplateSetup />}
                {activeTab === 'page-editor' && editingPageId && (
                    <PageEditor
                        pageId={editingPageId}
                        onBack={handleBackToDashboard}
                    />
                )}
                {activeTab === 'business-profile' && <BusinessProfile />}
                {activeTab === 'settings' && <Settings />}
            </div>
        </div>
    );
}
