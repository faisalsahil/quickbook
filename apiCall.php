<?php

require_once(__DIR__ . '/vendor/autoload.php');
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Payment;
use QuickBooksOnline\API\Facades\Account;

session_start();

function makeAPICall()
{

    // Create SDK instance
    $config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));

    /*
     * Retrieve the accessToken value from session variable
     */
    $accessToken = $_SESSION['sessionAccessToken'];

    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);
    $dataService->throwExceptionOnError(true);
    /*
     * Update the OAuth2Token of the dataService object
     */
    $dataService->updateOAuth2Token($accessToken);

    $dataService->setLogLocation("/Users/ksubramanian3/Desktop/HackathonLogs");

    /*
     * 1. Get CustomerRef and ItemRef
     */
    // $customerRef = getCustomerObj($dataService);
    // $itemRef = getItemObj($dataService);

    /*
     * 2. Create Invoice using the CustomerRef and ItemRef
     */
    $invoiceObj = Invoice::create([
        "Line" => [
            "Amount" => 100.00,
            "DetailType" => "SalesItemLineDetail",
            "SalesItemLineDetail" => [
                "Qty" => 2,
                "ItemRef" => [
                    "value" => 1
                ]
            ]
        ],
        "CustomerRef"=> [
            "value"=> 2
        ],
        "BillEmail" => [
            "Address" => "author@intuit.com"
        ]
    ]);
    $resultingInvoiceObj = $dataService->Add($invoiceObj);
    $invoiceId = $resultingInvoiceObj->Id;   // This needs to be passed in the Payment creation later
    echo "Created invoice Id={$invoiceId}. Reconstructed response body below:\n";
    die();
    //$companyInfo = $dataService->getCompanyInfo();
    $address = "QBO API call Successful!! Response Company name: " . $companyInfo->CompanyName . " Company Address: " . $companyInfo->CompanyAddr->Line1 . " " . $companyInfo->CompanyAddr->City . " " . $companyInfo->CompanyAddr->PostalCode;
    return $companyInfo;
}


function invoiceAndBilling()
{
    /*  This sample performs the folowing functions:
     1.   Add a customer
     2.   Add an item
     3    Create invoice using the information above
     4.   Email invoice to customer
     5.   Receive payments for the invoice created above
    */
    // Create SDK instance
    $config = include('config.php');
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));



    $accessToken = $_SESSION['sessionAccessToken'];
    $dataService->throwExceptionOnError(true);
    $dataService->updateOAuth2Token($accessToken);
    $dataService->setLogLocation("/Users/ksubramanian3/Desktop/HackathonLogs");


    //  Get Items List
    $dataService->updateOAuth2Token($accessToken);
    $Service_items = $dataService->Query("select * from Item where Type='Service'");
    // print_r($Service_items[0]);
    // print_r("#################################################################################################################");
    $services_array = [];
    $customer_id    = 0;

    foreach ($Service_items as $key => $item) {
        $id   = 0;
        $name = '';

        foreach($item as $key => $myObjectValues){
            if($key == 'Id'){$id = $myObjectValues;}
            if($key == 'Name'){$name =  $myObjectValues;}
        }

        if($id != 0 ){
            array_push($services_array, (object)[ 'id' => $id, 'name' => urlencode($name) ]);
        }
    }

    // print_r($services_array);
    $services_array = json_encode($services_array);



     // Get CustomerRef
    // KalsoomUmar(GP1153)
    // $customerRef = $dataService->Query("select * from Customer Where CompanyName='". $_POST['CustomerName']."'");
    // foreach ($customerRef[0] as $key => $myObjectValues) {
    //     if($key == 'Id'){
    //         $customer_id = $myObjectValues;
    //     }
    // }


    //Sending to GP Server to get Job data
    $ch         = curl_init();
    $parameters = "job_code=". $_POST['jobCode'] ."&beautician_id=".$_POST['beautician_id']."&services=". $services_array;
    // $url        = "http://192.168.1.28:3000/admin/api/v2/quickbooks.json";
    $url        = "http://demoapi.gharpar.co/admin/api/v2/quickbooks.json";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $data = curl_exec ($ch);
    curl_close ($ch);
    $data = json_decode($data, true);



    // Code for Invoice Creation
    $invoiceObj = Invoice::create($data);
    $resultingInvoiceObj = $dataService->Add($invoiceObj);
    $invoiceId = $resultingInvoiceObj->Id;   // This needs to be passed in the Payment creation later
    // echo "Created invoice Id={$invoiceId}. Reconstructed response body below:\n";
    $result = json_encode($resultingInvoiceObj, JSON_PRETTY_PRINT);

    print_r("Invoice#". $resultingInvoiceObj->DocNumber . " has been created successfully");

}

$result = invoiceAndBilling();

?>
