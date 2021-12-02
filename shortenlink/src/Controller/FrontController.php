<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\I18n\I18n;

class FrontController extends AppController
{

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->setLanguage();
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        $this->viewBuilder()->theme(get_option('theme', 'ClassicTheme'));
    }

    protected function setLanguage()
    {
        if (empty(get_option('site_languages'))) {
            return true;
        }
        if (isset($this->request->query['lang']) &&
            in_array($this->request->query['lang'], get_site_languages(true))) {
            setcookie('lang', $this->request->query['lang'], time() + (86400 * 30 * 12), '/');
            return $this->redirect('http://' . env('SERVER_NAME') . $this->request->here);
        }

        if (!isset($_COOKIE['lang']) && isset($this->request->acceptLanguage()[0])) {

            $lang = substr($this->request->acceptLanguage()[0], 0, 2);

            $langs = get_site_languages(true);

            $valid_langs = [];
            foreach ($langs as $key => $value) {
                if (preg_match('/^' . $lang . '/', $value)) {
                    $valid_langs[] = $value;
                }
            }

            if (isset($valid_langs[0])) {
                setcookie('lang', $valid_langs[0], time() + (86400 * 30 * 12));
                return $this->redirect('http://' . env('SERVER_NAME') . $this->request->here);
            }
        }

        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], get_site_languages(true))) {
            I18n::locale($_COOKIE['lang']);
        }
    }
}
