import './bootstrap';

import Alpine from 'alpinejs';
import { initDashboardCharts } from './dashboard';
import { teacherPicker } from './teacherPicker';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

window.Alpine = Alpine;
window.teacherPicker = teacherPicker;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
	initDashboardCharts();

	// Datepicker (calendar + manual typing)
	document.querySelectorAll('input[data-datepicker]').forEach((el) => {
		flatpickr(el, {
			allowInput: true,
			dateFormat: 'Y-m-d',
		});
	});
});
