<?php

namespace App\Controller\Auth;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Mailer\MailerAwareTrait;
use Cake\I18n\I18n;

class UsersController extends AppController
{

    use MailerAwareTrait;

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Cookie');
        $this->loadComponent('Recaptcha');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['signup', 'logout', 'activateAccount', 'forgotPassword']);
        $this->viewBuilder()->layout('auth');

        if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], get_site_languages(true))) {
            I18n::locale($_COOKIE['lang']);
        }
    }

    public function signin()
    {
        if ($this->Auth->user('id')) {
            return $this->redirect('/');
        }

        $user = $this->Users->newEntity();
        $this->set('user', $user);

        if ($this->request->is('post') || $this->request->query('provider')) {
            $user = $this->Auth->identify();
            if ($user) {
                $this->Auth->setUser($user);
                if ('admin' == $user['role']) {
                    return $this->redirect([
                            'plugin' => false,
                            'controller' => 'Users',
                            'action' => 'dashboard',
                            'prefix' => 'admin'
                    ]);
                }
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error(__('Invalid username or password, try again'));
        }
    }

    public function signup()
    {
        if ($this->Auth->user('id')) {
            return $this->redirect('/');
        }
        
        if ((bool) get_option('close_registration', false)) {
            return $this->redirect('/');
        }
        
        $user = $this->Users->newEntity();

        $this->set('user', $user);

        if ($this->request->is('post')) {
            if ((get_option('enable_captcha_signup') == 'yes') && isset_recaptcha() && !$this->Recaptcha->verify($this->request->data['g-recaptcha-response'])) {
                return $this->Flash->error(__('The CAPTCHA was incorrect. Try again'));
            }

            $user = $this->Users->patchEntity($user, $this->request->data);

            $referred_by_id = 0;
            if (null != $this->Cookie->read('ref')) {
                $user_referred_by = $this->Users->find()
                    ->where([
                        'username' => $this->Cookie->read('ref'),
                        'status' => 1
                    ])
                    ->first();

                if ($user_referred_by) {
                    $referred_by_id = $user_referred_by->id;
                }
            }
            $user->referred_by = $referred_by_id;

            $user->api_token = \Cake\Utility\Security::hash(\Cake\Utility\Text::uuid(), 'sha1', true);
            $user->activation_key = \Cake\Utility\Security::hash(\Cake\Utility\Text::uuid(), 'sha1', true);

            $user->role = 'member';
            $user->status = 1;

            if (get_option('account_activate_email', 'yes') == 'yes') {
                $user->status = 2;
            }

            if ($this->Users->save($user)) {

                if (get_option('account_activate_email', 'yes') == 'yes') {
                    // Send activation email
                    $this->getMailer('User')->send('activation', [$user]);

                    $this->Flash->success(__('Your account has been created. Please check your email inbox to activate your account.'));
                    return $this->redirect(['action' => 'signin']);
                }
                $this->Flash->success(__('Your account has been created.'));
                return $this->redirect(['action' => 'signin']);
            }
            $this->Flash->error(__('Unable to add the user.'));
        }
        $this->set('user', $user);
    }

    public function logout()
    {
        return $this->redirect($this->Auth->logout());
    }

    public function activateAccount($username = null, $key = null)
    {
        if (!$username && !$key) {
            $this->Flash->error(__('Invalid Activation.'));
            return $this->redirect(['action' => 'signin']);
        }
        $user = $this->Users->find()
            ->where([
                'status' => 2,
                'username' => $username,
                'activation_key' => $key
            ])
            ->first();

        if (!$user) {
            $this->Flash->error(__('Invalid Activation.'));
            return $this->redirect(['action' => 'signin']);
        }

        $user->status = 1;
        $user->activation_key = '';


        if ($this->Users->save($user)) {
            $this->Flash->success(__('Your account has been activated.'));
            $this->Auth->setUser($user->toArray());
            return $this->redirect(['controller' => 'users', 'action' => 'dashboard', 'prefix' => 'member']);
        } else {
            $this->Flash->error(__('Unable to activate your account.'));
            return $this->redirect(['action' => 'signin', 'prefix' => 'auth']);
        }
    }

    public function forgotPassword($username = null, $key = null)
    {
        if ($this->Auth->user('id')) {
            return $this->redirect('/');
        }

        if (!$username && !$key) {
            $user = $this->Users->newEntity();
            $this->set('user', $user);

            if ($this->request->is(['post', 'put'])) {
                if ((get_option('enable_captcha_forgot_password') == 'yes') && isset_recaptcha() && !$this->Recaptcha->verify($this->request->data['g-recaptcha-response'])) {
                    return $this->Flash->error(__('The CAPTCHA was incorrect. Try again'));
                }

                $user = $this->Users->findByEmail($this->request->data['email'])->first();

                if (!$user) {
                    $this->Flash->error(__('Invalid User.'));
                    return $this->redirect(['action' => 'forgotPassword', 'prefix' => 'auth']);
                }

                $user->activation_key = \Cake\Utility\Security::hash(\Cake\Utility\Text::uuid(), 'sha1', true);

                $user = $this->Users->patchEntity($user, $this->request->data, ['validate' => 'forgotPassword']);

                if ($this->Users->save($user)) {
                    // Send rest email
                    $this->getMailer('User')->send('forgotPassword', [$user]);

                    $this->Flash->success(__('Kindly check your email for reset password link.'));

                    return $this->redirect(['action' => 'forgotPassword', 'prefix' => 'auth']);
                } else {
                    $this->Flash->error(__('Unable to reset password.'));

                    return $this->redirect(['action' => 'forgotPassword', 'prefix' => 'auth']);
                }
            }
        } else {
            $user = $this->Users->find('all')
                ->where([
                    'status' => 1,
                    'username' => $username,
                    'activation_key' => $key
                ])
                ->first();
            if (!$user) {
                $this->Flash->error(__('Invalid Request.'));
                return $this->redirect(['action' => 'forgotPassword', 'prefix' => 'auth']);
            }

            if ($this->request->is(['post', 'put'])) {
                $user->activation_key = '';

                $user = $this->Users->patchEntity($user, $this->request->data, ['validate' => 'forgotPassword']);

                if ($this->Users->save($user)) {
                    $this->Flash->success(__('Your password has been changed.'));
                    return $this->redirect(['action' => 'signin', 'prefix' => 'auth']);
                } else {
                    $this->Flash->error(__('Unable to change your password.'));
                }
            }

            unset($user->password);

            $this->set('user', $user);
        }
    }
}
