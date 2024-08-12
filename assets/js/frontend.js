
jQuery(document).ready(function($) {
    $('.bundle-option').on('click', function() {
        $('.bundle-option').removeClass('selected');
        $(this).addClass('selected');

        let quantity = $(this).data('quantity');
        let discount = $(this).data('discount');

        // Update price display
        // Add to cart functionality
    });
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
