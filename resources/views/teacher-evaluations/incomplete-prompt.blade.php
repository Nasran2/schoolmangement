<dialog x-ref="incompleteDialog" @cancel.prevent="closeIncompletePrompt()" role="alertdialog" aria-labelledby="incomplete-title" aria-describedby="incomplete-description" class="evaluation-save-dialog">
    <div class="evaluation-save-dialog-header"><span class="evaluation-save-warning-icon" aria-hidden="true">!</span><div><h3 id="incomplete-title">Some details are incomplete</h3><p id="incomplete-description">You can still save this evaluation. These fields have not been completed:</p></div></div>
    <ul class="evaluation-missing-list"><template x-for="(field, index) in missingDetails" :key="index"><li x-text="field"></li></template></ul>
    <p class="evaluation-save-dialog-note">Blank ratings do not count as zero. The overall score uses only fully rated supervisor reviews.</p>
    <div class="evaluation-save-dialog-actions"><button type="button" x-ref="returnToForm" @click="closeIncompletePrompt()" class="evaluation-return-button">Return to form</button><button type="button" @click="confirmIncompleteSave()" :disabled="saving" class="evaluation-confirm-button" x-text="saving ? 'Saving…' : 'Save anyway'">Save anyway</button></div>
</dialog>
@if(session('evaluation_missing_details'))
    <noscript><div class="evaluation-incomplete-notice" role="alert"><h3>Some details are incomplete</h3><ul>@foreach(session('evaluation_missing_details') as $missingField)<li>{{ $missingField }}</li>@endforeach</ul><p>You can still save this evaluation.</p><button type="submit" name="acknowledge_incomplete" value="1" class="evaluation-confirm-button">Save anyway</button></div></noscript>
@endif
