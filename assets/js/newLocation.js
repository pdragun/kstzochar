// Hides the "new location" fieldset of the invitation form behind a button,
// unless it has errors or values. Without JS the fieldset stays visible.
const fieldset = document.querySelector('fieldset.new-location');
const label = fieldset?.closest('form')?.dataset.newLocationLabel;

if (fieldset && label) {
    const hasErrors = fieldset.querySelector('.is-invalid, .invalid-feedback') !== null;
    const inputs = fieldset.querySelectorAll('input');
    let hasValues = false;
    for (let i = 0; i < inputs.length; i++) {
        hasValues = hasValues || inputs[i].value !== '';
    }

    if (!hasErrors && !hasValues) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary btn-sm mb-3';
        button.textContent = label;
        button.addEventListener('click', () => {
            fieldset.hidden = false;
            button.remove();
            fieldset.querySelector('input')?.focus();
        });
        fieldset.hidden = true;
        fieldset.before(button);
    }
}
