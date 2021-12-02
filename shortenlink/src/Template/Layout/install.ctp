<!DOCTYPE html>
<html>
    <head>
        <?= $this->Html->charset(); ?>
        <title><?= h($this->fetch('title')); ?></title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= h($this->fetch('description')); ?>">

        <?php
        echo $this->Html->meta('icon');

        //echo $this->Html->css( 'base.css' );
        //echo $this->Html->css( 'cake.css' );
        echo $this->Html->css('//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/css/bootstrap.min.css');
        //echo $this->Html->css( '//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/css/bootstrap-theme.min.css' );
        if (get_option('language_direction', 'ltr') == 'rtl') {
            echo $this->Html->css('//cdn.rawgit.com/morteza/bootstrap-rtl/v3.4.0/dist/css/bootstrap-rtl.min.css');
            //echo $this->Html->css( '//cdn.rawgit.com/morteza/bootstrap-rtl/v3.4.0/dist/css/bootstrap-flipped.min.css' );
        }
        echo $this->Html->css('//cdn.rawgit.com/FortAwesome/Font-Awesome/v4.7.0/css/font-awesome.min.css');
        echo $this->Html->css('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/css/AdminLTE.min.css');
        echo $this->Html->css('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/css/skins/skin-blue.min.css');
        echo $this->Html->css('app.css?ver='.APP_VERSION);
        if (get_option('language_direction', 'ltr') == 'rtl') {
            echo $this->Html->css('app-rtl');
        }
        echo $this->fetch('meta');
        echo $this->fetch('css');
        echo $this->fetch('script');

        ?>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="installation  login-page">

        <div class="login-box">
            <div class="login-logo">
                <?= h($this->fetch('title')); ?>
            </div>
            <!-- /.login-logo -->
            <div class="login-box-body">
                <?= $this->Flash->render() ?>

                <?= $this->fetch('content') ?>

                <hr>

                <div class="text-center">
                    <?= __('Copyright &copy;') ?> <?php echo date('Y'); ?> | <?= __('Developed by') ?> <a href="http://www.mightyscripts.com" target="_blank">MightyScripts</a>
                </div>
            </div>
            <!-- /.login-box-body -->
        </div>
        <!-- /.login-box -->

        <?= $this->Html->script('//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js'); ?>
        <?= $this->Html->script('//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/js/bootstrap.min.js'); ?>
        <?= $this->Html->script('//cdn.rawgit.com/zenorocha/clipboard.js/v1.5.12/dist/clipboard.min.js'); ?>
        <?= $this->element('js_vars'); ?>
        <?= $this->Html->script('app.js?ver='.APP_VERSION); ?>
        <?= $this->Html->script('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/js/app.js'); ?>
        <?= $this->fetch('scriptBottom') ?>
    </body>
</html>
