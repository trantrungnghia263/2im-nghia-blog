<?php
$this->assign('title', __('Edit Link: {0}', $link->alias));
$this->assign('description', '');
$this->assign('content_title', __('Edit Link: {0}', $link->alias));

?>

<div class="box box-primary">
    <div class="box-body">

        <?= $this->Form->create($link); ?>

        <?= $this->Form->hidden('id'); ?>

        <?=
        $this->Form->input('url', [
            'label' => __('Long URL'),
            'class' => 'form-control',
            'type' => 'url',
            'disabled' => 'disabled'
        ]);

        ?>
        
        <?=
        $this->Form->input('title', [
            'label' => __('Title'),
            'class' => 'form-control',
            'type' => 'text'
        ]);

        ?>
        
        <?=
        $this->Form->input('description', [
            'label' => __('Description'),
            'class' => 'form-control',
            'type' => 'textarea'
        ]);

        ?>

        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']); ?>

        <?= $this->Form->end(); ?>
    </div>
</div>
