<?php

use WHMCS\Database\Capsule as DB;
use WHMCS\Module\Server\rhipe_csp\RhipeMetricsProvider;
use WHMCS\Module\Server\rhipe_csp\API;
use WHMCS\Service\Status;

function rhipe_csp_MetaData()
{
    return array(
        'ListAccountsUniqueIdentifierDisplayName' => 'Subscription ID',
        'ListAccountsUniqueIdentifierField' => 'username',
        'ListAccountsProductField' => 'configoption1',
    );
}

/**
 * Product Configuration Options
 */
function rhipe_csp_ConfigOptions() {
    return [
        'ProductID' => [
            'FriendlyName' => 'Product ID', 
            'Type' => 'text', 
            'Size' => '60'
        ],
    ];
}


function rhipe_csp_MetricProvider($params) {
    return new RhipeMetricsProvider($params);
}

function rhipe_csp_ListAccounts( array $params ) {
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