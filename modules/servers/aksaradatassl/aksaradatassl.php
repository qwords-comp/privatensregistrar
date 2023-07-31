<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

// require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';


/**
 * @return array
 */
function aksaradatassl_MetaData()
{
    return [
        'DisplayName' => 'Aksaradata SSL',
        'APIVersion' => '1.0', // Use API Version 1.0
        'RequiresServer' => false,
    ];
}

/**
 * @return array
 */
function aksaradatassl_ConfigOptions($params)
{
    $aksara = new Akasara;
    $request = $aksara->request("https://api6.irsfa.id/api/pricing?type=ssl","GET", '',null);
    $res = $request->data;
    
    $products = [];
    foreach ($res as $product) {
        $products[$product->product_id] = $product->product_name;
    }

    return [
        'Grant Type' => [
            'Type' => 'text',
            'Size' => '30',
            'Dafault' => 'client_credentials',
        ],
        'SSL Product' => [
            'Type' => 'dropdown',
            'Options' => $products,
        ],
        'Client ID' => [
            'Type' => 'text',
            'Size' => '30',
            'Dafault' => '',
        ],
        'Secret Key' => [
            'Type' => 'text',
            'Size' => '30',
            'Dafault' => '',
        ],
        'Aksaradata API URL' => [
            'Type' => 'text',
            'Size' => '60',
            'Default' => ''
        ],
        'SSL Panel URL' => [
            'Type' => 'text',
            'Size' => '60',
            'Default' => ''
        ],
        'Default language' => [
            'Type' => 'dropdown',
            'Options' => ['en_GB', 'ru_RU', 'es_ES', 'nl_NL'],
        ],
    ];
}

/**
 * @param array $params
 *
 * @return string
 */
function aksaradatassl_CreateAccount($params)
{
    try {
        logModuleCall(
            'aksaradatassl',
            'aksaradatassl_CreateAccount',
            $params,
            '',
            'Attempt to create new order'
        );
        
        $aksara = new Akasara;
        return $aksara->create($params);
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * @param $params
 *
 * @return string
 */
function aksaradatassl_Renew($params)
{
    $aksara = new Akasara;
    return $aksara->renew($params);
}

/**
 * @param $params
 *
 * @return string
 */
function aksaradatassl_Cancel($params)
{
    $aksara = new Akasara;
    return $aksara->cancel($params);
}

/**
 * @param array $params
 *
 * @return array|string
 */
function aksaradatassl_ClientArea($params)
{
    $fullMessage = null;
    $order = null;
    $updatedData = [];
    
    try {
        
        //{{url}}/api/rest/v3/ssl/product
        // $ssl = new Akasara;
        // $curl = curl_init();
        // curl_setopt_array($curl, array(
        //   CURLOPT_URL => 'https://api2.sandbox.irsfa.id/oauth/token',
        //   CURLOPT_RETURNTRANSFER => true,
        //   CURLOPT_ENCODING => '',
        //   CURLOPT_MAXREDIRS => 10,
        //   CURLOPT_TIMEOUT => 0,
        //   CURLOPT_FOLLOWLOCATION => true,
        //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //   CURLOPT_CUSTOMREQUEST => 'POST',
        //   CURLOPT_POSTFIELDS => array(
        //       'grant_type' => 'client_credentials','client_id' => 'ecc1d21e-f873-4a86-ac37-982adc0fc239','client_secret' => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh','scope' => '*'),
        //   CURLOPT_HTTPHEADER => array(
        //     'Accept: application/json'
        //   ),
        // ));
        // $response = curl_exec($curl);
        // curl_close($curl);
        // var_dump($response);die;
            
        
        var_dump('okeeee');die;
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $params['serviceid'])->first();
        $params['id'] = $order->order_id;
        $updatedData = updateOpOrdersTable($params);
        
        
        
        
        
    } catch (opApiException $e) {
        $fullMessage = $e->getFullMessage();
        logModuleCall(
            'aksaradatassl',
            'aksaradatassl_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    } catch (\Exception $e) {
        $fullMessage = $e->getMessage();
        logModuleCall(
            'aksaradatassl',
            'aksaradatassl_ClientArea',
            $params,
            $fullMessage,
            $e->getTraceAsString()
        );
    }

    $statusMap = [
        'PAI' => 'Paid',
        'REQ' => 'Requested',
        'REJ' => 'Rejected',
        'FAI' => 'Failed',
        'EXP' => 'Expired',
        'ACT' => 'Active',
    ];

    return [
        'templatefile' => 'templates/clientarea.tpl',
        'templateVariables' => [
            'linkValue' => 'serviceId=' . $params['serviceid'],
            'linkName' => 'SSL Panel',
            'errorMessage' => $fullMessage,
            'status' => ArrayHelper::getValue($statusMap, $updatedData['status']),
            'creationDate' => $updatedData['creationDate'],
            'activationDate' => $updatedData['activationDate'],
            'expirationDate' => $updatedData['expirationDate'],
        ],
    ];
}

/**
 * @return array
 */
function aksaradatassl_AdminCustomButtonArray()
{
    return [
        'Cancel' => 'Cancel',
        'Reissue' => 'Reissue',
    ];
}

function aksaradatassl_AdminServicesTabFields($params)
{
    if (isset($_GET['viewDetails'])) {
        $service = Capsule::table('tblhosting')->where('id', $params['serviceid'])->first();
        $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();
        $order = Capsule::table('openprovidersslnew_orders')->where('service_id', $service->id)->first();

        $html = '';

        if ($order->order_id) {
            $configuration = ConfigHelper::getServerConfigurationFromParams($product,
                EnvHelper::getServerEnvironmentFromParams($product));

            $apiCredentials = [
                'username' => ArrayHelper::getValue($configuration, 'username'),
                'password' => ArrayHelper::getValue($configuration, 'password'),
                'apiUrl' => ArrayHelper::getValue($configuration, 'opApiUrl'),
                'id' => $order->order_id,
            ];

            $reply = opApiWrapper::retrieveOrder($apiCredentials);

            $link1 = ArrayHelper::getValue($configuration,
                    'opRcpUrl') . '/ssl/order-details.php?ssl_order_id=' . $reply['id'];
            $link2 = ArrayHelper::getValue($configuration,
                    'sslPanelUrl') . '/#/orders/' . $reply['sslinhvaOrderId'] . '/details';

            $html = '<br /><a href=\'' . $link1 . '\' target=\'_blank\'>' . $link1 . '</a><br />';
            $html .= '<a href=\'' . $link2 . '\' target=\'_blank\'>' . $link2 . '</a><br /><br />';


            $html .= '<table style=\'border: solid 1px;\'>';

            $csrFieldMap = [
                'countryName' => 'Country',
                'stateOrProvinceName' => 'State',
                'localityName' => 'Locality',
                'organizationName' => 'Organization',
                'organizationalUnitName' => 'Organization Unit',
                'commonName' => 'Common Name',
                'emailAddress' => 'Email',
            ];

            foreach ($reply as $key => $value) {
                $html .= '<tr style=\'border: solid 1px;\'><td style=\'border: solid 1px;\'>' . $key . '</td><td>';

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $html .= $k ? $k . ':' . $v . '<br />' : nl2br($v) . '<br />';
                    }
                } else {
                    $html .= nl2br($value);

                    if ($key === 'csr' && $value) {
                        $csrData = opApiWrapper::processRequest(
                            'decodeCsrSslCertRequest',
                            opApiWrapper::buildParams($apiCredentials),
                            ['csr' => $value]
                        );

                        $html .= '<br /><strong>Decoded CSR:</strong><br /><table>';

                        foreach ($csrData as $k => $v) {
                            $html .= '<tr><td><b>' . $csrFieldMap[$k] . '</b>:</td><td>' . $v . '</td></tr>';
                        }

                        $html .= '</table>';
                    }
                }

                $html .= '</td></tr>' . PHP_EOL;
            }

            $html .= '</table>';
        }

        $fieldsarray = ['Certificate Info' => $html];
    } else {
        $fieldsarray = ['Certificate Info' => '<input type="button" value="View Info" onclick="window.location=\'?userid=' . $params['clientdetails']['userid'] . '&id=' . $params['serviceid'] . '&viewDetails\'" />'];
    }


    return $fieldsarray;
}




