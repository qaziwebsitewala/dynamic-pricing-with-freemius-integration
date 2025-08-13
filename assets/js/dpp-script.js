(function ($) {
  'use strict';

  function formatEuro(amount) {
    return '€' + (Math.round(amount * 100) / 100).toFixed(2);
  }

  $(function () {
    $('.dpp-widget').each(function () {
      var $widget = $(this);
      var price = parseFloat($widget.data('price')) || 0;
      var $qty = $widget.find('.dpp-qty');
      var $total = $widget.find('.dpp-total');
      var $message = $widget.find('.dpp-message');
      var $addToCart = $widget.find('.dpp-add-to-cart');
      var $buyNow = $widget.find('.dpp-buy-now');

      function updateTotal() {
        var q = Math.max(parseInt($qty.val(), 10) || 1, 1);
        $total.text(formatEuro(price * q));
        $addToCart.attr('data-quantity', q);
        $buyNow.attr('data-quantity', q);
      }

      // Quantity buttons
      $widget.find('.dpp-plus').on('click', function () {
        var v = parseInt($qty.val(), 10) || 1;
        $qty.val(v + 1);
        updateTotal();
      });

      $widget.find('.dpp-minus').on('click', function () {
        var v = parseInt($qty.val(), 10) || 1;
        if (v > 1) {
          $qty.val(v - 1);
          updateTotal();
        }
      });

      $qty.on('input', updateTotal);

      // AJAX Add to Cart
      $addToCart.on('click', function (e) {
        e.preventDefault();
        var pid = parseInt($(this).data('product_id'), 10);
        var qty = Math.max(parseInt($qty.val(), 10) || 1, 1);

        // Ensure latest qty is set before sending to Woo
        $(this).attr('data-quantity', qty);

        $.ajax({
          type: 'POST',
          url: dppVars.ajaxUrl,
          dataType: 'json',
          data: {
            action: 'dpp_add_to_cart',
            product_id: pid,
            quantity: qty,
            nonce: dppVars.nonce
          },
          success: function (resp) {
            if (resp && resp.success) {
              $message.text(resp.data.message || 'Added to cart');

              // Replace Woo fragments if returned
              if (resp.data.fragments) {
                $.each(resp.data.fragments, function (key, value) {
                  $(key).replaceWith(value);
                });
              }

              // Trigger Woo event for other plugins/theme
              $(document.body).trigger('added_to_cart', [
                resp.data.fragments || {},
                resp.data.cart_hash || ''
              ]);

            } else {
              $message.text(resp.data && resp.data.message ? resp.data.message : 'Error.');
            }
          },
          error: function () {
            $message.text('Error.');
          }
        });
      });

      // AJAX Buy Now
      $buyNow.on('click', function (e) {
        e.preventDefault();
        var pid = parseInt($(this).data('product_id'), 10);
        var qty = Math.max(parseInt($qty.val(), 10) || 1, 1);

        $(this).attr('data-quantity', qty);

        $.ajax({
          type: 'POST',
          url: dppVars.ajaxUrl,
          dataType: 'json',
          data: {
            action: 'dpp_buy_now',
            product_id: pid,
            quantity: qty,
            nonce: dppVars.nonce
          },
          success: function (resp) {
            if (resp && resp.success && resp.data.checkout_url) {
              window.location.href = resp.data.checkout_url;
            } else {
              $message.text(resp.data && resp.data.message ? resp.data.message : 'Error.');
            }
          },
          error: function () {
            $message.text('Error.');
          }
        });
      });

      updateTotal();
    });
  });
})(jQuery);
