import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import DashboardApp from './dashboard';

const rootElement = document.getElementById('app');

if (rootElement) {
    createRoot(rootElement).render(
        <StrictMode>
            <DashboardApp />
        </StrictMode>,
    );
}
