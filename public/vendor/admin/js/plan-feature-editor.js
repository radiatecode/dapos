function bindPlanFeatureEditor() {
    const syncRow = function ($row) {
        const assigned = $row.find('.js-feature-assigned').is(':checked');
        const unlimited = $row.find('.js-feature-unlimited').is(':checked');
        const $value = $row.find('.js-feature-value');

        $value.prop('disabled', !assigned || unlimited);
        $row.find('.js-feature-unlimited').prop('disabled', !assigned);
    };

    const syncCheckAll = function ($section) {
        const $boxes = $section.find('.js-feature-assigned');
        const checked = $boxes.filter(':checked').length;
        const $all = $section.find('.js-feature-check-all');

        $all.prop('checked', $boxes.length > 0 && checked === $boxes.length);
        $all.prop('indeterminate', checked > 0 && checked < $boxes.length);
    };

    $(document).on('change', '.js-feature-assigned, .js-feature-unlimited', function () {
        const $row = $(this).closest('.plan-feature-row');

        syncRow($row);
        syncCheckAll($row.closest('[data-feature-section]'));
    });

    $(document).on('change', '.js-feature-check-all', function () {
        const checked = $(this).is(':checked');
        const $section = $(this).closest('[data-feature-section]');

        console.log($section.data('featureSection'));

        $(this).prop('indeterminate', false);
        $section.find('.js-feature-assigned').each(function () {
            const $row = $(this).closest('.plan-feature-row');

            $(this).prop('checked', checked);

            if (checked && ($section.data('featureSection') === 'boolean' || $section.data('featureSection') === 'report')) {
                $row.find('.js-feature-value').val('1');
            }

            syncRow($row);
        });
    });

    $('.plan-feature-row').each(function () {
        syncRow($(this));
    });

    $('[data-feature-section]').each(function () {
        syncCheckAll($(this));
    });
}
