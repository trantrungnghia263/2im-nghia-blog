<?php
$this->assign('title', __('Edit User #{0}', $user->id));
$this->assign('description', '');
$this->assign('content_title', __('Edit User #{0}', $user->id));

?>

<div class="box box-primary">
    <div class="box-body">

        <?= $this->Form->create($user); ?>

        <?= $this->Form->hidden('id') ?>
        
        <?=
        $this->Form->input('role', [
            'label' => __('Role'),
            'options' => [
                'member' => __('Member'),
                'admin' => __('Admin')
            ],
            'class' => 'form-control'
        ]);

        ?>

        <?=
        $this->Form->input('status', [
            'label' => __('Status'),
            'options' => [
                1 => __('Active'),
                2 => __('Pending'),
                3 => __('Inactive')
            ],
            'class' => 'form-control'
        ]);

        ?>

        <?=
        $this->Form->input('username', [
            'label' => __('Username'),
            'class' => 'form-control'
        ])

        ?>

        <?=
        $this->Form->input('email', [
            'label' => __('Email'),
            'type' => 'email',
            'class' => 'form-control'
        ])

        ?>

        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']); ?>

        <?= $this->Form->end(); ?>
    </div>
</div>
