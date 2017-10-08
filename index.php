<?php

require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

class AwsClient {
    
    function createClient($region) {
        $this->client = DynamoDbClient::factory(array(
            'region' => $region,
            'version' => '2012-08-10',
            'profile' => $region,
            )
        );
    }
}

class Handler {
 
    var $client = null;
    var $table = 'validation';

    public function __construct() {
        $this->client = new AwsClient();
    }

}

class ValidationHandler extends Handler {

    function post($region, $instance_id) {
        
    }

    function get($region, $instance_id) {
        try {
            $this->client->createClient($region);
            print_r($this->client);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}

class DisplayHandler extends Handler {
    
    function get($region) {
        echo "DisplayHandler: $region";
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "DisplayHandler",
    "/([a-zA-Z0-9-_]+)/([a-zA-Z0-9-_]+)" => "ValidationHandler",
));

