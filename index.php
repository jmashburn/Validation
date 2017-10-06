<?php

require 'vendor/autoload.php';

use Aws\Ec2\Ec2Client;


class HelloHandler {
    function get() {
  		$ec2Client = new Ec2Client([
    		'region' => 'us-west-2',
    		'version' => '2016-11-15',
		]);
     	$result = $ec2Client->describeInstances();
		var_dump($result); 
    }
}

Toro::serve(array(
    "/" => "HelloHandler",
));

