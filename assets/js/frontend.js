jQuery(document).ready(function($) {
    const bundleOptions = $('.bundle-option');
    const totalPriceElement = $('.total-price');
    const addToCartButton = $('.add-to-cart-bundle');

    bundleOptions.on('click', function() {
        bundleOptions.removeClass('selected');
        $(this).addClass('selected');

        // var imageUrl = $(this).data('image');
        // $('.woocommerce-product-gallery__image img').attr('src', imageUrl);

        var imageUrl = $(this).data('image');

        selectProductImage(imageUrl);

        // Find the image in the WooCommerce product gallery
        var galleryImage = $('.woocommerce-product-gallery__image img[src="' + imageUrl + '"]');

        // Check if the image exists in the gallery
        if (galleryImage.length) {
            // Simulate a click on the gallery image to update the main product image
            galleryImage.closest('a').trigger('click');
        }


        const quantity = $(this).data('quantity');
        const discount = $(this).data('discount');
        const basePrice = $(this).data('price');

        // console.log("quantity : "+quantity);
        // console.log("discount : "+discount);
        // console.log("basePrice : "+basePrice);

        const originalPrice = basePrice * quantity;
        const discountedPrice = originalPrice * (1 - discount / 100);

        totalPriceElement.html(`
            <span class="original-price">${formatPrice(originalPrice)}</span>
            <span class="discounted-price">${formatPrice(discountedPrice)}</span>
            <span class="saving">Saving ${discount}%</span>
        `);
    });

    function selectProductImage(identifier) {
        var $gallery = $('.woocommerce-product-gallery');
        var $thumbnails = $gallery.find('.flex-control-thumbs').children();

        $thumbnails.each(function(index) {
            var $thumb = $(this).find('img');
            var thumbSrc = $thumb.attr('src');
            var fullSrc = thumbSrc.replace(/-\d+x\d+(?=\.[a-z]{3,4}$)/i, '');

            console.log('thumb url '+fullSrc);
            console.log('url '+identifier);

            if (identifier.startsWith('http')) {
                // If identifier is a URL
                if (fullSrc === identifier || thumbSrc === identifier) {
                    $thumb.trigger('click');
                    console.log("called thumb click");
                    return false; // Break the loop
                }
            } else {
                // If identifier is an attachment ID
                if ($thumb.attr('data-thumb-id') === identifier) {
                    $thumb.trigger('click');
                    return false; // Break the loop
                }
            }
        });
    }

    addToCartButton.on('click', function(e) {
        e.preventDefault();
        const selectedBundle = $('.bundle-option.selected');
        if (selectedBundle.length === 0) {
            alert('Please select a package');
            return;
        }

        // const productId = $('input[name="product_id"]').val();

        const productId = $(this).data('productid');

        console.log('productId '+productId);

        const index = selectedBundle.data('index');
        // const discount = selectedBundle.data('discount');



        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'add_bundle_to_cart',
                product_id: productId,
                bundle_index: index
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = wc_add_to_cart_params.cart_url;
                } else {
                    alert('Error adding to cart. Please try again.');
                }
            }
        });
    });

    function formatPrice(price) {
        return '$' + price.toFixed(2);
    }

    // Select the first option by default
    bundleOptions.first().click();
});

/*

jQuery(document).ready(function($) {
    // Handle bundle selection
    $('#bundle-quantity-options .bundle-option').on('click', function() {
        $('.bundle-option').removeClass('selected');
        $(this).addClass('selected');

        var quantity = $(this).data('quantity');
        var discount = $(this).data('discount');
        var originalPrice = parseFloat($('#price').data('original-price'));

        // Calculate the new price
        var discountedPrice = originalPrice - (originalPrice * (discount / 100));
        var totalPrice = discountedPrice * quantity;

        // Update the price display
        $('#bundle-price-display').text('Total: $' + totalPrice.toFixed(2));

        // Store selected bundle details in hidden inputs
        $('#bundle-quantity').val(quantity);
        $('#bundle-discount').val(discount);
    });

    // Add the bundle to the cart
    $('form.cart').on('submit', function(e) {
        e.preventDefault();

        var quantity = $('#bundle-quantity').val();
        var discount = $('#bundle-discount').val();

        if (!quantity || !discount) {
            alert('Please select a bundle option');
            return;
        }

        var form = $(this);

        // Adjust quantity before submitting the form
        form.find('input.qty').val(quantity);

        // Submit the form
        form.unbind('submit').submit();
    });
});
*/
