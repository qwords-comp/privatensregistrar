<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use libphonenumber\PhoneNumberUtil;

/**
 * @param $params
 *
 * @return string
 * @throws Exception
 * @throws opApiException
 */
 
class Akasara{

    function message($code, $msg){
        return [
            "code"    => $code,
            "message" => $msg
        ];
    }

    function messageWithData($code, $msg, $data){
        return [
            "code"    => $code,
            "message" => $msg,
            "data"    => $data
        ];
    }
    
    function request($url, $method, $oauth2, $params){
        if($params){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer ".$oauth2,
                    "Content-Type: application/x-www-form-urlencoded",
                    "X-Requested-With: XMLHttpRequest",
                    "Accept: application/json",
                ),
            ));
        }else{
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/x-www-form-urlencoded",
                    "X-Requested-With: XMLHttpRequest",
                    "Accept: application/json",
                ),
            ));
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return json_decode($response);
        }
    }

    function authentication($url,$data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url."/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($data)
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response);
        return $result->access_token;
    }
    
    function create($params){
    
        try {
            
            $url = $params['configoption5'];
            $oauth2 = [
                "grant_type" => "client_credentials",
                "client_id" => $params['configoption3'],
                "client_secret" => $params['configoption4'],
                "scope" => "",
            ];

            $param_ssl = [
                "product_id" => $params['configoption2'],
                "hostname" => [$params['customfields']['Domain']],
                "csr" => $params['customfields']['CSR'],
                "contact" => "2023CCT0014298",
                "approval_email" => $params['customfields']['Approval Email'],
                "period" => 1,
                "domain_validation_method" => $params['Domain Validation Method']
            ];
            var_dump($param_ssl);die;
            $auth = $this->authentication($url,$oauth2);
            $request = $this->request($url."/api/rest/v3/ssl/create","POST",$auth,$param_ssl);
            if($request->code !== 200){
                return ["error" => $request->message];
            }else{
                return ["success" => $request->message];
            }
            
        } catch (Exception $e) {
            $fullMessage = $e->getFullMessage();
    
            logModuleCall(
                'akasarassl',
                'create',
                $params,
                $fullMessage,
                $fullMessage . ', ' . $e->getTraceAsString()
            );
    
            return $fullMessage;
        } catch (\Exception $e) {
            $message = "Error occurred during order saving: {$e->getMessage()}";
    
            logModuleCall(
                'akasarassl',
                'create',
                $params,
                $message,
                $message . ', ' . $e->getTraceAsString()
            );
    
            return $message;
        }
    
        return 'success';
    }
    
    
    function renew($params){
    
        try {
            
            $url = $params['configoption5'];
            $oauth2 = [
                "grant_type" => "client_credentials",
                "client_id" => $params['configoption3'],
                "client_secret" => $params['configoption4'],
                "scope" => "",
            ];
            
            $params_ssl = [
                "id" => 44
            ];
            
            $auth = $this->authentication($url,$oauth2);
            $request = $this->request($url."/api/rest/v3/ssl/renew","POST",$auth,$params_ssl);
            if($request->code !== 200){
                return ["error" => $request->message];
            }else{
                return ["success" => $request->message];
            }
            
        } catch (Exception $e) {
            $fullMessage = $e->getFullMessage();
    
            logModuleCall(
                'akasarassl',
                'renew',
                $params,
                $fullMessage,
                $fullMessage . ', ' . $e->getTraceAsString()
            );
    
            return $fullMessage;
        } catch (\Exception $e) {
            $message = "Error occurred during order saving: {$e->getMessage()}";
    
            logModuleCall(
                'akasarassl',
                'renew',
                $params,
                $message,
                $message . ', ' . $e->getTraceAsString()
            );
    
            return $message;
        }
    
        return 'success';
    }
    
    
    function cancel($params){
    
        try {
            
            $url = $params['configoption5'];
            $oauth2 = [
                "grant_type" => "client_credentials",
                "client_id" => $params['configoption3'],
                "client_secret" => $params['configoption4'],
                "scope" => "",
            ];
            
            $params_ssl = [
                "id" => 44,
            ];
            
            $auth = $this->authentication($url,$oauth2);
            $request = $this->request($url."/api/rest/v3/ssl/cancel","POST",$auth,$params_ssl);
            
            if($request->code !== 200){
                return ["error" => $request->message];
            }else{
                return ["success" => $request->message];
            }
            
        } catch (Exception $e) {
            $fullMessage = $e->getFullMessage();
    
            logModuleCall(
                'akasarassl',
                'cancel',
                $params,
                $fullMessage,
                $fullMessage . ', ' . $e->getTraceAsString()
            );
    
            return $fullMessage;
        } catch (\Exception $e) {
            $message = "Error occurred during order saving: {$e->getMessage()}";
    
            logModuleCall(
                'akasarassl',
                'cancel',
                $params,
                $message,
                $message . ', ' . $e->getTraceAsString()
            );
    
            return $message;
        }
    
        return 'success';
    }
    
    
    function reissue($params){
    
        try {
            
            $url = $params['configoption5'];
            $oauth2 = [
                "grant_type" => "client_credentials",
                "client_id" => $params['configoption3'],
                "client_secret" => $params['configoption4'],
                "scope" => "",
            ];
            
            $param_ssl = [
                "id" => 44,
                "hostname" => [$params['customfields']['Domain']],
                "csr" => $params['customfields']['CSR'],
                "contact" => "2023CCT0014298",
                "approval_email" => $params['customfields']['Approval Email']
            ];
            $auth = $this->authentication($url,$oauth2);
            $request = $this->request($url."/api/rest/v3/ssl/cancel","POST",$auth,$params_ssl);
            
            if($request->code !== 200){
                return ["error" => $request->message];
            }else{
                return ["success" => $request->message];
            }
            
        } catch (Exception $e) {
            $fullMessage = $e->getFullMessage();
    
            logModuleCall(
                'akasarassl',
                'reissue',
                $params,
                $fullMessage,
                $fullMessage . ', ' . $e->getTraceAsString()
            );
    
            return $fullMessage;
        } catch (\Exception $e) {
            $message = "Error occurred during order saving: {$e->getMessage()}";
    
            logModuleCall(
                'akasarassl',
                'reissue',
                $params,
                $message,
                $message . ', ' . $e->getTraceAsString()
            );
    
            return $message;
        }
    
        return 'success';
    }
    
}