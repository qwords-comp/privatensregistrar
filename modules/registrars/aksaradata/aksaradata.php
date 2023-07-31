<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
use WHMCS\Carbon;

class Aksara{
    public $url = "http://api6.irsfa.id";

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

    function request($url, $method, $oauth2, $datas){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => http_build_query($datas),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ".$oauth2,
                "Content-Type: application/x-www-form-urlencoded",
                "X-Requested-With: XMLHttpRequest",
                "Accept: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return $err;
        } else {
            return json_decode($response);
        }
    }

    function authentication($data){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url."/oauth/token",
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
        return $result;
    }
}

function aksaradata_MetaData(){
    return array(
        'DisplayName' => 'Aksaradata',
        'APIVersion' => '2.0',
    );
}

function aksaradata_getConfigArray(){
    $configarray = array(
        "clientid" => array (
            "FriendlyName" => "Client Id",
            "Type"         => "text", # Text Box
            "Size"         => "255", # Defines the Field Width
            "Description"  => "Client Id API Aksara registrar",
            "Default"      => "",
            "Placeholder"  => "Client Id"
        ),
        "secretid" => array (
            "FriendlyName" => "Secret Id",
            "Type"         => "text", # Text Box
            "Size"         => "255", # Defines the Field Width
            "Description"  => "Secret Id API Aksara registrar",
            "Default"      => "",
            "Placeholder"  => "Secret Id"
        )
    );
    return $configarray;
}

function aksaradata_GetNameservers($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    try {
        $main = new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/info","POST",$auth->access_token, "");
    
        if($request->code == 200){
            $ns = explode(',',$request->data->nameserver);
            $i = 1;
            foreach($ns as $nameserver){
                $values["ns".$i]=$nameserver;
                $i++;
            }
            return $values;
        }
        
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_SaveNameservers($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $nameserver = [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']];
    $filtered = array_filter($nameserver);

    $datas = [
        "domain"     => $params['sld'].".".$params['tld'], 
        "nameserver" => $filtered,
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/modify/ns","PUT",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_RegisterDomain($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $data = [
        "domain"      => $params['sld'].".".$params['tld'],
        "period"      => $params['regperiod'],
        "nameserver"  => [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']],
        "description" => "WHMCS Register Domain [New Module]",
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    $registrant = array(
        'company_name'     => $params['companyname'],
        'initial'          => substr($params['firstname'],0,1).substr($params['lastname'],0,1),
        'first_name'       => $params['firstname'],
        'last_name'        => $params['lastname'],
        'gender'           => 'M',
        'street'           => $params['address1'],
        'street2'          => $params['address2'],
        'number'           => 13,
        'city'             => $params['city'],
        'state'            => $params['state'],
        'zip_code'         => $params['postcode'],
        'country'          => $params['country'],
        'email'            => $params['email'],
        'telephone_number' => str_replace('.','',$params['fullphonenumber']),
        'locale'           => 'en_GB'
      );

    if(($data['nameserver'][0] == "")){
        unset($data['nameserver']);
    }else{
        foreach($data['nameserver'] as $key => $value){
            if (empty($value)) {
                unset($data['nameserver'][$key]);
             }
        }
    }

    $datas = array_merge($data,$registrant);
    
    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/register","POST",$auth->access_token,$datas);

        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_TransferDomain($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $data = [
        "domain"      => $params['sld'].".".$params['tld'],
        "auth_code"   => $params['eppcode'],
        "period"      => $params['regperiod'],
        "nameserver"  => [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']],
        "description" => "[WHMCS] Transfer Domain [New Module]",
        "epp"         => $params['eppcode'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

   $registrant = array(
        'company_name'     => $params['companyname'],
        'initial'          => substr($params['firstname'],0,1).substr($params['lastname'],0,1),
        'first_name'       => $params['firstname'],
        'last_name'        => $params['lastname'],
        'gender'           => 'M',
        'street'           => $params['address1'],
        'street2'          => $params['address2'],
        'number'           => 13,
        'city'             => $params['city'],
        'state'            => $params['state'],
        'zip_code'         => $params['postcode'],
        'country'          => $params['country'],
        'email'            => $params['email'],
        'telephone_number' => str_replace('.','',$params['fullphonenumber']),
        'locale'           => 'en_GB'
      );

    if(($data['nameserver'][0] == "")){
        unset($data['nameserver']);
    }else{
        foreach($data['nameserver'] as $key => $value){
            if (empty($value)) {
                unset($data['nameserver'][$key]);
             }
        }
    }

    $datas = array_merge($data,$registrant);

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/transfer","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_RenewDomain($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $datas = [
        "domain"         => $params['sld'].".".$params['tld'],
        "period"         => $params['regperiod'],
        "description"    => "[WHMCS] Renew Domain [New Module]",
        "domain_name"    => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/renew","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        } 
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_GetEPPCode($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $datas = [
        "domain"      => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/eppcode","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return array('eppcode' => $request->data);
        }       
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
    
}

function aksaradata_GetRegistrarLock($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $datas = [
        "domain" => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/info","POST",$auth->access_token,$datas);

        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            $status = $request->data->thief_protection == 1 ? 'locked':'unlocked';
            return $status;
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_SaveRegistrarLock($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $datas = [
        "domain"         => $params['sld'].".".$params['tld'],
        // "status"         => ($params['lockenabled'] == 'locked') ? 'lock':'unlock'
        "domain_name"    => $params['sld'],
        "domain_extension" => $params['tld'],
        "status"         => ($params['lockenabled'] == 'locked') ? 1 : 0 
    ];
    
    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/lock","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return ['success' => true, 'result' => $request];
        } 
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_RegisterNameserver($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];

    $datas = [
        "name" => $params['nameserver'],
        "ip"   => $params['ipaddress'],
        "ip6"  => $params['ip6'],

        "host" => $params['nameserver'],
        "ipv4" => $params['ipaddress'],
        "ipv6" => $params['ip6'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/nameserver/create","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_ModifyNameserver($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];
    
    $datas_detail = [
        "domain_name" => $params['domainname'],
        "host" => $params['nameserver'],
    ];
    
    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        
        $req_cns = $main->request($main->url."/api/rest/v3/domain/nameserver/get","POST",$auth->access_token,$datas_detail);
        
        if ($req_cns->message == "Data Not Found!") {
            return ["error" => $req_cns->message];
        }
        
        $nameserver_id = null;
        foreach($req_cns->data as $struct) {
            if ($struct->nameserver == $params['nameserver']) {
                $nameserver_id = $struct->nameserver_id;
                break;
            }
        }
        
        $datas = [
            "nameserver_id" => $nameserver_id,
            "ip"            => $params['newipaddress'],

            "host" => $params['nameserver'],
            "ipv4" => $params['newipaddress'],
            "ipv6" => $params['ip6'],
        ];

        $request = $main->request($main->url."/api/rest/v3/domain/nameserver/update","PUT",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_DeleteNameserver($params) {
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];
    
    $datas_detail = [
        "domain_name" => $params['domainname'],
        "host" => $params['nameserver'],
    ];
    
    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        
        
        $req_cns = $main->request($main->url."/api/rest/v3/domain/nameserver/get","POST",$auth->access_token,$datas_detail);
        
        if ($req_cns->message == "Data Not Found!") {
            return ["error" => $req_cns->message];
        }
        
        $nameserver_id = null;
        foreach($req_cns->data as $struct) {
            if ($struct->nameserver == $params['nameserver']) {
                $nameserver_id = $struct->nameserver_id;
                break;
            }
        }
        
        $datas = [
            "nameserver_id" => $nameserver_id,
            "host" => $params['nameserver'],
        ];

        $request = $main->request($main->url."/api/rest/v3/domain/nameserver/delete","DELETE",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return $main->message($request->code,$request->message);
        }     
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_GetContactDetails($params){
    $oauth2 = [
        "grant_type" => "client_credentials",
        "client_id" => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope" => "",
    ];
    
    $datas = [
        "domain"         => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/contact/getbydomain","POST",$auth->access_token,$datas);
        
        if($request->code !== 200){
            return ["error" => $request->message];
        }else{
            return array(
                'Registrant' => array(
                    'First Name' => $request->data->reg_contact->contact_first_name,
                    'Last Name' => $request->data->reg_contact->contact_last_name,
                    'Company Name' => $request->data->reg_contact->contact_company_name,
                    'Email Address' => $request->data->reg_contact->contact_email,
                    'Address 1' => $request->data->reg_contact->contact_street,
                    'Address 2' => "",
                    'City' => $request->data->reg_contact->contact_city,
                    'State' => $request->data->reg_contact->contact_state,
                    'Postcode' => $request->data->reg_contact->contact_zip_code,
                    'Country' => $request->data->reg_contact->contact_country,
                    'Phone Number' => $request->data->reg_contact->contact_phone,
                    'Fax Number' => "",
                ),
            );
        }     
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_SaveContactDetails($params){
    $oauth2 = [
        "grant_type"    => "client_credentials",
        "client_id"     => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope"         => "",
    ];
    
    $datas = [
        "domain" => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];

    try {
        $main=new Aksara;
        $auth = $main->authentication($oauth2);
        $request = $main->request($main->url."/api/rest/v3/domain/contact/getbydomain","POST",$auth->access_token,$datas);
        
        $contactDetails = $params['contactdetails']['Registrant'];
        $contact_datas = [
            'contact_id'       => $request->data->admin_contact->client_contact_id,
            'telephone_number' => str_replace('.', '', $contactDetails['Phone Number']),
            'street'           => $contactDetails['Address 1'],
            'number'           => 13,
            'zip_code'         => $contactDetails['Postcode'],
            'city'             => $contactDetails['City'],
            'state'            => $contactDetails['State'],
            'country'          => $contactDetails['Country'],
            'email'            => $contactDetails['Email Address'],
            'company_name'     => $contactDetails['Company Name'],
            'first_name'       => $contactDetails['First Name'],
            'last_name'        => $contactDetails['Last Name'],
            'locale'           => $contactDetails['Country']
        ];        
        
        $reply = $main->request($main->url."/api/rest/v3/domain/contact/update","PUT",$auth->access_token,$contact_datas);

        if($reply->code !== 200){
            return ["error" => $reply->message];
        }else{
            return [
                'success' => true,
            ];
        }
    } catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_GetDNS($params){
	$oauth2 = [
        "grant_type"    => "client_credentials",
        "client_id"     => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope"         => "",
    ];
	
	$datas = [
        "domain" => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];
	
	try {
	
		$main=new Aksara;
		$auth = $main->authentication($oauth2);
		$reply = $main->request($main->url."/api/rest/v3/domain/dns/list","POST",$auth->access_token,$datas);

		if($reply->code !== 200){
			return ["error" => $reply->message];
		}else{
			$hostRecords = array();
			$address='';
			
			if(empty($reply->data->data->zone[0]->record )){
				$dt_create = [
                    "domain" =>  $params['sld'].".".$params['tld'],
                    "domain_name" => $params['sld'],
                    "domain_extension" => $params['tld'],
                    "ip" 	=> '103.102.152.5',
				];

				$new = $main->request($main->url."/api/rest/v3/domain/dns/create","POST",$auth->access_token,$dt_create);
				$reply = $main->request($main->url."/api/rest/v3/domain/dns/list","POST",$auth->access_token,$datas);
			}

			$typeNS=['A','AAAA','MXE','MX','CNAME','TXT','URL','FRAME'];
			foreach($reply->data->data->zone[0]->record  as $r){
				if(in_array($r->type,$typeNS)){
					if($r->name){
						if($r->exchange){
							$address=$r->exchange;
						}
						
						if($r->address){
							$address=$r->address;
						}
						if($r->nsdname){
							$address=$r->nsdname;
						}
						if($r->raw){
							$address=$r->raw;
						}
						if($r->cname){
							$address=$r->cname;
						}
						if($r->preference){
							$address=$r->preference;
						}
						if($r->txtdata){
							$address=$r->txtdata;
						}
						
						
						$hostRecords[] = array(
                            "hostname" => $r->name, // eg. www
                            "type" => $r->type, // eg. A
                            "address" => $address, // eg. 10.0.0.1
                            "priority" => $r->ttl, // eg. 10 (N/A for non-MX records)
                            "recid"	=> $r->Line
						);
					}
				}
			}
			return $hostRecords;
		}
	} catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_SaveDNS($params){
	$oauth2 = [
        "grant_type"    => "client_credentials",
        "client_id"     => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope"         => "",
    ];
    
	$dataGetDomain = [
        "domain" => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];
	
	try {
		$main=new Aksara;
		$auth = $main->authentication($oauth2);
		
		foreach($params['dnsrecords'] as $r){
			if($r['recid'] !='' ){
				
				if($r['hostname'] =='' || $r['address'] ==''){
					//delete
					$paramDelete = [
                        'domain'	=> $params['sld'].".".$params['tld'],
                        "domain_name" => $params['sld'],
                        "domain_extension" => $params['tld'],
                        'line'		=> $r['recid']
					];
					$deleteDNS = $main->request($main->url."/api/rest/v3/domain/dns/delete","POST",$auth->access_token,$paramDelete);
					if($deleteDNS->code != 200){
						return ['error' => $r['recid'].' del -> '.$deleteDNS->message];
					}
				}else{
					//update
					$paramUpdate = [
                        'domain'	=> $params['sld'].".".$params['tld'],
                        "domain_name" => $params['sld'],
                        "domain_extension" => $params['tld'],
                        'line'		=> $r['recid'],
                        'name'		=> $r['hostname'],
                        'class'		=> 'IN',
                        'ttl'		=> ($r['priority'] =='N/A')?86400:$r['priority'],
                        'type'		=> $r['type'],
                        'values'	=> [
							'address' => $r['address']
						]
					];
					$UpdateDNS = $main->request($main->url."/api/rest/v3/domain/dns/update","POST",$auth->access_token,$paramUpdate);
					
					if($UpdateDNS->code != 200){
						return ['error' => $UpdateDNS->message];
					}
				}
			}else{
				if($r['hostname'] !=''){
					if($r['type'] == 'MX' ){
						$value = [
                            'preference' 	=> $r['address'],
                            'exchange'		=> $r['hostname']
						];
					}elseif($r['type'] == 'CNAME'){
						$value = [
                            'flatten_to' 	=> $r['address'],
                            'cname'			=> $r['hostname'],
                            'flatten'		=> 1
						];					
					}elseif($r['type'] == 'TXT'){
						$value = [
							'txtdata' => $r['address']
						];
					}else{
						$value=[
							'address' => $r['address']
						];
					}

					$paramAdd=[
                        /* 'isroot'	=> true, */
                        'domain'	=> $params['sld'].".".$params['tld'],
                        "domain_name" => $params['sld'],
                        "domain_extension" => $params['tld'],
                        'name'		=> $r['hostname'],
                        'class'		=> 'IN',
                        'ttl'		=> ($r['priority'] !='')?$r['priority']:86400,
                        'type'		=> $r['type'],
                        'values'	=> $value
					];

					$AddDNS = $main->request($main->url."/api/rest/v3/domain/dns/add","POST",$auth->access_token,$paramAdd);
					if($AddDNS->code != 200){
						return ['error' => $AddDNS->message];
					}
				
				}
			}
		}
		return array(
            'success' => 'success',
        );
	}catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}

function aksaradata_Sync($params){
	$oauth2 = [
        "grant_type"    => "client_credentials",
        "client_id"     => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope"         => "",
    ];

	$dataGetDomain = [
        "domain" => $params['sld'].".".$params['tld'],
        "domain_name" => $params['sld'],
        "domain_extension" => $params['tld'],
    ];
	
	try {
		$main=new Aksara;
		$auth = $main->authentication($oauth2);
		$DomainStatus = $main->request($main->url."/api/rest/v3/domain/info","POST",$auth->access_token,$dataGetDomain);
		if($DomainStatus->code == 200){
			$created = new Carbon($DomainStatus->data->expirationday);
			$now = Carbon::now();
			$now->diff($created)->days;
			return [
                'expirydate' 		=>  Carbon::parse($DomainStatus->data->expirationday)->format('Y-m-d'),
                'active'	 		=> ($now->diff($created)->days == 0)?false:true,
                'expired'	 		=> ($now->diff($created)->days == 0)?true:false,
                'transferredAway'	=> ($DomainStatus->data->transferday)?true:false
			];
		}else{
			return array(
				'error' => $DomainStatus->message,
			);
		}
    }catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
}



function aksaradata_TransferSync($params){
	$oauth2 = [
        "grant_type"    => "client_credentials",
        "client_id"     => $params['clientid'],
        "client_secret" => $params['secretid'],
        "scope"         => "",
    ];
	
	try {
		$main=new Aksara;
		$auth = $main->authentication($oauth2);
		$domain = [
			"domain"      => $params['sld'].".".$params['tld'],
            "domain_name" => $params['sld'],
            "domain_extension" => $params['tld'],
		];

		$eppData = $main->request($main->url."/api/rest/v3/domain/eppcode","POST",$auth->access_token,$domain);
		if($eppData->code == 200){
            $dataGetDomain = [
                "domain" => $params['sld'].".".$params['tld'],
                "domain_name" => $params['sld'],
                "domain_extension" => $params['tld'],
                "epp" => $request->data
            ];
				
            $transfer = $main->request($main->url."/api/rest/v3/domain/stransfer","POST",$auth->access_token,$dataGetDomain);
            if($transfer->code == 200){
                if($transfer->data->code == 1000 ){
                    return array(
                        'completed' => true,
                        'expirydate' =>   Carbon::parse($transfer->data->data->exDate)->format('Y-m-d'),
                    ); 
                }elseif($transfer->data->code == 2301 ){
                    return array();
                }else{
                    return array(
                        'failed' => true,
                        'reason' => $transfer->data->messages,
                    );
                }	
            }else{
                return array(
                    'error' => $transfer->message,
                );
            }
		}else{
			return array(
				'error' => $eppData->message,
			);
		}
	}catch (\Exception $e) {
        return array(
            'error' => $e->getMessage(),
        );
    }
	
}
