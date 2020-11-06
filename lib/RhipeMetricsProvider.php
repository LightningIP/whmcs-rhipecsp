<?php

namespace WHMCS\Module\Server\rhipe_csp;

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

        $tenants = $api->getTenantsAndSubscriptions($this->moduleParams['serveraccesshash']);
        
        $usage = [];
        foreach ($tenants as $tenant) {
            foreach($tenant->Subscriptions as $subscription) {
                $usage[$subscription->SubscriptionId] = $this->wrapUserData($subscription, $api);
            }
        }

        return $usage;
    }
    
    public function tenantUsage($subscriptionId)
    {
        
        $api = new API($this->moduleParams['serverusername'], $this->moduleParams['serverpassword']);
        $subscription = $api->getSubscription($subscriptionId);        
        $data = $this->wrapUserData($subscription, $api);
        return $data;
    }

    private function wrapUserData($subscription, $api)
    {
        $metrics = [];
        foreach ($this->metrics() as $metric) {
            if ($metric->systemName() === $subscription->Unit) {
                if ($subscription->Unit === 'Usage-based') {
                    $usage = $api->getAzureUsage($subscription->SubscriptionId);
                    $metrics[] = $metric->withUsage(new Usage($usage->TotalCost));
                }
                else {
                    if ($subscription->status === 'Suspended') {
                        $metrics[] = $metric->withUsage(new Usage(0));
                    }
                    $metrics[] = $metric->withUsage(new Usage($subscription->Quantity));
                }
            } else {
                $metrics[] = $metric->withUsage(new Usage(0));
            }
        }
        return $metrics;
    }
    
}