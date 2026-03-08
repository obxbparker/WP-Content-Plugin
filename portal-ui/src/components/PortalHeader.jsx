export default function PortalHeader({ siteName, siteIconUrl, activeTab, onTabChange }) {
    return (
        <header className="portal-header">
            <div className="portal-header-brand">
                {siteIconUrl ? (
                    <img src={siteIconUrl} alt={siteName} className="portal-header-icon" />
                ) : (
                    <div className="portal-header-icon-placeholder">
                        {siteName?.charAt(0) || 'C'}
                    </div>
                )}
                <span className="portal-header-name">{siteName}</span>
            </div>
            <nav className="portal-nav">
                <button
                    className={`portal-nav-tab ${activeTab === 'profile' ? 'is-active' : ''}`}
                    onClick={() => onTabChange('profile')}
                >
                    Business Profile
                </button>
                <button
                    className={`portal-nav-tab ${activeTab === 'content' ? 'is-active' : ''}`}
                    onClick={() => onTabChange('content')}
                >
                    Content
                </button>
            </nav>
        </header>
    );
}
