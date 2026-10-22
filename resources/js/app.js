import './bootstrap';

import Alpine from 'alpinejs';
import { termCalendar, bulkClassFees } from './classFees';
import { initDashboardCharts } from './dashboard';
import { teacherPicker } from './teacherPicker';
import { teacherEvaluationForm } from './teacherEvaluation';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import 'tom-select/dist/css/tom-select.css';
import '../css/tom-select-overrides.css';
import '../css/teacher-evaluations.css';
import '../css/report-overview.css';
import { initSearchableSelects } from './searchableSelect';

window.Alpine = Alpine;
window.termCalendar = termCalendar;
window.bulkClassFees = bulkClassFees;
window.teacherPicker = teacherPicker;
window.teacherEvaluationForm = teacherEvaluationForm;
window.initSearchableSelects = initSearchableSelects;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
	initDashboardCharts();
	initSearchableSelects();

	// Datepicker (calendar + manual typing)
	document.querySelectorAll('input[data-datepicker]').forEach((el) => {
		flatpickr(el, {
			allowInput: true,
			dateFormat: 'Y-m-d',
		});
	});
});
