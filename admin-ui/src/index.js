import { createRoot } from '@wordpress/element';
import App from './App';

const root = document.getElementById('contenthub-wp-root');
if (root) {
    createRoot(root).render(<App />);
}
