<?php

namespace App\Controller\Member;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\I18n\I18n;

class AppMemberController extends AppController
{

    public $paginate = [
        'limit' => 10,
        'order' => ['id' => 'DESC']
    ];
    
    public function isAuthorized($user)
    {
        // Admin can access every action
        if (isset($user['role'])) {
            return true;
        }

        // Default deny
        return false;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        if (in_array($this->Auth->user('role'), ['member', 'admin'])) {
            // Allow all actions
            $this->Auth->allow();
        }
        $this->viewBuilder()->layout('member');

        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], get_site_languages(true))) {
            I18n::locale($_COOKIE['lang']);
        }
    }
}
