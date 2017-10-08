<?php
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class AwsClient {

    function createClient($region) {
        return DynamoDbClient::factory(array(
            'region' => $region,
            'version' => '2012-08-10',
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

    function get($region, $instance_id) {
        $ip_address = (($_GET['ip'])?$_GET['ip']:'0.0.0.0');
        $test       = (($_GET['test'])?$_GET['test']:'null');
        $result     = (($_GET['result'])?$_GET['result']:'UNKNOWN');
        $marshaler = new Marshaler();

        $inputs = array(
                'instance_id' => $instance_id,
                'ip_address'  => $ip_address,
                'region'      => $region,
                'test'        => $test,
                'result'      => $result,
                'timestamp'   => (string) time(),
        );

        try {
                $client = $this->client->createClient($region);
                $result = $client->putItem(array(
                        'TableName' => $this->table,
                        'Item' => $marshaler->marshalItem($inputs)
                ));
        } catch (DynamoDbException $e) {
                echo $e->getMessage();
        }

    }
}

class DisplayHandler extends Handler {

    function get($region) {
        $client = $this->client->createClient($region);
        $iterator = $client->getIterator('Scan', array(
            'TableName' => $this->table
        ));
        foreach ($iterator as $item) {
			print_r($item);
		}
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "DisplayHandler",
    "/([a-zA-Z0-9-_]+)/([a-zA-Z0-9-_]+)" => "ValidationHandler",
));
