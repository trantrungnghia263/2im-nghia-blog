<?php

namespace App\Controller;

use App\Controller\AppController;
use App\Form\ContactForm;
use Cake\Event\Event;

class FormsController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Recaptcha');
    }
    
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['contact']);
    }

    public function contact()
    {
        $this->autoRender = false;

        $this->response->type('json');

        $contact = new ContactForm();

        if (!$this->request->is('ajax')) {
            $content = [
                'status' => 'error',
                'message' => __('Bad Request.'),
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
        
        if ((get_option('enable_captcha_contact') == 'yes') && isset_recaptcha() && !$this->Recaptcha->verify($this->request->data['g-recaptcha-response'])) {
            $content = [
                'status' => 'error',
                'message' => __('The CAPTCHA was incorrect. Try again'),
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }

        if ($contact->execute($this->request->data)) {
            $content = [
                'status' => 'success',
                'message' => __('Your message has been sent!'),
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        } else {
            $content = [
                'status' => 'error',
                //'message' => serialize($contact->errors()),
                'message' => __('Can\'t send the message. Please try again latter.'),
            ];
            $this->response->body(json_encode($content));
            return $this->response;
        }
    }
}
