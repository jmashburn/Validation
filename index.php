<?php
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Iterator\ItemIterator;

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
        $instances = new ItemIterator($client->getScanIterator(array(
            'TableName' => $this->table
        )));
        $ec2 = array();
        foreach ($instances as $item) {
                $item = $item->toArray();
                $ec2['instances'][$item['instance_id']]['ip_address'] = $item['ip_address'];
                $ec2['instances'][$item['instance_id']]['region'] = $item['region'];
                $ec2['instances'][$item['instance_id']]['timestamp'] = (in_array('timestamp',array_keys($item))?$item['timestamp']:time());
                $ec2['instances'][$item['instance_id']]['validation'][$item['test']] = $item['result'];
        }
        echo "<html>";
        echo "<style>";
        echo ".SUCCESS { background: green }";
        echo ".ERROR { background: red }";
        echo "</style>";
        echo "<body>";
        echo "<table width=\"100%\" border=\"1\">";
        echo "<tr><th>Instance ID</th><th>IP Address</th><th>Region</th><th colspan=\"5\">Test</th></tr>";
        foreach ($ec2['instances'] as $instance_id => $instance) {
          echo "<tr><td>".$instance_id."</td><td>".$instance['ip_address']."</td><td>".$instance['region']."</td><td>";
          echo "<table width=\"100%\"><tr>";
          foreach ($instance['validation'] as $test => $validation) {
            echo "<td class=\"$validation\">". $test ."</td>";
          }
          echo "</tr></table></td></tr>";
        }
        echo "</table>";
        echo "</body>";
        echo "</html>";
        #$json = json_encode($ec2);
        #echo $json;
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "DisplayHandler",
    "/([a-zA-Z0-9-_]+)/([a-zA-Z0-9-_]+)" => "ValidationHandler",
));
