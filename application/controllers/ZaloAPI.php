<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH.'/vendor/autoload.php';
use Zalo\Authentication\AccessToken;
use Zalo\Authentication\OAuth2Client;
use Zalo\Authentication\ZaloRedirectLoginHelper;
use Zalo\Authentication\AccessTokenMetadata;
use Zalo\Url\UrlDetectionInterface;
use Zalo\Url\ZaloUrlDetectionHandler;
use Zalo\Url\ZaloUrlManipulator;
use Zalo\HttpClients\HttpClientsFactory;
use Zalo\HttpClients\ZaloCurl;
use Zalo\HttpClients\ZaloCurlHttpClient;
use Zalo\HttpClients\ZaloHttpClientInterface;
use Zalo\HttpClients\ZaloStream;
use Zalo\HttpClients\ZaloStreamHttpClient;
use Zalo\Http\GraphRawResponse;
use Zalo\Http\RequestBodyInterface;
use Zalo\Http\RequestBodyUrlEncoded;
use Zalo\Exceptions\ZaloSDKException;
use Zalo\Exceptions\ZaloAuthenticationException;
use Zalo\Exceptions\ZaloAuthorizationException;
use Zalo\Exceptions\ZaloClientException;
use Zalo\Exceptions\ZaloOtherException;
use Zalo\Exceptions\ZaloResponseException;
use Zalo\Exceptions\ZaloResumableUploadException;
use Zalo\Exceptions\ZaloServerException;
use Zalo\FileUpload\ZaloFile;
use Zalo\ZaloApp;
use Zalo\Zalo;
use Zalo\ZaloClient;
use Zalo\ZaloRequest;
use Zalo\ZaloResponse;
use Zalo\Builder\MessageBuilder;
use Zalo\ZaloEndPoint;

class ZaloAPI extends ClientsController
{
   
    public function index()
    {
       echo 'Hi';
    }

    public function zalo_oa(){
        $config = array(
            'app_id' => '4030226841344043955',
            'app_secret' => '6UqqPjPeQ6cKFL44UQYS'
        );
        $zalo = new Zalo($config);
        $codeVerifier = ZaloAPI::genCodeVerifier();
      
        $state = "OA DDSG 2";
        if (get_option('zalo_codeVerifier') != null){
            // update_option('zalo_codeVerifier', $codeVerifier);
            $codeVerifier = get_option('zalo_codeVerifier');
        } else {
            add_option('zalo_codeVerifier', $codeVerifier);
        }
        $codeChallenge = ZaloAPI::genCodeChallenge($codeVerifier);
        // if (get_option('zalo_codeChallenge') != null){
        //     // update_option('zalo_codeChallenge', $codeChallenge);
        //     $codeChallenge = get_option('zalo_codeChallenge');
        // } else {
        //     add_option('zalo_codeChallenge', $codeChallenge);
        // }
      
        // echo $codeVerifier;
        // echo '<br />';
        // echo $codeChallenge;
        $helper = $zalo->getRedirectLoginHelper();
        
        $callbackUrl = "https://portal.chantroituonglai.com/ZaloAPI/zalo_oa_redirect_uri";
       
        $loginUrl = $helper->getLoginUrlByOA($callbackUrl, $codeChallenge, $state);
        // echo $loginUrl;
        redirect($loginUrl, 'refresh');
        return;
    }

    public function zalo_oa_redirect_uri(){
    //  https://portal.chantroituonglai.com/ZaloAPI/zalo_oa_redirect_uri?oa_id=4068327073172401353&code=eYsOVqevRsgcGhjYQ4G11OrdxH8tU10IpcAH5c10O2E-PO84O15i79GixMj0CmfxyY7xS38gGt-DCenCKHjgRenXk650TWGdhLBw12593W_2KuqzOaHA4RXbht9nELO7u7ICB64_VJdD9PWfJMzEGwqrrp8j7o1jgYQXENGn0c6vL8zXOrvxQV54-pL8Gorsq2AM1MuA47RvIOm49rHy5xj8cGSzAtX9_ock9tvJ717GUvj2GMK8HiGzkGiZE4q2paIu04PA7MF8EhWSWKLS9LyAxrBIwZPAOlJ7GfhfGW45nu0Z-DTNCJ6PymNFfc158UjBjCVXY8mAiwQspfrGj2QjrkAOmtV-DD6Gvu3T6-rBtv7yrgLag7lEjlYsYp-t9gkvn7me3Mvz&state=OA+DDSG&code_challenge=N9WCyWu3YGsnLHZRmGCQX9WeF-2MR8F_fCB43jEfQMM
        if ($this->input->get('code') && $this->input->get('code') != ''){
            $code = $this->input->get('code');
            $codeVerifier = get_option('zalo_codeVerifier');
          
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oauth.zaloapp.com/v4/oa/access_token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'code='.$code.'&app_id=4030226841344043955&grant_type=authorization_code&code_verifier=' . $codeVerifier,
            CURLOPT_HTTPHEADER => array(
                'secret_key: 6UqqPjPeQ6cKFL44UQYS',
                'Content-Type: application/x-www-form-urlencoded'
            ),
            ));

            $response = curl_exec($curl);
            $response_json = json_decode($response, true);
            if ($response_json && $response_json['access_token']){
                $accessToken = $response_json['access_token'];
                
                if (get_option('zalo_access_token') != null){
                    update_option('zalo_access_token', $response);
                } else {
                    add_option('zalo_access_token', $response, true);
                }

                if (get_option('zalo_access_token_date') != null){
                    update_option('zalo_access_token_date', date('Y-m-d H:i:s'));
                } else {
                    add_option('zalo_access_token_date', date('Y-m-d H:i:s'), true);
                }

                // $config = array(
                //     'app_id' => '4030226841344043955',
                //     'app_secret' => '6UqqPjPeQ6cKFL44UQYS'
                // );
                // $zalo = new Zalo($config);
                // $msgBuilder = new MessageBuilder('text');
                // $msgBuilder->withUserId('938253510686931614');
                // $msgBuilder->withText('CẤP QUYỀN GỬI TIN NHẮN ZALO TỪ API: ' . date('d/m/Y h:i:s a', time()) );
                
                // $msgText = $msgBuilder->build();
                // // send request
                // $response = $zalo->post(ZaloEndpoint::API_OA_SEND_MESSAGE, $accessToken, $msgText);
                // $result = $response->getDecodedBody(); // result
                // $this->zalo_single_send('938253510686931614', 'API APPROVED');
                echo '<p>ĐÃ CẤP QUYỀN TRUY CẬP</p>';
              
                return;
            } else {
                if ( $response_json['error'] == '-14019'){
                    // $this->zalo_single_send('938253510686931614', 'API APPROVED');
                    echo '<p>'.$response_json['error_name'].'</p>';
                    return;
                }
            }
           
            echo  $response ;
        
        }
      
        return;
    }

    function zalo_single_send($user_id, $message){
        if (get_option('zalo_access_token')){
            $zalo_access_token_arr = get_option('zalo_access_token');
            $zalo_access_token_date = get_option('zalo_access_token_date');
            $zalo_access_token_arr_val = json_decode(  $zalo_access_token_arr , true);
            // Kiểm tra xem có access_token trong mảng không
            if (isset($zalo_access_token_arr_val['access_token']) && !empty($zalo_access_token_arr_val['access_token'])) {
                // Kiểm tra xem ngày hiện tại có còn trong thời gian hợp lệ hay không
                $currentTime = time();
                // $expirationDate = strtotime($zaloAccessTokenDate) + $zalo_access_token_arr_val['expires_in'] * 60;
                $expirationDate = strtotime($zalo_access_token_date) + ($zalo_access_token_arr_val['expires_in'] * 60);
                
                if ($currentTime <= $expirationDate) {
                    echo "<p>Token hợp lệ</p>"; // Token hợp lệ   $curl = curl_init();
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://openapi.zalo.me/v2.0/oa/message',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS =>'{ 
                            "phone": "84784207831",
                            "template_id": "274478",
                            "template_data": {
                                "otp": "000000"
                            }
                          }',
                        CURLOPT_HTTPHEADER => array(
                            'access_token: '. $zalo_access_token_arr_val['access_token'],
                            'Content-Type: application/json',
                        ),
                    ));
                
                    $response = curl_exec($curl);
                    echo $response;
                } else {
                    echo "<p>Token đã hết hạn: $currentTime | $expirationDate </p>";
                 
                }
            } else {
                // echo "false"; // Không có access token
                redirect('https://portal.chantroituonglai.com/ZaloAPI/zalo_oa', 'refresh');
            }
        }

     
    }

    /**
     * generates code verifier
     *
     * @return string
     */
    public static function genCodeVerifier()
    {
        $random = bin2hex(openssl_random_pseudo_bytes(32));
        return self::base64url_encode(pack('H*', $random));
    }

    /**
     * generates code challenge
     *
     * @param $codeVerifier
     * @return string
     */
    public static function genCodeChallenge($codeVerifier)
    {
        if (!isset($codeVerifier)) {
            return '';
        }

        return self::base64url_encode(pack('H*', hash('sha256', $codeVerifier)));
    }

    private static function base64url_encode($plainText)
    {
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        return strtr($base64, '+/', '-_');
    }


    public function zalo_oa_token(){
       
        echo json_encode( array(
            'zalo_access_token' =>  get_option('zalo_access_token'), 
            'zalo_access_token_date' =>  get_option('zalo_access_token_date'), 
        ));
        return;
    }

    public function zalo_save_record_sms(){
        
        if ($this->input->post('order_number')){
        
            $order_number = $this->input->post('order_number');
            $target_id = $this->input->post('target_id');
            $uniquekey = 'ZALO_ZNS_' . $order_number;
            $this->load->model('external_model');
  
            $result = $this->external_model->add_record(array(
                "uniquekey" =>  $uniquekey,
                "rel" => 'ZNS',
                "root_id" => $order_number,
                "target_id" => false
            ));

            echo json_encode( array(
                'result' =>  $result, 
                '_debug' => array(
                    "uniquekey" =>  $uniquekey,
                    "rel" => 'ZNS',
                    "root_id" => $order_number,
                    "target_id" => $target_id
                )
            ));
            return;
        }
       
        echo json_encode( array(
            'result' =>  false, 
            '_debug' => $this->input->post()
        ));
        return;
        
    }

    public function zalo_get_record_sms(){
        $this->load->model('external_model');
  
        if ($this->input->post('uniquekey')){
            $uniquekey =  $this->input->post('uniquekey');
            $check_order = $this->external_model->get_record($uniquekey);
            echo json_encode( array(
                'check_order' =>  $check_order, 
            ));
            return;
        } 

        echo json_encode( array(
            'check_order' =>  false
        ));
        
        return;
   
    }

    public function zalo_send_order_notify(){
        if ($this->input->post("order_number") && $this->input->post("order_total") && $this->input->post("customer_name") && $this->input->post("customer_phone")){
            if (get_option('zalo_access_token')){
                $zalo_access_token_arr = get_option('zalo_access_token');
                $zalo_access_token_date = get_option('zalo_access_token_date');
                $zalo_access_token_arr_val = json_decode(  $zalo_access_token_arr , true);
                // Kiểm tra xem có access_token trong mảng không
                if (isset($zalo_access_token_arr_val['access_token']) && !empty($zalo_access_token_arr_val['access_token'])) {
                    // Kiểm tra xem ngày hiện tại có còn trong thời gian hợp lệ hay không
                    $currentTime = time();
                    // $expirationDate = strtotime($zaloAccessTokenDate) + $zalo_access_token_arr_val['expires_in'] * 60;
                    $expirationDate = strtotime($zalo_access_token_date) + ($zalo_access_token_arr_val['expires_in'] * 60);
                    
                    if ($currentTime <= $expirationDate) {
                        // echo "<p>Token hợp lệ</p>"; // Token hợp lệ   $curl = curl_init();
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://business.openapi.zalo.me/message/template',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{ 
                                "phone": "'.$this->input->post("customer_phone").'",
                                "template_id": "274143",
                                "template_data": {
                                    "customer_name": "'.$this->input->post("customer_name").'",
                                    "order_code": "'.$this->input->post("order_number").'",
                                    "payment_status": "ĐANG XỬ LÝ",
                                    "cost": "'.$this->input->post("order_total").'",
                                }
                              }',
                            CURLOPT_HTTPHEADER => array(
                                'access_token: '. $zalo_access_token_arr_val['access_token'],
                                'Content-Type: application/json',
                            ),
                        ));
                    
                        $response = curl_exec($curl);
                        // debug
                        if ($response && json_decode($response)){
                            $json_response = json_decode($response);
                            $this->load->model('external_model');
                            $uniquekey = 'ZALO_ZNS_' . $this->input->post('order_number');
                            if ($response["error"] == 0){
                             
                                $this->external_model->update_record($uniquekey, array(
                                    "target_id" => $this->input->post("customer_phone"),
                                ));
                            } else {
                             
                                $this->external_model->update_record($uniquekey, array(
                                    "target_id" => $response["message"] . '-' .$this->input->post("customer_phone"),
                                ));
                            }
                        }
                        echo $response;
                    } else {
                        echo "<p>Token đã hết hạn: $currentTime | $expirationDate </p>";
                     
                    }
                } else {
                    // echo "false"; // Không có access token
                    redirect('https://portal.chantroituonglai.com/ZaloAPI/zalo_oa', 'refresh');
                }
            }
        }
        

   
    }

    public function zalo_send_otp_notify(){
        if ($this->input->post("otp") && $this->input->post("customer_phone")){
            if (get_option('zalo_access_token')){
                $zalo_access_token_arr = get_option('zalo_access_token');
                $zalo_access_token_date = get_option('zalo_access_token_date');
                $zalo_access_token_arr_val = json_decode(  $zalo_access_token_arr , true);
                // Kiểm tra xem có access_token trong mảng không
                if (isset($zalo_access_token_arr_val['access_token']) && !empty($zalo_access_token_arr_val['access_token'])) {
                    // Kiểm tra xem ngày hiện tại có còn trong thời gian hợp lệ hay không
                    $currentTime = time();
                    // $expirationDate = strtotime($zaloAccessTokenDate) + $zalo_access_token_arr_val['expires_in'] * 60;
                    $expirationDate = strtotime($zalo_access_token_date) + ($zalo_access_token_arr_val['expires_in'] * 60);
                    
                    if ($currentTime <= $expirationDate) {
                        // echo "<p>Token hợp lệ</p>"; // Token hợp lệ   $curl = curl_init();
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://business.openapi.zalo.me/message/template',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS =>'{ 
                                "phone": "'.$this->input->post("customer_phone").'",
                                "template_id": "274478",
                                "template_data": {
                                    "otp": "'.$this->input->post("otp").'",
                                }
                              }',
                            CURLOPT_HTTPHEADER => array(
                                'access_token: '. $zalo_access_token_arr_val['access_token'],
                                'Content-Type: application/json',
                            ),
                        ));
                    
                        $response = curl_exec($curl);
                        // debug
                        if ($response && json_decode($response)){
                            $json_response = json_decode($response);
                            $this->load->model('external_model');
                            $uniquekey = 'ZALO_ZNS_' . $this->input->post('otp');
                            if ($response["error"] == 0){
                             
                                $this->external_model->update_record($uniquekey, array(
                                    "target_id" => $this->input->post("customer_phone"),
                                ));
                            } else {
                             
                                $this->external_model->update_record($uniquekey, array(
                                    "target_id" => $response["message"] . '-' .$this->input->post("customer_phone"),
                                ));
                            }
                        }
                        echo $response;
                    } else {
                        echo "<p>Token đã hết hạn: $currentTime | $expirationDate </p>";
                     
                    }
                } else {
                    // echo "false"; // Không có access token
                    redirect('https://portal.chantroituonglai.com/ZaloAPI/zalo_oa', 'refresh');
                }
            }
        }
        

   
    }
}