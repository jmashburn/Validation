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
        $ip_address = ((array_key_exists('ip', $_GET))?$_GET['ip']:'0.0.0.0');
        $hostname   = ((array_key_exists('hostname', $_GET))?$_GET['hostname']:'UNKNOWN');
        $test       = ((array_key_exists('test', $_GET))?$_GET['test']:'null');
        $result     = ((array_key_exists('result', $_GET))?$_GET['result']:'UNKNOWN');
        $marshaler = new Marshaler();

        $inputs = array(
                'instance_id' => $instance_id,
                'ip_address'  => $ip_address,
                'hostname'    => $hostname,
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
		print_r(json_encode($inputs));
		return true;
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
		if (!isset($item['hostname'])) continue;
                $item = $item->toArray();
                $ec2['instances'][$item['instance_id']]['ip_address'] = $item['ip_address'];
                $ec2['instances'][$item['instance_id']]['hostname'] = $item['hostname'];
                $ec2['instances'][$item['instance_id']]['region'] = $item['region'];
                $ec2['instances'][$item['instance_id']]['timestamp'] = (in_array('timestamp',array_keys($item))?$item['timestamp']:time());
                $ec2['instances'][$item['instance_id']]['validation'][$item['test']] = $item['result'];
        }
?>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
  	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<style>
        td { font-size: 10px; }
		.ERROR { background-color: #d9534f; };
		.SUCCESS { background-color: #5cb85c; };
	</style>
</head>
<body>
<div class="container">
	<table class="table table-striped">
        <tr><th>Instance ID</th><th>IP Address</th><th>Hostname</th><th>Region</th><th colspan="5">Test</th></tr>
<?php
        foreach ($ec2['instances'] as $instance_id => $instance) {
          echo "<tr><td>".$instance_id."</td><td>".$instance['ip_address']."</td><td>".$instance['hostname']."</td><td>".$instance['region']."</td><td>";
          foreach ($instance['validation'] as $test => $validation) {
            echo "<td class=\"$validation\">". $test ."</td>";
          }
          echo "</td></tr>";
        }
?>
 </table>
</div>
 </body>
 </html>
<?php 
    }
}

Toro::serve(array(
    "/([a-zA-Z0-9-_]+)" => "DisplayHandler",
    "/([a-zA-Z0-9-_]+)/([a-zA-Z0-9-_]+)" => "ValidationHandler",
));
