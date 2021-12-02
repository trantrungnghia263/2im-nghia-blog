<?php
$this->assign('title', __('Campaign #{0}', $campaign->id));
$this->assign('description', '');
$this->assign('content_title', __('Campaign #{0}', $campaign->id));

?>


<div class="box box-primary checkout-form">
    <div class="box-header with-border">
        <i class="fa fa-credit-card"></i> <h3 class="box-title"><?= __('Payment Method') ?></h3>
    </div>
    <div class="box-body">

        <?=
        $this->Form->create(null, [
            'url' => ['controller' => 'Campaigns', 'action' => 'checkout'],
            'id' => 'checkout-form'
        ]);

        $this->Form->templates([
            'radioWrapper' => '<div class="radio">{{label}}</div>'
        ]);

        ?>

        <?= $this->Form->hidden('id', ['value' => $campaign->id]); ?>

        <?php
        $payment_methods = [];
        
        if( (bool) get_option('wallet_enable', false) ) {
            $payment_methods['wallet'] = "<b>" . __("My Wallet") . "</b>";
        }

        if (get_option('paypal_enable', 'no') == 'yes') {
            $payment_methods['paypal'] = '<img src="https://www.paypalobjects.com/webstatic/en_US/i/btn/png/silver-rect-paypal-34px.png" alt="PayPal">';
        }

        if (get_option('payza_enable', 'no') == 'yes') {
            $payment_methods['payza'] = '<img src="https://www.payza.com/images/payza-buy-now.png" alt="Payza">';
        }
        
        if( (bool) get_option('skrill_enable', false) ) {
            $payment_methods['skrill'] = '<img src="https://www.skrill.com/fileadmin/content/images/brand_centre/Skrill_Logos/skrill-85x37_en.gif" alt="Skrill">';
        }
        
        if (get_option('coinbase_enable', 'no') == 'yes') {
            $payment_methods['coinbase'] = '<img src="https://www.coinbase.com/assets/buttons/buy_now_small-cd4d106f61a3e365bf8af254c3d48d22c4207476e8e94a58e285c97c77f9b474.png" alt="Coinbase">';
        }
        
        if (get_option('webmoney_enable', 'no') == 'yes') {
            $payment_methods['webmoney'] = '<b>'.__('Web Money').'</b> <img src="https://www.wmtransfer.com/img/icons/wmlogo_32.png" alt="Webmoney">';
        }

        if (get_option('banktransfer_enable', 'no') == 'yes') {
            $payment_methods['banktransfer'] = "<b>" . __("Bank Transfer") . "</b>";
        }

        ?>

        <?=
        $this->Form->input('payment_method', [
            'type' => 'radio',
            'escape' => false,
            'options' => $payment_methods,
            'required' => 'required',
            'label' => false
        ]);

        ?>

        <?php if (get_option('banktransfer_enable', 'no') == 'yes') : ?>
            <div class="banktransfer_instructions" style="display: none;">
                <?php
                $visitors = 0;
                foreach ($campaign->campaign_items as $campaign_item) {
                    $visitors += $campaign_item->purchase * 1000;
                }

                $searchReplaceArray = array(
                    '[campaign_id]' => $campaign->id,
                    '[campaign_price]' => display_price_currency($campaign->price),
                    '[campaign_visitors]' => display_price_currency($visitors, ['places' => 0, 'before' => '']),
                );

                $banktransfer_instructions = str_replace(
                    array_keys($searchReplaceArray), array_values($searchReplaceArray), get_option('banktransfer_instructions')
                );

                ?>

                <?= $banktransfer_instructions ?>
            </div>
        <?php endif; ?>

        <p class="text-center">
            <?= $this->Form->button(__('Pay Campaign'), ['class' => 'btn btn-success btn-lg']); ?>
        </p>

        <?= $this->Form->end(); ?>

    </div><!-- /.box-body -->
</div>

<?php $this->start('scriptBottom'); ?>
<script>
    /**
     * Report invalid link
     */
    $("#checkout-form").on("submit", function (e) {
        e.preventDefault();
        var checkoutForm = $(this);

        var submitButton = checkoutForm.find('button');

        $.ajax({
            dataType: 'json', // The type of data that you're expecting back from the server.
            type: 'POST', // he HTTP method to use for the request
            url: checkoutForm.attr('action'),
            data: checkoutForm.serialize(), // Data to be sent to the server.
            beforeSend: function (xhr) {
                submitButton.attr("disabled", "disabled");
                $('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>').insertAfter($('.checkout-form .box-body'));
            },
            success: function (result, status, xhr) {
                //console.log( result );
                if (result.status === 'success') {

                    if (result.type === 'form') {
                        //console.log( result.message );
                        $(result.form).insertAfter(checkoutForm);
                        $('#checkout-redirect-form').submit();
                    }

                    if (result.type === 'url') {
                        //console.log( result.message );
                        window.location.href = result.url;
                    }

                    if (result.type === 'offline') {
                        //console.log( result.message );
                        window.location.href = result.url;
                    }

                } else {
                    alert(result.message);
                    submitButton.removeAttr("disabled");
                    $('.checkout-form').find('.overlay').remove();
                    checkoutForm[0].reset();
                }
            },
            error: function (xhr, status, error) {
                alert("An error occured: " + xhr.status + " " + xhr.statusText);
            },
            complete: function (xhr, status) {
                
            }
        });
    });

    $("#checkout-form").on("change", function (e) {
        var payment_method = $(this).find('input[name=payment_method]:checked').val();
        var banktransfer_instructions = $(this).find('.banktransfer_instructions');

        if (payment_method === 'banktransfer') {
            banktransfer_instructions.css("display", "block");
        } else {
            banktransfer_instructions.css("display", "none");
        }
    });

</script>
<?php $this->end(); ?>
