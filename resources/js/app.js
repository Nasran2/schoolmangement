import './bootstrap';

import Alpine from 'alpinejs';
import { initDashboardCharts } from './dashboard';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
	initDashboardCharts();
});
