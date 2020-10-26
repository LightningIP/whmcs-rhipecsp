<?php

use WHMCS\Database\Capsule as DB;
use WHMCS\Module\Server\lip_rhipe_csp\RhipeMetricsProvider;
use WHMCS\Module\Server\lip_rhipe_csp\API;
use WHMCS\Service\Status;

function lip_rhipe_csp_MetaData()
{
    return array(
        // The display name of the unique identifier to be displayed on the table output
        'ListAccountsUniqueIdentifierDisplayName' => 'Subscription ID',
        // The field in the return that matches the unique identifier
        'ListAccountsUniqueIdentifierField' => 'username',
        // The config option indexed field from the _ConfigOptions function that identifies the product on the remote system
        'ListAccountsProductField' => 'configoption1',
    );
}

/**
 * Product Configuration Options
 */
function lip_rhipe_csp_ConfigOptions() {
    return [
        'ProductID' => [
            'FriendlyName' => 'Product ID', 
            'Type' => 'text', 
            'Size' => '60'
        ],
    ];
}


function lip_rhipe_csp_MetricProvider($params) {
    return new RhipeMetricsProvider($params);
}


function lip_rhipe_csp_ListAccounts( array $params ) {
    try {
        
        $api = new API($params['serverusername'],$params['serverpassword']);
        $tenants = $api->getTenantsAndSubscriptions('bc7e9cfb-7aeb-e711-817c-e0071b65e251');



        $subscriptions = [];
        foreach ($tenants as $tenant) {
            
            foreach ($tenant->Subscriptions as $subscription) {

                $status = Status::SUSPENDED;

                if ($subscription->Status === 'Active') {
                    $status = Status::ACTIVE;
                }

                $subscriptions[] = [
                    'username'          => $subscription->SubscriptionId, 
                    'domain'            => $tenant->TenantDomain,
                    'uniqueIdentifier'  => $subscription->SubscriptionId,
                    'product'           => $subscription->ProductId,
                    'primaryip'         => '',
                    'created'           => explode('.',str_replace('T', ' ', $subscription->FirstPurchased))[0],
                    'status'            => $status,
                ];
            }
        }
        
        return [
            'success'  => true, // Boolean value
            'accounts' => $subscriptions,
        ];

    } catch (Exception $e) {
        return [
            'success'  => false, // Boolean value
            'error' => $e->getMessage(),
        ];
    }

}