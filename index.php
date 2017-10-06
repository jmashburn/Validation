<?php

require 'vendor/autoload.php';

use Aws\SimpleDb\SimpleDbClient;


class HelloHandler {

    var $client = null;

    function get($region) {
        try {
            $this->createClient($region);
            if ($this->createDomain($region)) {
                $domains = $this->client->getIterator('ListDomains')->toArray();
                var_export($domains);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    function createClient($region) {
        $this->client = SimpleDbClient::factory(array(
            'region' => $region
            )
        );
    }


    function createDomain($domain) {
        if (!$this->client) $this->createClient($domain);
        if ($client->createDomain(array('DomainName' => $region))) {
            return true;
        }
        return false;
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "HelloHandler",
));

