<?php

namespace App\Controller\Member;

use App\Controller\Member\AppMemberController;
use Cake\Event\Event;
use Cake\Routing\Router;
use Cake\Network\Exception\NotFoundException;

class CampaignsController extends AppMemberController
{

    public function initialize()
    {
        parent::initialize();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        if (in_array($this->request->action, ['ipn'])) {
            $this->eventManager()->off($this->Csrf);
            $this->eventManager()->off($this->Security);
        }
        $this->Auth->allow(['ipn']);
    }

    public function isAuthorized($user = null)
    {
        // The owner of an article can edit and delete it
        if (in_array($this->request->action, ['pay', 'pause', 'resume'])) {
            $id = (int) $this->request->params['pass'][0];
            return $this->Campaigns->isOwnedBy($id, $user['id']);
        }

        return parent::isAuthorized($user);
    }
    
    protected function checkEnableAdvertising()
    {
        if( get_option('enable_advertising', 'yes') == 'no' ) {
            $this->Flash->error(__('Creating campaigns is currently disabled.'));
            return $this->redirect( ['controller' => 'Users', 'action' => 'dashboard' ] );
        }
    }

    public function view($id = null)
    {
        $this->checkEnableAdvertising();
        
        if (!$id) {
            throw new NotFoundException(__('Invalid campaign'));
        }

        $campaign = $this->Campaigns->findById($id)
            ->contain(['CampaignItems'])
            ->where(['user_id' => $this->Auth->user('id')])
            ->first();
        if (!$campaign) {
            throw new NotFoundException(__('Campaign Not Found'));
        }

        $this->set('campaign', $campaign);
    }

    public function index()
    {
        $this->checkEnableAdvertising();
        
        $conditions = [];
        
        $filter_fields = ['id', 'status', 'ad_type', 'name', 'other_fields'];
        
        //Transform POST into GET
        if ($this->request->is(['post', 'put']) && isset($this->request->data['Filter'])) {
            
            $filter_url = [];
            
            $filter_url['controller'] = $this->request->params['controller'];
            
            $filter_url['action'] = $this->request->params['action'];
            
            // We need to overwrite the page every time we change the parameters
            $filter_url['page'] = 1;

            // for each filter we will add a GET parameter for the generated url
            foreach ($this->request->data['Filter'] as $name => $value) {
                if (in_array($name, $filter_fields) && $value) {
                    // You might want to sanitize the $value here
                    // or even do a urlencode to be sure
                    $filter_url[$name] = urlencode($value);
                }
            }
            // now that we have generated an url with GET parameters,
            // we'll redirect to that page
            return $this->redirect($filter_url);
        } else {
            // Inspect all the named parameters to apply the filters
            foreach ($this->request->query as $param_name => $value) {
                if (in_array($param_name, $filter_fields)) {

                    if (in_array($param_name, ['name'])) {
                        $conditions[] = [
                            ['Campaigns.' . $param_name . ' LIKE' => '%' . $value . '%']
                        ];
                    } elseif (in_array($param_name, ['other_fields'])) {
                        $conditions['OR'] = [
                            ['Campaigns.website_title LIKE' => '%' . $value . '%'],
                            ['Campaigns.website_url LIKE' => '%' . $value . '%'],
                            ['Campaigns.banner_name LIKE' => '%' . $value . '%'],
                            ['Campaigns.banner_size LIKE' => '%' . $value . '%']
                        ];
                    } elseif (in_array($param_name, ['id', 'status', 'ad_type']) ) {
                        if( $param_name == 'status' && !in_array($value, [1, 2, 3, 4, 5, 6, 7, 8]) ) {
                            continue;
                        }
                        if( $param_name == 'ad_type' && !in_array($value, [1, 2, 3]) ) {
                            continue;
                        }
                        $conditions['Campaigns.' . $param_name] = $value;
                    }
                    $this->request->data['Filter'][$param_name] = $value;
                }
            }
        }
        
        $query = $this->Campaigns->find()
            ->contain(['CampaignItems'])
            ->where(['user_id' => $this->Auth->user('id')])
            ->where($conditions);
        $campaigns = $this->paginate($query);

        $this->set('campaigns', $campaigns);
    }

    public function createInterstitial()
    {
        $this->checkEnableAdvertising();
        
        if( get_option('enable_interstitial', 'yes') == 'no' ) {
            $this->Flash->error(__('Creating interstitial campaigns is currently disabled.'));
            return $this->redirect( ['controller' => 'Users', 'action' => 'dashboard' ] );
        }
        
        $campaign = $this->Campaigns->newEntity(null, ['associated' => ['CampaignItems']]);
        $this->set('campaign', $campaign);
        
        if ($this->request->is('post')) {
            $campaign->user_id = $this->Auth->user('id');
            $campaign->ad_type = 1;
            $campaign->status = 6;

            $this->request->data['price'] = 0;

            foreach ($this->request->data['campaign_items'] as $key => $value) {
                if (empty($value['purchase'])) {
                    unset($this->request->data['campaign_items'][$key]);
                    continue;
                }
                $this->request->data['price'] += $value['purchase'] * $value['advertiser_price'];
            }
            
            if(count($this->request->data['campaign_items']) == 0){
                return $this->Flash->error(__('You must purchase at least from one country.'));
            }
            
            $campaign = $this->Campaigns->patchEntity($campaign, $this->request->data);

            if ($this->Campaigns->save($campaign)) {
                $this->Flash->success(__('Your campaign has been added. After payiny, we will review it and it will appear on the website.'));
                return $this->redirect(['action' => 'pay', $campaign->id]);
            } else {
                //debug($campaign->errors());
                $this->Flash->error(__('Unable to create your campaign.'));
            }
        }
        $this->set('campaign', $campaign);
    }
    
    public function createBanner()
    {
        $this->checkEnableAdvertising();
        
        if( get_option('enable_banner', 'yes') == 'no' ) {
            $this->Flash->error(__('Creating banner campaigns is currently disabled.'));
            return $this->redirect( ['controller' => 'Users', 'action' => 'dashboard' ] );
        }
        
        $campaign = $this->Campaigns->newEntity(null, ['associated' => ['CampaignItems']]);
        $this->set('campaign', $campaign);
        
        if ($this->request->is('post')) {
            $campaign->user_id = $this->Auth->user('id');
            $campaign->ad_type = 2;
            $campaign->status = 6;

            $this->request->data['price'] = 0;

            foreach ($this->request->data['campaign_items'] as $key => $value) {
                if (empty($value['purchase'])) {
                    unset($this->request->data['campaign_items'][$key]);
                    continue;
                }
                $this->request->data['price'] += $value['purchase'] * $value['advertiser_price'];
            }
            
            if(count($this->request->data['campaign_items']) == 0){
                return $this->Flash->error(__('You must purchase at least from one country.'));
            }

            $campaign = $this->Campaigns->patchEntity($campaign, $this->request->data);

            if ($this->Campaigns->save($campaign)) {
                $this->Flash->success(__('Your campaign has been added. After payiny, we will review it and it will appear on the website.'));
                return $this->redirect(['action' => 'pay', $campaign->id]);
            } else {
                $this->Flash->error(__('Unable to create your campaign.'));
            }
        }
        $this->set('campaign', $campaign);
    }

    public function createPopup()
    {
        $this->checkEnableAdvertising();
        
        if( get_option('enable_popup', 'yes') == 'no' ) {
            $this->Flash->error(__('Creating popup campaigns is currently disabled.'));
            return $this->redirect( ['controller' => 'Users', 'action' => 'dashboard' ] );
        }
        
        $campaign = $this->Campaigns->newEntity(null, ['associated' => ['CampaignItems']]);
        $this->set('campaign', $campaign);
        
        if ($this->request->is('post')) {
            $campaign->user_id = $this->Auth->user('id');
            $campaign->ad_type = 3;
            $campaign->status = 6;

            $this->request->data['price'] = 0;

            foreach ($this->request->data['campaign_items'] as $key => $value) {
                if (empty($value['purchase'])) {
                    unset($this->request->data['campaign_items'][$key]);
                    continue;
                }
                $this->request->data['price'] += $value['purchase'] * $value['advertiser_price'];
            }
            
            if(count($this->request->data['campaign_items']) == 0){
                return $this->Flash->error(__('You must purchase at least from one country.'));
            }
            
            $campaign = $this->Campaigns->patchEntity($campaign, $this->request->data);

            if ($this->Campaigns->save($campaign)) {
                $this->Flash->success(__('Your campaign has been added. After payiny, we will review it and it will appear on the website.'));
                return $this->redirect(['action' => 'pay', $campaign->id]);
            } else {
                //debug($campaign->errors());
                $this->Flash->error(__('Unable to create your campaign.'));
            }
        }
        $this->set('campaign', $campaign);
    }
    
    public function pay($id)
    {
        $this->checkEnableAdvertising();
        
        $campaign = $this->Campaigns->findById($id)
            ->contain(['CampaignItems'])
            ->where([
                'user_id' => $this->Auth->user('id'),
                'status' => 6
            ])
            ->first();

        if (!$campaign) {
            $this->Flash->success(__('Campaign not found'));
            return $this->redirect(['action' => 'view', $id]);
        }
        
        $this->set('campaign', $campaign);
    }
    
    public function checkout()
    {
        $this->autoRender = false;

        $this->response->type('json');
        
        if (!$this->request->is('ajax')) {
            $content = [
                'status' => 'error',
                'message' => __('Bad Request.'),
                'form' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        $campaign = $this->Campaigns->findById($this->request->data['id'])
            ->where(['user_id' => $this->Auth->user('id')])
            ->first();
        
        if (!$campaign) {
            $content = [
                'status' => 'error',
                'message' => __('Bad Request.'),
                'form' => ''
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        if ('wallet' == $this->request->data['payment_method']) {
            
            $user = $this->Campaigns->Users->get($this->Auth->user('id'));
            
            if( $campaign->price > $user->wallet_money ) {
                $content = [
                    'status' => 'error',
                    'message' => __("You don't have enough money in your wallet.")
                ];
                $this->response->body(json_encode($content));
                return $this->response;
            }
            
            $campaign->payment_method = 'wallet';
            $campaign->status = 5;
            $this->Campaigns->save($campaign);
            
            $user->wallet_money -= $campaign->price;
            $this->Campaigns->Users->save($user);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'offline',
                'url' => Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true)
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        if ('paypal' == $this->request->data['payment_method']) {
            
            $return_url = Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true);

            $paymentData = [
                'business' => get_option('paypal_email'),
                'cmd' => '_xclick',
                'currency_code' => get_option('currency_code'),
                'amount' => $campaign->price,
                'item_name' => __("Advertising Campaign"),
                'item_number' => '#'.$campaign->id,
                'page_style' => 'paypal',
                'return' => $return_url,
                'rm' => '0',
                'cancel_return' => $return_url,
                'custom' => $campaign->id,
                'no_shipping' => 1,
                'lc' => 'US'
            ];
            
            $url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

            if (get_option('paypal_sandbox', 'no') == 'no') {
                $url = 'https://www.paypal.com/cgi-bin/webscr';
            }

            $form = $this->redirect_post($url, $paymentData);
            
            $campaign->payment_method = 'paypal';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'form',
                'form' => $form
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        if ('payza' == $this->request->data['payment_method']) {
            
            $return_url = Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true);
            $alert_url = Router::url(['controller' => 'Campaigns', 'action' => 'ipn'], true);

            $paymentData = [
                'ap_merchant' => get_option('payza_email'),
                'apc_1' => $campaign->id,
                'ap_purchasetype' => 'service',
                'ap_amount' => $campaign->price,
                'ap_quantity' => 1,
                'ap_itemname' => __("Advertising Campaign"),
                'ap_itemcode' => '#'.$campaign->id,
                'ap_currency' => get_option('currency_code'),
                'ap_returnurl' => $return_url,
                'ap_cancelurl' => $return_url,
                'ap_alerturl' => $alert_url,
                'ap_ipnversion' => 2,
            ];
            
            $url = 'https://secure.payza.com/checkout';
            
            $form = $this->redirect_post($url, $paymentData);
            
            $campaign->payment_method = 'payza';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'form',
                'form' => $form
            ];
            $this->response->body(json_encode($content));
            return $this->response;
            
        }
        
        if ('skrill' == $this->request->data['payment_method']) {
            
            $return_url = Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true);
            $status_url = Router::url(['controller' => 'Campaigns', 'action' => 'ipn'], true);

            $paymentData = [
                'pay_to_email' => get_option('skrill_email'),
                'recipient_description' => get_option('site_name'),
                'status_url' => $status_url,
                'amount' => $campaign->price,
                'currency' => get_option('currency_code'),
                'detail1_description' => __("Advertising Campaign"),
                'detail1_text' => '#'.$campaign->id,
                'transaction_id' => $campaign->id,
                'return_url' => $return_url,
                'cancel_url' => $return_url
            ];
            
            $url = 'https://pay.skrill.com';
            
            $form = $this->redirect_post($url, $paymentData);
            
            $campaign->payment_method = 'skrill';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'form',
                'form' => $form
            ];
            $this->response->body(json_encode($content));
            return $this->response;
            
        }
        
        if ('coinbase' == $this->request->data['payment_method']) {
            
            $return_url = Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true);
            $alert_url = Router::url(['controller' => 'Campaigns', 'action' => 'ipn'], true);

            $paymentData = [
                'amount' => $campaign->price,
                'currency' => get_option('currency_code'),
                'name' => __("Advertising Campaign").' #'.$campaign->id,
                //'description' => '',
                'type' => 'order',
                'success_url' => $return_url,
                'cancel_url' => $return_url,
                'notifications_url' => $alert_url,
                'auto_redirect' => true,
                'metadata' => [
                    'campaign_id' => $campaign->id
                ]
            ];
            
            $sandbox = '';
            if (get_option('coinbase_sandbox', 'no') == 'yes') {
                $sandbox = 'sandbox.';
            }
            
            $url = "https://api.{$sandbox}coinbase.com/v2/checkouts";
            
            $timestamp = time();
            $method = 'POST';
            $path = '/v2/checkouts';
            $body = json_encode($paymentData);

            $sign = hash_hmac('sha256', $timestamp.$method.$path.$body, get_option('coinbase_api_secret'));

            $headers = [
                "CB-ACCESS-KEY: ".get_option('coinbase_api_key'),
                "CB-ACCESS-SIGN: {$sign}",
                "CB-ACCESS-TIMESTAMP: {$timestamp}",
                "CB-VERSION: 2016-09-12",
                "Content-Type: application/json",
            ];
            $reponse = json_decode(curlRequest($url, "POST", $body, $headers));
            
            if( isset( $reponse->errors ) ) {
                $message = '';
                foreach ($reponse->errors as $error) {
                    $message .= $error->id." - ".$error->message."\n";
                }
                
                $content = [
                    'status' => 'error',
                    'message' => $message,
                    'type' => 'url',
                    'url' => ''
                ];
                $this->response->body(json_encode($content));
                return $this->response;
            }
            
            $sandbox = 'www';
            if (get_option('coinbase_sandbox', 'no') == 'yes') {
                $sandbox = 'sandbox';
            }
            
            $redurect_url = "https://{$sandbox}.coinbase.com/checkouts/".$reponse->data->embed_code;
            
            $campaign->payment_method = 'coinbase';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'url',
                'url' => $redurect_url
            ];
            $this->response->body(json_encode($content));
            return $this->response;
            
        }
        
        if ('webmoney' == $this->request->data['payment_method']) {
            
            $return_url = Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true);
            $result_url = Router::url(['controller' => 'Campaigns', 'action' => 'ipn'], true);

            // https://wiki.wmtransfer.com/projects/webmoney/wiki/Web_Merchant_Interface
            $paymentData = [
                'LMI_PAYMENT_AMOUNT' => $campaign->price,
                'LMI_PAYMENT_DESC' => __("Advertising Campaign").' #'.$campaign->id,
                'LMI_PAYMENT_NO' => $campaign->id,
                'LMI_PAYEE_PURSE' => get_option('webmoney_merchant_purse'),
                'LMI_RESULT_URL' => $result_url,
                'LMI_SUCCESS_URL' => $return_url,
                'LMI_FAIL_URL' => $return_url
            ];
            
            $url = 'https://merchant.wmtransfer.com/lmi/payment.asp';
            
            $form = $this->redirect_post($url, $paymentData);
            
            $campaign->payment_method = 'webmoney';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'form',
                'form' => $form
            ];
            $this->response->body(json_encode($content));
            return $this->response;
            
        }
        
        if ('banktransfer' == $this->request->data['payment_method']) {
            
            $campaign->payment_method = 'banktransfer';
            $this->Campaigns->save($campaign);
            
            $content = [
                'status' => 'success',
                'message' => '',
                'type' => 'offline',
                'url' => Router::url(['controller' => 'Campaigns', 'action' => 'view', $campaign->id], true)
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        $content = [
            'status' => 'error',
            'message' => __("Invalide payment method.")
        ];
        $this->response->body(json_encode($content));
        return $this->response;

    }
    
    public function ipn()
    {
        $this->autoRender = false;
        
        if (!empty($this->request->data)) {
            
            // PayPal IPN
            if( isset($this->request->data['txn_id']) ) {
                $this->ipn_paypal($this->request->data);
                //return $this->response;
            }
            
            // Payza IPN
            if( isset($this->request->data['ap_merchant']) ) {
                $this->ipn_payza($this->request->data);
                //return $this->response;
            }
            
            // Skrill IPN
            if( isset($this->request->data['mb_amount']) ) {
                $this->ipn_skrill($this->request->data);
                //return $this->response;
            }
            
            // Coinbase IPN
            $raw_body = json_decode(file_get_contents('php://input'));
            if( isset( $raw_body->type ) ) {
                $this->ipn_coinbase($raw_body);
                //return $this->response;
            }
            
            // Payza IPN
            if( isset($this->request->data['LMI_PAYEE_PURSE']) ) {
                $this->ipn_webmoney($this->request->data);
                //return $this->response;
            }
            
        }

    }
    
    protected function ipn_webmoney($data)
    {
        if(isset($data['LMI_PAYMENT_NO'])) {
        
            $campaign_id = (int) $data['LMI_PAYMENT_NO'];
            $campaign = $this->Campaigns->get($campaign_id);
            
            if($campaign->price == $data['LMI_PAYMENT_AMOUNT']) {
                $campaign->status = 5;
                $this->Campaigns->save($campaign);
                $message = 'VERIFIED';
            } else {
                $campaign->status = 7;
                $this->Campaigns->save($campaign);
                $message = 'INVALID';
            }
        }
    }
    
    protected function ipn_coinbase($data)
    {
        $campaign_id = (int) $data->data->metadata->campaign_id;
        $campaign = $this->Campaigns->get($campaign_id);
        
        if( $data->type == 'wallet:orders:paid' ) {
            $campaign_price = (float) $campaign->price;
            $order_amount = (float) $data->data->amount->amount;

            if( $campaign_price != $order_amount ) {
                $campaign->status = 7;
                $this->Campaigns->save($campaign);
                $message = 'INVALID';
            } else {
                $campaign->status = 5;
                $this->Campaigns->save($campaign);
                $message = 'VERIFIED';
            }
        }
        
        if( $data->type == 'wallet:orders:mispaid' ) {
            $campaign->status = 7;
            $this->Campaigns->save($campaign);
            $message = 'INVALID';
        }
        
    }
    
    protected function ipn_payza($data)
    {
        $token = [
            'token' => urlencode($data['token'])
        ];
        
        // https://dev.payza.com/resources/references/ipn-variables

        $url = 'https://secure.payza.com/ipn2.ashx';

        $res = curlRequest($url, 'POST', $token );

        if(strlen($res) > 0) {
        
            $campaign_id = (int) $data['apc_1'];
            $campaign = $this->Campaigns->get($campaign_id);

            if (urldecode($res) != "INVALID TOKEN") {
                switch ($data['ap_transactionstate']) {
                    case 'Refunded':
                        $campaign->status = 8;
                        break;
                    case 'Completed':
                        $campaign->status = 5;
                        break;
                }

                $this->Campaigns->save($campaign);
                $message = 'VERIFIED';
            } else {
                $campaign->status = 7;
                $this->Campaigns->save($campaign);
                $message = 'INVALID';
            }

        }
    }
    
    protected function ipn_skrill($data)
    {
        $concatFields = $data['merchant_id']
            . $data['transaction_id']
            . strtoupper(md5(get_option('skrill_secret_word')))
            . $data['mb_amount']
            . $data['mb_currency']
            . $data['status'];

        $MBEmail = get_option('skrill_email');


        $campaign_id = (int) $data['transaction_id'];
        $campaign = $this->Campaigns->get($campaign_id);

        if ($campaign->price == $data['amount']) {
            if (strtoupper(md5($concatFields)) == $data['md5sig'] && $data['status'] == 2 && $data['pay_to_email'] == $MBEmail) {
                $campaign->status = 5;
                $this->Campaigns->save($campaign);
                $message = 'VERIFIED';
            }
        } else {
            $campaign->status = 7;
            $this->Campaigns->save($campaign);
            $message = 'INVALID';
        }
    }

    protected function ipn_paypal($data)
    {
        $data['cmd'] = '_notify-validate';

        // https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNTesting/?mark=IPN%20troubleshoot#invalid

        $paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';

        if (get_option('paypal_sandbox', 'no') == 'no') {
            $paypalURL = 'https://www.paypal.com/cgi-bin/webscr?';
        }

        $res = curlRequest($paypalURL, 'POST', $data );

        $campaign_id = (int) $data['custom'];
        $campaign = $this->Campaigns->get($campaign_id);

        if (strcmp($res, "VERIFIED") == 0) {
            switch ($data['payment_status']) {
                case 'Refunded':
                    $campaign->status = 8;
                    break;
                case 'Completed':
                    $campaign->status = 5;
                    break;
            }

            $this->Campaigns->save($campaign);
            $message = 'VERIFIED';
        } elseif (strcmp($res, "INVALID") == 0) {
            $campaign->status = 7;
            $this->Campaigns->save($campaign);
            $message = 'INVALID';
        }
    }
    
    protected function redirect_post($url, array $data)
    {
        ob_start();
        ?>
        <form id="checkout-redirect-form" method="post" action="<?= $url; ?>">
            <?php
            if ( !is_null($data) ) {
                foreach ($data as $k => $v) {
                    echo '<input type="hidden" name="' . $k . '" value="' . $v . '"> ';
                }
            }
            ?>
        </form>
        <?php
        $form = ob_get_contents();
        ob_end_clean();
        
        return $form;
    }

    public function pause($id)
    {
        $this->checkEnableAdvertising();
        
        $this->request->allowMethod(['post', 'put']);

        $campaign = $this->Campaigns->findById($id)
            ->where(['user_id' => $this->Auth->user('id')])
            ->where(['status' => 1])
            ->first();

        if (!$campaign) {
            $this->Flash->success(__('Campaign not found'));
            return $this->redirect(['action' => 'index']);
        }

        $campaign->status = 2;
        $this->Campaigns->save($campaign);

        return $this->redirect(['action' => 'index']);
    }

    public function resume($id)
    {
        $this->checkEnableAdvertising();
        
        $this->request->allowMethod(['post', 'put']);

        $campaign = $this->Campaigns->findById($id)
            ->where(['user_id' => $this->Auth->user('id')])
            ->where(['status' => 2])
            ->first();

        if (!$campaign) {
            $this->Flash->success(__('Campaign not found'));
            return $this->redirect(['action' => 'index']);
        }

        $campaign->status = 1;
        $this->Campaigns->save($campaign);

        return $this->redirect(['action' => 'index']);
    }
}
