$(function () {
    let typeInput = $('select#massbill-type');
    let periodInputContainer = $('div#periodDropdownContainer');
    typeInput.on('change.check', function () {
        switch ($(this).val()) {
            case 'all_without_fines':
            case 'all_with_fines':
                periodInputContainer.hide();
                break;
            default: periodInputContainer.show();
        }
    });
});