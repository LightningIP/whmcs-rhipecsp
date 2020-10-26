<?php

namespace WHMCS\Module\Server\lip_rhipe_csp;

use WHMCS\UsageBilling\Contracts\Metrics\MetricInterface;
use WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface;
use WHMCS\UsageBilling\Metrics\Metric;
use WHMCS\UsageBilling\Metrics\Units\Accounts;
use WHMCS\UsageBilling\Metrics\Usage;

class RhipeMetricsProvider implements ProviderInterface
{
    private $moduleParams = [];
    public function __construct($moduleParams)
    {
        // A sample `$params` array may be defined as:
        //
        // ```
        // array(
        //     "server" => true
        //     "serverid" => 1
        //     "serverip" => "11.111.4.444"
        //     "serverhostname" => "my.testserver.tld"
        //     "serverusername" => "root"
        //     "serverpassword" => ""
        //     "serveraccesshash" => "ZZZZ1111222333444555AAAA"
        //     "serversecure" => true
        //     "serverhttpprefix" => "https"
        //     "serverport" => "77777"
        // )
        // ```
        $this->moduleParams = $moduleParams;
    }

    public function metrics()
    {
        return [
            new Metric(
                'User',
                'Users',
                MetricInterface::TYPE_SNAPSHOT,
                new Accounts('Users')
            ),
            new Metric(
                'Licenses',
                'Licenses',
                MetricInterface::TYPE_SNAPSHOT,
                new Accounts('Licenses')
            ),
            new Metric(
                'Usage-based',
                'Usage',
                MetricInterface::TYPE_PERIOD_MONTH,
                new UsageMetric('Usage-based')
            ),
        ];
    }

    public function usage()
    {
        $api = new API($this->moduleParams['serverusername'], $this->moduleParams['serverpassword']);

        $tenants = $api->getTenantsAndSubscriptions('bc7e9cfb-7aeb-e711-817c-e0071b65e251');

        $usage = [];
        foreach ($tenants as $tenant) {
            foreach($tenant->Subscriptions as $subscription)
            $usage[$subscription->SubscriptionId] = $this->wrapUserData($tenant, $subscription, $api);
        }

        return $usage;
    }
    
    public function tenantUsage($tenant)
    {
        $userData = $this->apiCall('user_stats');
        
        return $this->wrapUserData($userData);
    }

    private function wrapUserData($tenant, $subscription, $api)
    {
        foreach ($this->metrics() as $metric) {
            if ($metric->systemName() === $subscription->Unit) {
                if ($subscription->Unit === 'Usage-based') {
                    $usage = $api->getAzureUsage($subscription->SubscriptionId);
                    return [ $metric->withUsage(new Usage($usage->TotalCost))];
                }
                else {
                    if ($subscription->status === 'Suspended') {
                        return [ $metric->withUsage(new Usage(0)) ];
                    }
                    return [ $metric->withUsage(new Usage($subscription->Quantity)) ];
                }
            }
        }
    }
    
}