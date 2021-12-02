<?php

namespace App\Controller;

use App\Controller\FrontController;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;

class LinksController extends FrontController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Cookie');
        $this->loadComponent('Recaptcha');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->layout('front');
        $this->Auth->allow(['shorten', 'st', 'api', 'view', 'go', 'stats', 'popad']);
    }

    public function shorten()
    {
        $this->autoRender = false;

        $this->response->type('json');

        if (!$this->request->is('ajax')) {
            $content = [
                'status' => 'error',
                'message' => __('Bad Request.'),
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $user_id = 1;
        if (null !== $this->Auth->user('id')) {
            $user_id = $this->Auth->user('id');
        }
        

        if ( $user_id === 1 && (bool) get_option('enable_captcha_shortlink_anonymous', false) && isset_recaptcha() && !$this->Recaptcha->verify($this->request->data['g-recaptcha-response'])) {
            $content = [
                'status' => 'error',
                'message' => __('The CAPTCHA was incorrect. Try again'),
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }


        if ($user_id == 1 && get_option('home_shortening_register') === 'yes') {
            $content = [
                'status' => 'error',
                'message' => __('Bad Request.'),
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $user = $this->Links->Users->find()->where(['status' => 1, 'id' => $user_id])->first();

        if (!$user) {
            $content = [
                'status' => 'error',
                'message' => __('Invalid user'),
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $this->request->data['url'] = trim($this->request->data['url']);
        $this->request->data['url'] = str_replace(" ", "%20", $this->request->data['url']);
        $this->request->data['url'] = parse_url($this->request->data['url'], PHP_URL_SCHEME) === null ? 'http://' . $this->request->data['url'] : $this->request->data['url'];

        $domain = '';
        if (isset($this->request->data['domain'])) {
            $domain = $this->request->data['domain'];
        }
        if (!in_array($domain, get_multi_domains_list())) {
            $domain = '';
        }

        $linkWhere = [
            'user_id' => $user->id,
            'status' => 1,
            'ad_type' => $this->request->data['ad_type'],
            'url' => $this->request->data['url']
        ];

        if (isset($this->request->data['alias']) && strlen($this->request->data['alias']) > 0) {
            $linkWhere['alias'] = $this->request->data['alias'];
        }

        $link = $this->Links->find()->where($linkWhere)->first();

        if ($link) {
            $content = [
                'status' => 'success',
                'message' => '',
                'url' => get_short_url($link->alias, $domain)
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $link = $this->Links->newEntity();
        $data = [];

        $data['user_id'] = $user->id;
        $data['url'] = $this->request->data['url'];
        
        $data['domain'] = $domain;

        if (empty($this->request->data['alias'])) {
            $data['alias'] = $this->Links->geturl();
        } else {
            $data['alias'] = $this->request->data['alias'];
        }

        $data['ad_type'] = $this->request->data['ad_type'];
        $link->status = 1;
        $link->hits = 0;

        $linkMeta = [
            'title' => '',
            'description' => '',
            'image' => ''
        ];

        if ($user_id === 1 && get_option('disable_meta_home') === 'no') {
            $linkMeta = $this->Links->getLinkMeta($this->request->data['url']);
        }

        if ($user_id !== 1 && get_option('disable_meta_member') === 'no') {
            $linkMeta = $this->Links->getLinkMeta($this->request->data['url']);
        }

        $link->title = $linkMeta['title'];
        $link->description = $linkMeta['description'];
        $link->image = $linkMeta['image'];


        $link = $this->Links->patchEntity($link, $data);
        if ($this->Links->save($link)) {
            $content = [
                'status' => 'success',
                'message' => '',
                'url' => get_short_url($link->alias, $domain)
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $message = __('Invalid URL.');
        if ($link->errors()) {
            $error_msg = [];
            foreach ($link->errors() as $errors) {
                if (is_array($errors)) {
                    foreach ($errors as $error) {
                        $error_msg[] = $error;
                    }
                } else {
                    $error_msg[] = $errors;
                }
            }

            if (!empty($error_msg)) {
                $message = implode("<br>", $error_msg);
            }
        }

        $content = [
            'status' => 'error',
            'message' => $message,
            'url' => ''
        ];
        $this->response->body(json_encode($content));
        return $this->response;
    }

    public function st()
    {
        $this->autoRender = false;

        $message = '';

        if (!isset($this->request->query) || !isset($this->request->query['api']) || !isset($this->request->query['url'])) {
            $message = __('Invalid Request.');
            $this->set('message', $message);
            return;
        }

        $api = $this->request->query['api'];
        $url = urldecode($this->request->query['url']);

        $ad_type = get_option('member_default_advert', 1);
        if (isset($this->request->query['type'])) {
            if (array_key_exists($this->request->query['type'], get_allowed_ads())) {
                $ad_type = $this->request->query['type'];
            }
        }

        $user = $this->Links->Users->find()->where(['api_token' => $api, 'status' => 1])->first();

        if (!$user) {
            $message = __('Invalid API token.');
            $this->set('message', $message);
            return;
        }

        $url = trim($url);
        $url = str_replace(" ", "%20", $url);
        $url = parse_url($url, PHP_URL_SCHEME) === null ? 'http://' . $url : $url;

        $link = $this->Links->find()->where([
                'user_id' => $user->id,
                'status' => 1,
                'ad_type' => $ad_type,
                'url' => $url
            ])->first();

        if ($link) {
            return $this->redirect(get_short_url($link->alias));
        }

        $link = $this->Links->newEntity();
        $data = [];

        $data['user_id'] = $user->id;
        $data['url'] = $url;
        $data['alias'] = $this->Links->geturl();
        $data['ad_type'] = $ad_type;

        $link->status = 1;
        $link->hits = 0;

        $linkMeta = [
            'title' => '',
            'description' => '',
            'image' => ''
        ];

        if (get_option('disable_meta_api') === 'no') {
            $linkMeta = $this->Links->getLinkMeta($url);
        }

        $link->title = $linkMeta['title'];
        $link->description = $linkMeta['description'];
        $link->image = $linkMeta['image'];

        $link = $this->Links->patchEntity($link, $data);
        if ($this->Links->save($link)) {
            return $this->redirect(get_short_url($link->alias));
        }

        $message = __('Error.');
        $this->set('message', $message);
        return;
    }

    public function api()
    {
        $this->autoRender = false;

        $format = 'json';
        if( isset($this->request->query['format']) && strtolower($this->request->query['format']) === 'text' ) {
            $format = 'text';
        }
        $this->response->type($format);

        if (!isset($this->request->query) || !isset($this->request->query['api']) || !isset($this->request->query['url'])) {
            $content = [
                'status' => 'error',
                'message' => 'Invalid API call',
                'shortenedUrl' => ''
            ];
            $this->response->body($this->apiContent($content, $format));
            return $this->response;
        }

        $api = $this->request->query['api'];
        $url = urldecode($this->request->query['url']);

        $ad_type = get_option('member_default_advert', 1);
        if (isset($this->request->query['type'])) {
            if (array_key_exists($this->request->query['type'], get_allowed_ads())) {
                $ad_type = $this->request->query['type'];
            }
        }

        $user = $this->Links->Users->find()->where(['api_token' => $api, 'status' => 1])->first();

        if (!$user) {
            $content = [
                'status' => 'error',
                'message' => 'Invalid API token',
                'shortenedUrl' => ''
            ];
            $this->response->body($this->apiContent($content, $format));
            return $this->response;
        }

        $url = trim($url);
        $url = str_replace(" ", "%20", $url);
        $url = parse_url($url, PHP_URL_SCHEME) === null ? 'http://' . $url : $url;

        $link = $this->Links->find()->where([
                'url' => $url,
                'user_id' => $user->id,
                'ad_type' => $ad_type
            ])->first();

        if ($link) {
            $content = [
                'status' => 'success',
                'shortenedUrl' => get_short_url($link->alias, $link->domain)
            ];
            $this->response->body($this->apiContent($content, $format));
            return $this->response;
        }

        $link = $this->Links->newEntity();
        $data = [];

        $data['user_id'] = $user->id;
        $data['url'] = $url;
        if (empty($this->request->query['alias'])) {
            $data['alias'] = $this->Links->geturl();
        } else {
            $data['alias'] = $this->request->query['alias'];
        }
        $data['ad_type'] = $ad_type;

        $link->status = 1;
        $link->hits = 0;

        $linkMeta = [
            'title' => '',
            'description' => '',
            'image' => ''
        ];

        if (get_option('disable_meta_api') === 'no') {
            $linkMeta = $this->Links->getLinkMeta($url);
        }

        $link->title = $linkMeta['title'];
        $link->description = $linkMeta['description'];
        $link->image = $linkMeta['image'];

        $link = $this->Links->patchEntity($link, $data);

        if ($this->Links->save($link)) {
            $content = [
                'status' => 'success',
                'message' => '',
                'shortenedUrl' => get_short_url($link->alias, $link->domain)
            ];
            $this->response->body($this->apiContent($content, $format));
            return $this->response;
        }

        $content =[
            'status' => 'error',
            'message' => 'Invalid URL',
            'shortenedUrl' => ''
        ];
        $this->response->body($this->apiContent($content, $format));
        return $this->response;
    }
    
    protected function apiContent($content = [], $format = 'json')
    {
        $body = json_encode($content);
        if( $format === 'text' ) {
            $body = $content['shortenedUrl'];
        }
        return $body;
    }

    public function view($alias = null)
    {
        if (!$alias) {
            throw new NotFoundException(__('Invalid link'));
        }

        //$link = $this->Links->find()->where( ['alias' => $alias, 'status' => 1] )->contain(['Users'])->first();
        $link = $this->Links->find()->contain(['Users'])->where(['Links.alias' => $alias, 'Links.status <>' => 3])->first();
        if (!$link) {
            throw new NotFoundException(__('404 Not Found'));
        }
        $this->set('link', $link);

        $user = $this->Links->Users->find()->where(['id' => $link->user_id, 'status' => 1])->first();
        if (!$user) {
            throw new NotFoundException(__('404 Not Found'));
        }

        // No Ads
        if ($link->ad_type == 0) {
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $link->ad_type, [
                'ci' => 0,
                'cui' => 0,
                'cii' => 0,
                'ref' => (env('HTTP_REFERER')) ? env('HTTP_REFERER') : '',
                ], get_ip(), 10);
            return $this->redirect($link->url);
        }

        $this->viewBuilder()->layout('captcha');
        $this->render('captcha');

        if (!( (get_option('enable_captcha_shortlink') == 'yes') && isset_recaptcha() ) || $this->request->is('post')) {

            if ((get_option('enable_captcha_shortlink') == 'yes') && isset_recaptcha() && !$this->Recaptcha->verify($this->request->data['g-recaptcha-response'])) {
                throw new BadRequestException(__('The CAPTCHA was incorrect. Try again'));
            }

            //env('HTTP_REFERER', $this->request->data['ref']);

            $_SERVER['HTTP_REFERER'] = (!( (get_option('enable_captcha_shortlink') == 'yes') && isset_recaptcha() )) ? env('HTTP_REFERER') : $this->request->data['ref'];

            $this->_setVisitorCookie();

            $country = $this->Links->Statistics->get_country(get_ip());

            $detector = new \Detection\MobileDetect();
            if ($detector->isMobile()) {
                $traffic_source = 3;
            } else {
                $traffic_source = 2;
            }

            $campaign_item = $this->_getCampaignItem($link->ad_type, $traffic_source, $country);
            $this->set('campaign_item', $campaign_item);

            $pop_ad = [
                'link' => $link,
                'country' => $country,
                'traffic_source' => $traffic_source
            ];
            $this->set('pop_ad', $this->_encrypt($pop_ad));

            // Interstitial Ads
            if ($link->ad_type == 1) {
                $this->viewBuilder()->layout('go_interstitial');
                $this->render('view_interstitial');
            }

            // Banner Ads
            if ($link->ad_type == 2) {

                $banner_728x90 = get_option('banner_728x90', '');
                if ('728x90' == $campaign_item->campaign->banner_size) {
                    $banner_728x90 = $campaign_item->campaign->banner_code;
                }

                $banner_468x60 = get_option('banner_468x60', '');
                if ('468x60' == $campaign_item->campaign->banner_size) {
                    $banner_468x60 = $campaign_item->campaign->banner_code;
                }

                $banner_336x280 = get_option('banner_336x280', '');
                if ('336x280' == $campaign_item->campaign->banner_size) {
                    $banner_336x280 = $campaign_item->campaign->banner_code;
                }

                $this->set('banner_728x90', $banner_728x90);
                $this->set('banner_468x60', $banner_468x60);
                $this->set('banner_336x280', $banner_336x280);

                $this->viewBuilder()->layout('go_banner');
                $this->render('view_banner');
            }
        }
    }

    public function popad()
    {
        $this->autoRender = false;

        if ($this->request->is('post')) {
            $pop_ad = $this->_decrypt($this->request->data['pop_ad']);
            
            $campaign_item = $this->_getCampaignItem(3, $pop_ad['traffic_source'], $pop_ad['country']);
            $data = [
                'alias' => $pop_ad['link']->alias,
                'ci' => $campaign_item->campaign_id,
                'cui' => $campaign_item->campaign->user_id,
                'cii' => $campaign_item->id,
                'ref' => strtolower(env('HTTP_REFERER'))
            ];
            $content = $this->_calcEarnings($data, $pop_ad['link'], 3);
            
            return $this->redirect($campaign_item->campaign->website_url);
        }
        //die("Invalid Request");
    }

    protected function _encrypt($value)
    {
        $key = Security::salt();
        $value = serialize($value);
        $value = Security::encrypt($value, $key);
        return base64_encode($value);
    }

    protected function _decrypt($value)
    {
        $key = Security::salt();
        $value = base64_decode($value);
        $value = Security::decrypt($value, $key);
        return unserialize($value);
    }

    public function go()
    {
        $this->autoRender = false;
        $this->response->type('json');

        if (!$this->request->is('ajax')) {
            $content = [
                'status' => 'error',
                'message' => 'Bad Request.',
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        $link = $this->Links->find()->contain(['Users'])->where([
                'Links.alias' => $this->request->data['alias'],
                'Links.status <>' => 3
            ])->first();
        if (!$link) {
            $content = [
                'status' => 'error',
                'message' => '404 Not Found.',
                'url' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        $data = $this->request->data;
        
        $content = $this->_calcEarnings($data, $link, $link->ad_type);
        
        $this->response->body(json_encode($content));
        return $this->response;
    }
    
    protected function _getCampaignItem($ad_type, $traffic_source, $country)
    {
        $CampaignItems = TableRegistry::get('CampaignItems');

        $campaign_items = $CampaignItems->find()
            ->contain(['Campaigns'])
            ->where([
                'Campaigns.default_campaign' => 0,
                'Campaigns.ad_type' => $ad_type,
                'Campaigns.status' => 1,
                "Campaigns.traffic_source IN (1, :traffic_source)",
                'CampaignItems.weight <' => 100,
                'CampaignItems.country' => $country,
                //'Campaigns.user_id <>' => $link->user_id
            ])
            ->order(['CampaignItems.weight' => 'ASC'])
            ->bind(':traffic_source', $traffic_source, 'integer')
            ->limit(10)
            ->toArray();

        if (count($campaign_items) == 0) {
            $campaign_items = $CampaignItems->find()
                ->contain(['Campaigns'])
                ->where([
                    'Campaigns.default_campaign' => 0,
                    'Campaigns.ad_type' => $ad_type,
                    'Campaigns.status' => 1,
                    "Campaigns.traffic_source IN (1, :traffic_source)",
                    'CampaignItems.weight <' => 100,
                    'CampaignItems.country' => 'all',
                    //'Campaigns.user_id <>' => $link->user_id
                ])
                //->order(['CampaignItems.weight' => 'ASC'])
                ->bind(':traffic_source', $traffic_source, 'integer')
                ->limit(10)
                ->toArray();
        }

        if (count($campaign_items) == 0) {
            $campaign_items = $CampaignItems->find()
                ->contain(['Campaigns'])
                ->where([
                    'Campaigns.default_campaign' => 1,
                    'Campaigns.ad_type' => $ad_type,
                    'Campaigns.status' => 1,
                    "Campaigns.traffic_source IN (1, :traffic_source)",
                    'CampaignItems.weight <' => 100,
                    "CampaignItems.country IN ( 'all', :country)",
                    //'Campaigns.user_id <>' => $link->user_id
                ])
                ->order(['CampaignItems.weight' => 'ASC'])
                ->bind(':traffic_source', $traffic_source, 'integer')
                ->bind(':country', $country, 'string')
                ->limit(10)
                ->toArray();
        }

        shuffle($campaign_items);
        return array_values($campaign_items)[0];
    }
    
    protected function _calcEarnings($data, $link, $ad_type)
    {
        /**
         * Views reasons
         * 1- Earn
         * 2- Disabled cookie
         * 3- Anonymous user
         * 4- Adblock
         * 5- Proxy
         * 6- IP changed
         * 7- Not unique
         * 8- Full weight
         * 9- Default campaign
         * 10- Direct
         */
        /**
         * Check if cookie valid
         */
        $cookie = $this->Cookie->read('visitor');
        if (!is_array($cookie)) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, get_ip(), 2);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because no cookie',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Check if anonymous user
         */
        if ('anonymous' == $link->user->username) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 3);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because anonymous user',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Check for Adblock
         */
        if (!empty($this->request->cookie('adblockUser'))) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 4);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because Adblock',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Check if proxy
         */
        /*
          if (!isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && $this->_isProxy()) {
          // Update link hits
          $this->_updateLinkHits($link);
          $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 5);
          $content = [
          'status' => 'success',
          'message' => 'Go without Earn because proxy',
          'url' => $link->url
          ];
          return $content;
          }
         */

        /**
         * Check if IP changed
         */
        if ($cookie['ip'] != get_ip()) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 6);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because IP changed',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Check for unique vistits within last 24 hour
         */
        $startOfToday = Time::today()->format('Y-m-d H:i:s');
        $endOfToday = Time::now()->endOfDay()->format('Y-m-d H:i:s');

        $statistics = $this->Links->Statistics->find()
            ->where([
                'Statistics.ip' => $cookie['ip'],
                'Statistics.campaign_id' => $data['ci'],
                'Statistics.publisher_earn >' => 0,
                'Statistics.created BETWEEN :startOfToday AND :endOfToday'
            ])
            ->bind(':startOfToday', $startOfToday, 'datetime')
            ->bind(':endOfToday', $endOfToday, 'datetime')
            ->count();

        if ($statistics >= get_option('campaign_paid_views_day', 1)) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 7);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because Not unique.',
                'url' => $link->url
            ];
            return $content;
        }


        /**
         * Check Campaign Item weight
         */
        $CampaignItems = TableRegistry::get('CampaignItems');

        $campaign_item = $CampaignItems->find()
            ->contain(['Campaigns'])
            ->where(['CampaignItems.id' => $data['cii']])
            ->where(['CampaignItems.weight <' => 100])
            ->where(['Campaigns.status' => 1])
            ->first();


        if (!$campaign_item) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 8);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because Campaign Item weight is full.',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Check if default campaign
         */
        if ($campaign_item->campaign->default_campaign) {
            // Update link hits
            $this->_updateLinkHits($link);
            $this->_addNormalStatisticEntry($link, $ad_type, $data, $cookie['ip'], 9);
            $content = [
                'status' => 'success',
                'message' => 'Go without Earn because Default Campaign.',
                'url' => $link->url
            ];
            return $content;
        }

        /**
         * Add statistic record
         */
        
        $user_update = $this->Links->Users->get($link->user_id);
        $user_update->publisher_earnings += $campaign_item['publisher_price'] / 1000;

        $this->Links->Users->save($user_update);

        $referral_id = $referral_earn = 0;
        
        if (!empty($user_update->referred_by)) {
            $referral_percentage = get_option('referral_percentage', 20) / 100;
            $referral_value = ( $campaign_item['publisher_price'] / 1000 ) * $referral_percentage;
            
            $user_referred_by = $this->Links->Users->get($user_update->referred_by);
            $user_referred_by->referral_earnings += $referral_value;

            $this->Links->Users->save($user_referred_by);
            
            $referral_id = $user_update->referred_by;
            $referral_earn = $referral_value;
        }
        
        
        $country = $this->Links->Statistics->get_country($cookie['ip']);

        $statistic = $this->Links->Statistics->newEntity();

        $statistic->link_id = $link->id;
        $statistic->user_id = $link->user_id;
        $statistic->ad_type = $campaign_item['campaign']['ad_type'];
        $statistic->campaign_id = $campaign_item['campaign']['id'];
        $statistic->campaign_user_id = $campaign_item['campaign']['user_id'];
        $statistic->campaign_item_id = $campaign_item['id'];
        $statistic->ip = $cookie['ip'];
        $statistic->country = $country;
        $statistic->owner_earn = ($campaign_item['advertiser_price'] - $campaign_item['publisher_price']) / 1000;
        $statistic->publisher_earn = $campaign_item['publisher_price'] / 1000;
        $statistic->referral_id = $referral_id;
        $statistic->referral_earn = $referral_earn;
        $statistic->referer_domain = (parse_url($data['ref'], PHP_URL_HOST) ? parse_url($data['ref'], PHP_URL_HOST) : 'Direct');
        $statistic->referer = $data['ref'];
        $statistic->user_agent = env('HTTP_USER_AGENT');
        $statistic->reason = 1;
        $this->Links->Statistics->save($statistic);

        /**
         * Update campaign item views and weight
         */
        $campaign_item_update = $CampaignItems->newEntity();
        $campaign_item_update->id = $campaign_item['id'];
        $campaign_item_update->views = $campaign_item['views'] + 1;
        $campaign_item_update->weight = (($campaign_item['views'] + 1) / ($campaign_item['purchase'] * 1000)) * 100;
        $CampaignItems->save($campaign_item_update);

        // Update link hits
        $this->_updateLinkHits($link);
        $content = [
            'status' => 'success',
            'message' => 'Go With earning :)',
            'url' => $link->url
        ];
        return $content;
    }

    protected function _addNormalStatisticEntry($link, $ad_type, $data, $ip, $reason = 0)
    {
        if (!$ip) {
            $ip = get_ip();
        }
        $country = $this->Links->Statistics->get_country($ip);

        $statistic = $this->Links->Statistics->newEntity();

        $statistic->link_id = $link->id;
        $statistic->user_id = $link->user_id;
        $statistic->ad_type = $ad_type;
        $statistic->campaign_id = $data['ci'];
        $statistic->campaign_user_id = $data['cui'];
        $statistic->campaign_item_id = $data['cii'];
        $statistic->ip = $ip;
        $statistic->country = $country;
        $statistic->owner_earn = 0;
        $statistic->publisher_earn = 0;
        $statistic->referer_domain = (parse_url($data['ref'], PHP_URL_HOST) ? parse_url($data['ref'], PHP_URL_HOST) : 'Direct');
        $statistic->referer = $data['ref'];
        $statistic->user_agent = env('HTTP_USER_AGENT');
        $statistic->reason = $reason;
        $this->Links->Statistics->save($statistic);
    }

    protected function _setVisitorCookie()
    {
        $cookie = $this->Cookie->read('visitor');

        if (isset($cookie)) {
            return true;
        }

        $cookie_data = [
            'ip' => get_ip(),
            'date' => (new Time())->toDateTimeString()
        ];
        $this->Cookie->configKey('visitor', [
            'expires' => '+1 day',
            'httpOnly' => true
        ]);
        $this->Cookie->write('visitor', $cookie_data);

        return true;
    }

    protected function _updateLinkHits($link = null)
    {
        if (!$link) {
            return;
        }
        $link->hits += 1;
        $link->modified = $link->modified;
        $this->Links->save($link);
        return;
    }

    protected function _isProxy()
    {
        $proxy_headers = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_FORWARDED_FOR_IP',
            'VIA',
            'X_FORWARDED_FOR',
            'FORWARDED_FOR',
            'X_FORWARDED',
            'FORWARDED',
            'CLIENT_IP',
            'FORWARDED_FOR_IP',
            'HTTP_PROXY_CONNECTION'
        ];
        foreach ($proxy_headers as $proxy_header) {
            if (isset($_SERVER[$proxy_header])) {
                return true;
            }
        }
        return false;
    }
}
