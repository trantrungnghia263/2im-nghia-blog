<?php

namespace App\Controller;

use App\Controller\FrontController;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Exception\NotFoundException;

class StatisticsController extends FrontController
{

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->layout('go_banner');
        $this->Auth->allow(['view']);
    }

    public function view($alias = null)
    {
        
        if (null !== $this->Auth->user('id')) {
            if($this->Auth->user('role') == 'member' && get_option('link_info_member', 'yes') == 'no') {
                throw new NotFoundException(__('Invalid link'));
            }
        } else {
            if(get_option('link_info_public', 'yes') == 'no') {
                throw new NotFoundException(__('Invalid link'));
            }
        }
        
        if (!$alias) {
            throw new NotFoundException(__('Invalid link'));
        }
        
        $link = $this->Statistics->Links->find()->where(['alias' => $alias, 'status <>' => 3])->first();
        if (!$link) {
            throw new NotFoundException(__('404 Not Found'));
        }
        $this->set('link', $link);
        
        $user = $this->Statistics->Links->Users->find()->where(['id' => $link->user_id, 'status' => 1])->first();
        if (!$user) {
            throw new NotFoundException(__('404 Not Found'));
        }
        
        $now = Time::now()->format('Y-m-d H:i:s');
        $last30 = Time::now()->modify('-30 day')->format('Y-m-d H:i:s');
        
        $stats = $this->Statistics->find()
            ->select([
                'statDate' => 'DATE_FORMAT(created,"%d-%m-%Y")',
                'statDateCount' => 'COUNT(DATE_FORMAT(created,"%d-%m-%Y"))'
            ])
            ->where([
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'created BETWEEN :last30 AND :now'
            ])
            ->bind(':last30', $last30, 'datetime')
            ->bind(':now', $now, 'datetime')
            ->order(['created' => 'DESC'])
            ->group('statDate');

        $this->set('stats', $stats);
        
        
        $countries = $this->Statistics->find()
            ->select([
                'country',
                'clicks' => 'COUNT(country)'
            ])
            ->where([
                'link_id' => $link->id,
                'user_id' => $link->user_id
            ])
            ->order(['clicks' => 'DESC'])
            ->group('country');

        $this->set('countries', $countries);
        
        
        $referrers = $this->Statistics->find()
            ->select([
                'referer_domain',
                'clicks' => 'COUNT(referer)'
            ])
            ->where([
                'link_id' => $link->id,
                'user_id' => $link->user_id
            ])
            ->order(['clicks' => 'DESC'])
            ->group('referer_domain');

        $this->set('referrers', $referrers);
        
        
        
    }
    
}
