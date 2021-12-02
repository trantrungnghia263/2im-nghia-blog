<?php

namespace App\Controller\Member;

use App\Controller\Member\AppMemberController;
use Cake\Mailer\MailerAwareTrait;
use Cake\I18n\Time;

class UsersController extends AppMemberController
{

    use MailerAwareTrait;
    
    public function dashboard()
    {
        $total_views = $this->Users->Statistics->find()
            ->where([
                'Statistics.publisher_earn >' => 0,
                'Statistics.user_id' => $this->Auth->user('id')
            ])
            ->count();

        $this->set('total_views', $total_views);

        $total_earnings = $this->Users->Statistics->find()
            ->select(['total' => 'SUM(Statistics.publisher_earn)'])
            ->where([
                'Statistics.publisher_earn >' => 0,
                'Statistics.user_id' => $this->Auth->user('id')
            ])
            ->first();

        $this->set('total_earnings', $total_earnings->total);
        
        $referral_earnings = $this->Users->Statistics->find()
            ->select(['total' => 'SUM(Statistics.referral_earn)'])
            ->where([
                'Statistics.referral_earn >' => 0,
                'Statistics.referral_id' => $this->Auth->user('id')
            ])
            ->first();

        $this->set('referral_earnings', $referral_earnings->total);

///////////////////////////

        $last_record = $this->Users->Statistics->find()
            ->select('created')
            ->where(['user_id' => $this->Auth->user('id')])
            ->order(['created' => 'DESC'])
            ->first();

        if (!$last_record) {
            $last_record = Time::now();
        } else {
            $last_record = $last_record->created;
        }

        $first_record = $this->Users->Statistics->find()
            ->select('created')
            ->where(['user_id' => $this->Auth->user('id')])
            ->order(['created' => 'ASC'])
            ->first();

        if (!$first_record) {
            $first_record = Time::now()->modify('-1 week');
        } else {
            $first_record = $first_record->created;
        }

        $year_month = [];

        $last_month = Time::now()->setDate($last_record->year, $last_record->month, 01);
        $first_month = Time::now()->setDate($first_record->year, $first_record->month, 01);

        while ($first_month <= $last_month) {
            $year_month[$last_month->format('Y-m')] = $last_month->format('F Y');

            $last_month->modify('-1 month');
        }

        $this->set('year_month', $year_month);

        $to_month = Time::now()->format('Y-m');
        if ($this->request->is('post')) {
            $to_month = explode('-', $this->request->data['to_month']);
            $year = (int) $to_month[0];
            $month = (int) $to_month[1];
        } else {
            $time = new Time($to_month);
            $current_time = $time->startOfMonth();

            $year = (int) $current_time->format('Y');
            $month = (int) $current_time->format('m');
        }

        $date1 = Time::now()->year($year)->month($month)->startOfMonth()->format('Y-m-d H:i:s');
        $date2 = Time::now()->year($year)->month($month)->endOfMonth()->format('Y-m-d H:i:s');

        $views = $this->Users->Statistics->find()
            ->select([
                'day' => 'DATE_FORMAT(Statistics.created,"%d-%m-%Y")',
                'count' => 'COUNT(Statistics.created)',
                'publisher_earnings' => 'SUM(Statistics.publisher_earn)',
                'referral_earnings' => 'SUM(Statistics.referral_earn)',
            ])
            ->where([
                "Statistics.created BETWEEN :date1 AND :date2",
                'Statistics.publisher_earn >' => 0,
                'Statistics.user_id' => $this->Auth->user('id')
            ])
            ->order(['Statistics.created' => 'DESC'])
            ->bind(':date1', $date1, 'datetime')
            ->bind(':date2', $date2, 'datetime')
            ->group('day');

        $this->set('views', $views);

        $CurrentMonthDays = [];

        $targetTime = Time::now();
        $targetTime->year($year)
            ->month($month)
            ->day(1);

        for ($i = 1; $i <= $targetTime->format('t'); $i++) {
            $CurrentMonthDays[$i . "-" . $month . "-" . $year] = [
                'view' => 0,
                'publisher_earnings' => 0,
                'referral_earnings' => 0,
            ];
        }
        foreach ($views as $view) {
            $day = Time::now()->modify($view->day)->format('j-n-Y');
            $CurrentMonthDays[$day]['view'] = $view->count;
            $CurrentMonthDays[$day]['publisher_earnings'] = $view->publisher_earnings;
            $CurrentMonthDays[$day]['referral_earnings'] = $view->referral_earnings;
        }
        $this->set('CurrentMonthDays', $CurrentMonthDays);
        
        $popularLinks = $this->Users->Statistics->find()
            ->contain(['Links'])
            ->select([
                'Links.alias','Links.url','Links.title','Links.domain','Links.created',
                'views' => 'COUNT(Statistics.link_id)',
                'publisher_earnings' => 'SUM(Statistics.publisher_earn)'
            ])
            ->where([
                "Statistics.created BETWEEN :date1 AND :date2",
                'Statistics.publisher_earn >' => 0,
                'Statistics.user_id' => $this->Auth->user('id')
            ])
            ->order(['views' => 'DESC'])
            ->bind(':date1', $date1, 'datetime')
            ->bind(':date2', $date2, 'datetime')
            ->limit(10)
            ->group('Statistics.link_id');

        $this->set('popularLinks', $popularLinks);
        
        $this->loadModel('Announcements');
        
        $announcements = $this->Announcements->find()
            ->where(['Announcements.published' => 1])
            ->order(['Announcements.id DESC'])
            ->limit(3);
        $this->set('announcements', $announcements);
    }

    public function referrals()
    {
        $query = $this->Users->find()
            ->where(['referred_by' => $this->Auth->user('id')]);
        $referrals = $this->paginate($query);
        
        $this->set('referrals', $referrals);
    }

    public function profile()
    {
        $user = $this->Users->findById($this->Auth->user('id'))->first();

        if ($this->request->is(['post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            //debug($user->errors());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Profile has been updated'));
                $this->redirect(['action' => 'profile']);
            } else {
                $this->Flash->error(__('Oops! There are mistakes in the form. Please make the correction.'));
            }
        }
        unset($user->password);
        $this->set('user', $user);
    }

    public function changeEmail($username = null, $key = null)
    {
        if (!$username && !$key) {
            $user = $this->Users->findById($this->Auth->user('id'))->first();

            if ($this->request->is(['post', 'put'])) {
                $uuid = \Cake\Utility\Text::uuid();

                $user->activation_key = \Cake\Utility\Security::hash($uuid, 'sha1', true);

                $user = $this->Users->patchEntity($user, $this->request->data, ['validate' => 'changEemail']);

                if ($this->Users->save($user)) {
                    // Send rest email
                    $this->getMailer('User')->send('changeEmail', [$user]);

                    $this->Flash->success(__('Kindly check your email to confirm it.'));

                    $this->redirect(['action' => 'changeEmail']);
                } else {
                    $this->Flash->error(__('Oops! There are mistakes in the form. Please make the correction.'));
                }
            }
            $this->set('user', $user);
        } else {
            $user = $this->Users->find('all')
                ->where([
                    'status' => 1,
                    'username' => $username,
                    'activation_key' => $key
                ])
                ->first();

            if (!$user) {
                $this->Flash->error(__('Invalid Activation.'));
                return $this->redirect(['action' => 'changeEmail']);
            }

            $user->email = $user->temp_email;
            $user->temp_email = '';
            $user->activation_key = '';

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Your email has been confirmed.'));

                $this->Auth->logout();

                return $this->redirect(['action' => 'signin', 'prefix' => 'auth']);
            } else {
                $this->Flash->error(__('Unable to confirm your email.'));
                return $this->redirect(['action' => 'changeEmail']);
            }
        }
    }

    public function changePassword()
    {
        $user = $this->Users->findById($this->Auth->user('id'))->first();

        if ($this->request->is(['post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data, ['validate' => 'changePassword']);
            //debug($user->errors());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Password has been updated'));
                $this->redirect(['action' => 'changePassword']);
            } else {
                $this->Flash->error(__('Oops! There are mistakes in the form. Please make the correction.'));
            }
        }
        unset($user->password);
        $this->set('user', $user);
    }
}
