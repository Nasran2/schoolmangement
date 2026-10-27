<div>
    <label for="{{ $filterPrefix }}-fee-type" class="block text-sm font-medium text-gray-700">Fee type</label>
    <select id="{{ $filterPrefix }}-fee-type" x-model="installment" @change="selected = []" class="mt-1 w-full rounded-md border-gray-300">
        <option value="exam_computer">Exam &amp; computer fees</option>
        <option value="exam">Exam fee only</option>
        <option value="computer">Computer fee only</option>
        <option value="facility">Facility fee only</option>
        <option value="all">All fees, including facility</option>
    </select>
</div>
<fieldset x-show="installment !== 'facility'" style="border:0;padding:0;margin:12px 0 0;">
    <legend class="block text-sm font-medium text-gray-700">Terms to include</legend>
    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-top:8px;">
        @foreach([1, 2, 3] as $term)
            <label style="display:flex;align-items:center;justify-content:center;gap:5px;padding:8px 5px;border:1px solid #dbe2ea;border-radius:6px;background:#f8fafc;font-size:12px;color:#334155;cursor:pointer;white-space:nowrap;">
                <input type="checkbox" x-model="terms" value="{{ $term }}" @change="selected = []" style="width:14px;height:14px;flex:none;" class="rounded border-gray-300 text-indigo-600"> Term {{ $term }}
            </label>
        @endforeach
    </div>
    <div style="display:flex;gap:14px;margin-top:8px;">
        <button type="button" @click="selectTerms(true)" class="text-xs font-semibold text-indigo-700 underline">All terms</button>
        <button type="button" @click="selectTerms(false)" class="text-xs text-gray-500 underline">Clear terms</button>
    </div>
    <p x-show="terms.length === 0" x-cloak style="margin-top:8px;font-size:12px;color:#92400e;" x-text="installment === 'all' ? 'No term fees selected. Annual facility fees are still included.' : 'Select at least one term to find matching students.'"></p>
</fieldset>
<div style="padding:9px 11px;border-radius:6px;background:#eef2ff;color:#3730a3;font-size:12px;" aria-live="polite" x-text="selectionSummary()"></div>
<p class="text-xs text-gray-500">Filters choose new entries. Payments already in your review list stay there until you remove or clear them.</p>
