import './bootstrap';

import Alpine from 'alpinejs';
import { initDashboardCharts } from './dashboard';
import { teacherPicker } from './teacherPicker';

window.Alpine = Alpine;
window.teacherPicker = teacherPicker;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
	initDashboardCharts();
});
