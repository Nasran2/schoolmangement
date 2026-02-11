import TomSelect from 'tom-select';

function initSearchableSelects(root = document) {
	const selects = root.querySelectorAll('select[data-searchable-select]');
	selects.forEach((selectEl) => {
		if (selectEl.dataset.searchableSelectInitialized === '1') return;
		selectEl.dataset.searchableSelectInitialized = '1';

		// If the select is disabled or has < 2 options, skip.
		if (selectEl.disabled) return;
		if ((selectEl.options?.length ?? 0) < 2) return;

		const placeholder =
			selectEl.getAttribute('placeholder') ||
			selectEl.dataset.placeholder ||
			(selectEl.querySelector('option[value=""]')?.textContent || '').trim() ||
			'Select...';

		new TomSelect(selectEl, {
			plugins: ['dropdown_input'],
			maxItems: selectEl.multiple ? null : 1,
			create: false,
			allowEmptyOption: true,
			placeholder,
			closeAfterSelect: !selectEl.multiple,
		});
	});
}

export { initSearchableSelects };
