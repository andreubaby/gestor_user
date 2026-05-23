import './bootstrap';
import Alpine from 'alpinejs';
import { initAutomationSequencesIndexPage, registerSequenceDashboardComponent } from './pages/automation/sequences-index';

registerSequenceDashboardComponent(Alpine);

window.Alpine = Alpine;
Alpine.start();

const runPageInitializers = () => {
	initAutomationSequencesIndexPage();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', runPageInitializers, { once: true });
} else {
	runPageInitializers();
}
