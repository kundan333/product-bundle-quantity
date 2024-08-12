jQuery(document).ready(function($) {
    const bundleOptions = $('.bundle-option');
    const totalPriceElement = $('.total-price');
    const addToCartButton = $('.add-to-cart-bundle');

    bundleOptions.on('click', function() {
        bundleOptions.removeClass('selected');
        $(this).addClass('selected');

        const quantity = $(this).data('quantity');
        const discount = $(this).data('discount');
        const basePrice = parseFloat($('input[name="base_price"]').val());

        const originalPrice = basePrice * quantity;
        const discountedPrice = originalPrice * (1 - discount / 100);

        totalPriceElement.html(`
            <span class="original-price">${formatPrice(originalPrice)}</span>
            <span class="discounted-price">${formatPrice(discountedPrice)}</span>
            <span class="saving">Saving ${discount}%</span>
        `);
    });

    addToCartButton.on('click', function(e) {
        e.preventDefault();
        const selectedBundle = $('.bundle-option.selected');
        if (selectedBundle.length === 0) {
            alert('Please select a package');
            return;
        }

        const productId = $('input[name="product_id"]').val();
        const quantity = selectedBundle.data('quantity');
        const discount = selectedBundle.data('discount');

        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'add_bundle_to_cart',
                product_id: productId,
                quantity: quantity,
                discount: discount
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
