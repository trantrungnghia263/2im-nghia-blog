<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Exception;

class RecaptchaComponent extends Component
{

    public $error = null;

    public function verify($response = null)
    {
        $recaptchaSecretKey = get_option('reCAPTCHA_secret_key');
        if (empty($recaptchaSecretKey)) {
            throw new Exception(__("You must set your Recaptcha secret key!"));
        }

        $data = array(
            'secret' => $recaptchaSecretKey,
            'response' => $response,
        );

        $result = curlRequest('https://www.google.com/recaptcha/api/siteverify', 'POST', $data );
        $responseData = json_decode($result, true);

        if ($responseData['success'] == false) {
            //$recaptchaError = '';
            //foreach ($responseData['error-codes'] as $code) {
            //    $recaptchaError .= $code . ' ';
            //}

            //$this->error = $recaptchaError;
            $this->error = true;
        }

        return $responseData['success'];
    }
}
