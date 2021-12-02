<!DOCTYPE html>
<html lang="<?= locale_get_primary_language(null) ?>">
    <head>
        <meta name="robots" content="noindex, nofollow">
        <meta name="og:title" content="<?= h($this->fetch('og_title')); ?>">
        <meta name="og:description" content="<?= h($this->fetch('og_description')); ?>">
        <meta property="og:image" content="<?= h($this->fetch('og_image')); ?>" />
        <?= $this->element('front_head'); ?>
    </head>
    <body class="interstitial-page">
        <?= get_option('after_body_tag_code'); ?>

        <nav id="mainNav" class="navbar navbar-default">
            <div class="container">

                <div class="row is-table-row">
                    <div class="col-xs-6 col-sm-3">
                        <div class="navbar-header pull-left">
                            <?php
                            $logo = get_logo();
                            $class = '';
                            if ($logo['type'] == 'image') {
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
                        <div class="pull-right">
                            <div class="skip-ad">
                                <div class="text-center">
                                    <span><?= __('Please Wait') ?></span><br>
                                    <span class="counter">Skip</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </nav>

        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>

        <?= $this->Html->script('/vendor/jquery.min.js'); ?>
        <?= $this->Html->script('/vendor/bootstrap/js/bootstrap.min.js'); ?>
        <?= $this->Html->script('/vendor/owl/owl.carousel.min.js'); ?>
        <?= $this->Html->script('/vendor/wow.min.js'); ?>
        <?= $this->Html->script('/vendor/clipboard.min.js'); ?>

        <script type='text/javascript'>
            /* <![CDATA[ */
            var app_vars = [];
            app_vars['base_url'] = '<?= $this->Url->build('/', true); ?>';
            app_vars['language'] = '<?= locale_get_default() ?>';
            app_vars['copy'] = '<?= __("Copy"); ?>';
            app_vars['copied'] = '<?= __("Copied!"); ?>';
            app_vars['user_id'] = '<?= $this->request->session()->read('Auth.User.id'); ?>';
            app_vars['home_shortening_register'] = '<?= ( get_option('home_shortening_register') == 'yes' ) ? 'yes' : 'no' ?>';
            /* ]]> */
        </script>
        <!-- Custom Theme JavaScript -->
        <?= $this->Html->script('front'); ?>
        <?= $this->Html->script('app.js?ver=' . APP_VERSION); ?>

        <?= $this->fetch('scriptBottom') ?>
        <?= get_option('footer_code'); ?>

    </body>

</html>
