<?php

namespace App\Controller\Member;

use App\Controller\Member\AppMemberController;
use Cake\Event\Event;

class ToolsController extends AppMemberController
{

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
    }

    public function quick()
    {
        $this->loadModel('Users');

        $user = $this->Users->findById($this->Auth->user('id'))->first();
        $this->set('user', $user);
    }

    public function massShrinker()
    {
        $this->loadModel('Users');

        $user = $this->Users->findById($this->Auth->user('id'))->first();
        $this->set('user', $user);

        $link = $this->Users->Links->newEntity();
        if ($this->request->is('post')) {
            $urls = explode("\n", str_replace("\r", "\n", $this->request->data['urls']));
            $urls = array_unique(array_filter($urls));
            $urls = array_slice($urls, 0, get_option('mass_shrinker_limit', 20));
            
            $ad_type = get_option('member_default_advert', 1);
            if (isset($this->request->data['ad_type'])) {
                if( array_key_exists($this->request->data['ad_type'], get_allowed_ads()) ) {
                    $ad_type = $this->request->data['ad_type'];
                }
            }

            $results = [];
            foreach ($urls as $url) {
                $results[] = $this->addMassShrinker($url, $ad_type);
            }

            $this->set('results', $results);
        }
        $this->set('link', $link);
    }

    public function api()
    {
        $this->loadModel('Users');

        $user = $this->Users->findById($this->Auth->user('id'))->first();
        $this->set('user', $user);
    }
    
    public function full()
    {
        $this->loadModel('Users');

        $user = $this->Users->findById($this->Auth->user('id'))->first();
        $this->set('user', $user);
    }

    protected function addMassShrinker($url, $ad_type = 1)
    {
        $this->loadModel('Links');

        $result = ['url' => '', 'short' => ''];

        $url = parse_url($url, PHP_URL_SCHEME) === null ? 'http://' . $url : $url;

        $link = $this->Links->find()->where(['url' => $url, 'user_id' => $this->Auth->user('id')])->first();

        if ($link) {
            return ['url' => $url, 'short' => $link->alias];
        }

        $link = $this->Links->newEntity();
        $data = [];

        $data['user_id'] = $this->Auth->user('id');
        $data['url'] = $url;
        $data['alias'] = $this->Links->geturl();
        $data['ad_type'] = $ad_type;
        $link->status = 1;
        $link->hits = 0;
        
        $linkMeta = [
            'title'       => '',
            'description' => '',
            'image' => ''
        ];
        
        if( get_option('disable_meta_api') === 'no' ) {
            $linkMeta = $this->Links->getLinkMeta($url);
        }
        
        $link->title = $linkMeta[ 'title' ];
        $link->description = $linkMeta[ 'description' ];
        $link->image = $linkMeta[ 'image' ];

        $link = $this->Links->patchEntity($link, $data);
        if ($this->Links->save($link)) {
            return ['url' => $url, 'short' => $link->alias, 'domain' => $link->domain];
        }
        return ['url' => $url, 'short' => 'error','domain' => ''];
    }
}
