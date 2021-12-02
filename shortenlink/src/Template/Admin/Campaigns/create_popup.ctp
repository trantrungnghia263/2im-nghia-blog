<?php
$this->assign('title', __('Create Popup Campaign'));
$this->assign('description', '');
$this->assign('content_title', __('Create Popup Campaign'));

?>

<?php
$popup_price = get_option('popup_price');
$countries = get_countries(true);
$i = 0;

?>

<div class="box box-primary">
    <div class="box-body">

        <div class="callout callout-success">
            <h4><i class="fa fa-question-circle"></i> <?= __("Why views/earnings/Statistics/analytics are not counting?") ?></h4>
            <ul>
                <li><?= __("Important: <b>Default</b> campaigns will not count earnings so you need to create a non-default campaigns and earnings will start count.") ?></li>
                <li><?= __("Anonymous(not registered users) short links will not earn, only registered can earn.") ?></li>
                <li><?= __('Visitors must be unique within a 24 hours.') ?></li>
                <li><?= __('Visitors must have JavaScript enabled') ?></li>
                <li><?= __('Visitors must have Cookies enabled') ?></li>
                <li><?= __('Must view your website for at least {0} seconds', h(get_option('counter_value', 5))) ?></li>
            </ul>
        </div>

        <?= $this->Form->create($campaign, ['id' => 'campaign-create']); ?>
        
        <label><?= $this->Form->checkbox('default_campaign') ?> <?= __('Default Campaign') ?></label>
        <span class="help-block"><b><?= __('Default means that campaign will not count earning for publisher.') ?></b></span>
        
        <?=
        $this->Form->input('user_id', [
            'label' => __('User'),
            'options' => $users,
            'empty' => __( 'Choose' ),
            'class' => 'form-control'
        ]);

        ?>
        
        <?=
        $this->Form->input('name', [
            'label' => __('Campaign Name'),
            'class' => 'form-control'
        ]);

        ?>
        
        <?=
        $this->Form->input('status', [
            'label' => __('Status'),
            'options' => [
                1 => __('Active'),
                2 => __('Paused'),
                3 => __('Canceled'),
                4 => __('Finished'),
                5 => __('Under Review'),
                6 => __('Pending Payment'),
                7 => __('Invalid Payment'),
                8 => __('Refunded')
            ],
            'empty' => __('Choose'),
            'class' => 'form-control'
        ]);

        ?>

        <legend><?= __('Website Details') ?></legend>

        <?=
        $this->Form->input('website_title', [
            'label' => __('Title'),
            'class' => 'form-control'
        ]);

        ?>

        <?=
        $this->Form->input('website_url', [
            'label' => __('URL'),
            'class' => 'form-control',
            'type' => 'url'
        ]);

        ?>
        
        <legend><?= __('Advertising Rates') ?></legend>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th><?= __('Country') ?></th>
                    <th><?= __('Price / 1,000') ?></th>
                    <th><?= __('Purchase') ?></th>
                </tr>
            </thead>
            <?php foreach ($popup_price as $key => $value) : ?>
                <?php
                if (empty($value['advertiser'])) {
                    continue;
                }

                ?>
                <tr>
                    <td>
                        <?= $countries[$key] ?>
                        <?= $this->Form->hidden("campaign_items.$i.country", ['value' => $key]); ?>
                        <?= $this->Form->input("campaign_items.$i.advertiser_price", ['type' => 'hidden', 'value' => $value['advertiser']]); ?>
                        <?= $this->Form->input("campaign_items.$i.publisher_price", ['type' => 'hidden', 'value' => $value['publisher']]); ?>
                    </td>
                    <td>
                        <?= display_price_currency($value['advertiser']); ?>
                    </td>
                    <td>
                        <?=
                        $this->Form->input("campaign_items.$i.purchase", [
                            'label' => false,
                            'class' => 'form-control',
                            'type' => 'number'
                        ]);

                        ?>
                    </td>
                </tr>
                <?= ( 0 == $i % 2 ) ? '</div><div class="row">' : ''; ?>
                <?php $i++ ?>
            <?php endforeach; ?>
        </table>
        
        <?=
        $this->Form->input('traffic_source', [
            'label' => __('Traffic Sources'),
            'options' => [
                '1' => __('Desktop and Mobile'),
                '2' => __('Desktop'),
                '3' => __('Mobile')
            ],
            'empty' => __('Choose'),
            'class' => 'form-control'
        ]);

        ?>
        
        <div class="text-center">
            <p class="text-success" style="font-size: 23px;"><?= __("You have ordered {0} visitors for a total of {1}", "<span id='total-visitors'>0</span>", get_option('currency_symbol', '$')." <span id='total-price'>0.00</span>") ?></p>
            <?= $this->Form->button(__('Pay Campaign'), ['class' => 'btn btn-success btn-lg']); ?>
        </div>

        <?= $this->Form->end(); ?>
    </div><!-- /.box-body -->
</div>



