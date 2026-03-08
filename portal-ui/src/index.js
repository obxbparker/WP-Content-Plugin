import { createRoot } from 'react-dom/client';
import './portal.css';
import PortalApp from './PortalApp';

const container = document.getElementById('contenthub-portal');
if (container) {
    const root = createRoot(container);
    root.render(<PortalApp />);
}
