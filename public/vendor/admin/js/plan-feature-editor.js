function bindPlanFeatureEditor() {
    const syncRow = function ($row) {
        const assigned = $row.find('.js-feature-assigned').is(':checked');
        const unlimited = $row.find('.js-feature-unlimited').is(':checked');
        const $value = $row.find('.js-feature-value');

        $value.prop('disabled', !assigned || unlimited);
        $row.find('.js-feature-unlimited').prop('disabled', !assigned);
    };

    $(document).on('change', '.js-feature-assigned, .js-feature-unlimited', function () {
        syncRow($(this).closest('.plan-feature-row'));
    });

    $('.plan-feature-row').each(function () {
        syncRow($(this));
    });
}
