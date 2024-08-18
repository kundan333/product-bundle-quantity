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


    $(document).on('click', '.upload_image_button', function (e) {
        e.preventDefault();

        const button = $(this);
        const index = button.data('index');
        const file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or Upload Image',
            button: {
                text: 'Use this image',
            },
            multiple: false
        });

        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();
            button.siblings('.bundle_image_id').val(attachment.id);
            button.siblings('.bundle-image-preview').attr('src', attachment.url).show();
            button.siblings('.remove_image_button').show();
        });

        file_frame.open();
    });

    // Handle image removal
    $(document).on('click', '.remove_image_button', function (e) {
        e.preventDefault();
        const button = $(this);
        button.siblings('.bundle_image_id').val('');
        button.siblings('.bundle-image-preview').hide();
        button.hide();
    });


});