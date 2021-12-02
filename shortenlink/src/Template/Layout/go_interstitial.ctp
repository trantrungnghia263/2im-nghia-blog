<?php $user = $this->request->session()->read('Auth.User'); ?>
<!DOCTYPE html>
<html lang="<?= locale_get_primary_language(null) ?>">
    <head>
        <?= $this->Html->charset(); ?>
        <title><?= h($this->fetch('title')); ?></title>
        <meta name="robots" content="noindex, nofollow">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="<?= h($this->fetch('description')); ?>">
        <meta name="og:title" content="<?= h($this->fetch('og_title')); ?>">
        <meta name="og:description" content="<?= h($this->fetch('og_description')); ?>">
        <meta property="og:image" content="<?= h($this->fetch('og_image')); ?>" />
        <?php
        echo $this->Html->meta('icon');

        //echo $this->Html->css( 'base.css' );
        //echo $this->Html->css( 'cake.css' );
        echo $this->Html->css('//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/css/bootstrap.min.css');
        //echo $this->Html->css( '//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/css/bootstrap-theme.min.css' );
        if (get_option('language_direction') == 'rtl') {
            echo $this->Html->css('//cdn.rawgit.com/morteza/bootstrap-rtl/v3.4.0/dist/css/bootstrap-rtl.min.css');
            //echo $this->Html->css( '//cdn.rawgit.com/morteza/bootstrap-rtl/v3.4.0/dist/css/bootstrap-flipped.min.css' );
        }
        echo $this->Html->css('//cdn.rawgit.com/FortAwesome/Font-Awesome/v4.7.0/css/font-awesome.min.css');
        echo $this->Html->css('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/css/AdminLTE.min.css');
        echo $this->Html->css('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/css/skins/skin-blue.min.css');
        echo $this->Html->css('app.css?ver='.APP_VERSION);
        if (get_option('language_direction') == 'rtl') {
            echo $this->Html->css('app-rtl');
        }
        echo $this->fetch('meta');
        echo $this->fetch('css');
        echo $this->fetch('script');

        ?>

        <?= get_option('head_code'); ?>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="<?= (get_option('language_direction') == 'rtl' ? "rtl" : "") ?> frame layout-top-nav skin-blue">
        <?= get_option('after_body_tag_code'); ?>

        <div class="wrapper">


            <header class="main-header">
                <!-- Fixed navbar -->
                <nav class="navbar">
                    <div class="container">
                        
                        <div class="row is-table-row">
                            <div class="col-xs-6 col-sm-3">
                                <div class="navbar-header pull-left">
                                    <?php
                                    $logo = get_logo();
                                    $class= '';
                                    if( $logo['type'] == 'image' ) {
                                        $class = 'logo-image';
                                    }
                                    ?>
                                    <a class="navbar-brand <?= $class ?>" href="<?= $this->Url->build('/'); ?>"><?= $logo['content'] ?></a>
                                </div>
                            </div>
                            <div class="hidden-xs col-sm-6">
                                <?php if (!empty(get_option('interstitial_ads'))) : ?>
                                    <div class="banner banner-468x60">
                                        <div class="banner-inner">
                                            <?= get_option('interstitial_ads'); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="pull-right counter">
                                    <a href="" class="btn btn-default skip-ad" onclick="javascript: return false;"><?= __('Please Wait') ?></a>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </nav>
            </header>

            <?= $this->Flash->render() ?>
            <?= $this->fetch('content') ?>

        </div>
        
        <?= $this->Html->script('//ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js'); ?>
        <?= $this->Html->script('//cdn.rawgit.com/twbs/bootstrap/v3.3.7/dist/js/bootstrap.min.js'); ?>
        <?= $this->Html->script('//cdn.rawgit.com/zenorocha/clipboard.js/v1.5.12/dist/clipboard.min.js'); ?>
        
        <?= $this->element('js_vars'); ?>
        
        <?= $this->Html->script('app.js?ver='.APP_VERSION); ?>
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
        <?= $this->Html->script('//cdn.rawgit.com/almasaeed2010/AdminLTE/v2.3.11/dist/js/app.js'); ?>
        <?= $this->fetch('scriptBottom') ?>
        <?= get_option('footer_code'); ?>
    </body>
</html>
