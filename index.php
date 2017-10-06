<?php

require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;


class ValidationHandler {

    var $table = 'validation';
    var $client = null;

    function get($region) {
        try {
            $this->createClient($region);
            $result = $client->describeTable(array(
                'TableName' => $this->table
            ));
            var_export($result);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    function createClient($region) {
        $this->client = DynamoDbClient::factory(array(
            'region' => $region
            )
        );
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "ValidationHandler",
));

