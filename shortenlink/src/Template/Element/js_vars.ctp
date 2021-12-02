<script type='text/javascript'>
    /* <![CDATA[ */
    var app_vars = [];
    app_vars['base_url'] = '<?= $this->Url->build('/', true); ?>';
    app_vars['language'] = '<?= locale_get_default() ?>';
    app_vars['copy'] = '<?= __("Copy"); ?>';
    app_vars['copied'] = '<?= __("Copied!"); ?>';
    app_vars['user_id'] = '<?= $this->request->session()->read('Auth.User.id'); ?>';
    app_vars['home_shortening_register'] = '<?= ( get_option('home_shortening_register') == 'yes' ) ? 'yes' : 'no' ?>';
    app_vars['reCAPTCHA_site_key'] = '<?= get_option('reCAPTCHA_site_key'); ?>';
    /* ]]> */
</script>
