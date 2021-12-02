<?php

namespace App\Controller\Admin;

use App\Controller\Admin\AppAdminController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Cache\Cache;

class ActivationController extends AppAdminController
{
    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->viewBuilder()->layout('admin');
        
    }
    
    public function index()
    {
        if ($this->request->is('post')) {
            
            $result = $this->Activation->licenseCurlRequest($this->request->data);
            
            if( isset($result['item']['id']) && $result['item']['id'] == 16887109 ) {
                Cache::write('license_response_result', $result, '1week');
                
                $Options = TableRegistry::get('Options');
                
                $personal_token = $Options->find()->where(['name' => 'personal_token'])->first();
                $personal_token->value = trim($this->request->data['personal_token']);
                $Options->save($personal_token);
                
                $purchase_code = $Options->find()->where(['name' => 'purchase_code'])->first();
                $purchase_code->value = trim($this->request->data['purchase_code']);
                $Options->save($purchase_code);
                
                $this->Flash->success(__('Your license has been verified.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
            } else {
                if( isset($result['description']) && !empty($result['description']) ) {
                    $this->Flash->error( $result['description'] );
                } elseif( isset($result['error_description']) && !empty($result['error_description']) ) {
                    $this->Flash->error( $result['error_description'] );
                } else {
                    $this->Flash->error( $result['error'] );
                }
                
            }
            
        }
    }
    
}
