<?php

require 'vendor/autoload.php';

use Aws\Ec2\Ec2Client;


class HelloHandler {
    function get() {
        echo sprintf("AWS_ACCESS_KEY_ID: %s, AWS_SECRET_ACCESS_KEY: %s", getenv("AWS_ACCESS_KEY_ID"), getenv("AWS_SECRET_ACCESS_KEY"));
  		#$ec2Client = new Ec2Client([
    	#	'region' => 'us-west-2',
    	#	'version' => '2016-11-15',
    	#	'profile' => 'default'
		#]);
     	#$result = $ec2Client->describeInstances();
		#var_dump($result); 
    }
}

Toro::serve(array(
    "/" => "HelloHandler",
));

