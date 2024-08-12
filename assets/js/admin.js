jQuery(document).ready(function($) {
    let bundleOptionsContainer = $('#bundle_quantity_options_container');
    let bundleOptionTemplate = wp.template('bundle-option-template');

    $('#add_bundle_option').on('click', function() {
        let index = bundleOptionsContainer.children().length;
        bundleOptionsContainer.append(bundleOptionTemplate({index: index}));
    });

    bundleOptionsContainer.on('click', '.remove-bundle-option', function() {
        $(this).closest('.bundle-option').remove();
    });
});