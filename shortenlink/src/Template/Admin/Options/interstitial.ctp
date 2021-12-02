<?php
$this->assign('title', __('Interstitial Advertisement Price'));
$this->assign('description', '');
$this->assign('content_title', __('Interstitial Advertisement Price'));

?>

<div class="box box-primary">
    <div class="box-body">

        <?= $this->Form->create($option); ?>

        <?= $this->Form->hidden('id'); ?>

        <?php $i = 1; ?>

        <div class="row">
            <?php foreach (get_countries(true) as $key => $value) : ?>
                <div class="col-sm-6">
                    <div class="row">
                        <div class="col-sm-4"><?= $value ?></div>
                        <div class="col-sm-4">
                            <?=
                            $this->Form->input('value[' . $key . '][advertiser]', [
                                'label' => false,
                                'class' => 'form-control',
                                'type' => 'text',
                                'placeholder' => 'Advertiser Price',
                                'value' => $option->value[$key]['advertiser']
                            ]);

                            ?>
                        </div>
                        <div class="col-sm-4">
                            <?=
                            $this->Form->input('value[' . $key . '][publisher]', [
                                'label' => false,
                                'class' => 'form-control',
                                'type' => 'text',
                                'placeholder' => 'Publisher Price',
                                'value' => $option->value[$key]['publisher']
                            ]);

                            ?>
                        </div>
                    </div>
                </div>
                <?= ( 0 == $i % 2 ) ? '</div><div class="row">' : ''; ?>
                <?php $i++; ?>
            <?php endforeach; ?>
        </div>

        <?= $this->Form->button(__('Save'), ['class' => 'btn btn-primary']); ?>

        <?= $this->Form->end(); ?>

    </div><!-- /.box-body -->
</div>
