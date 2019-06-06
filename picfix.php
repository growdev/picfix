<?php

require './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

use Aws\S3\S3Client;
$key = getenv('S3_KEY');
$secret = getenv('S3_SECRET');
$credentials = new Aws\Credentials\Credentials($key, $secret);

$s3 = new Aws\S3\S3Client([
	'version' => 'latest',
	'region' => 'us-east-1',
	'credentials' => $credentials 
]);

/*
$result = $s3->listBuckets();
$buckets = $result->get('Buckets');

foreach ( $buckets as $bucket ){
	echo $bucket['Name'] . PHP_EOL;
}
*/

/*
$response = $s3->listObjects([
'Bucket' => 'TeamEspinozaData',
'MaxKeys' => 1000,
'Prefix' => 'Photos/2018'
]);

$files = $response->getPath('Contents');
foreach ($files as $file){
	echo $file['Key'] . PHP_EOL;
}
*/

$object = 'Photos/2018/IMG_5078.JPG';

$response = $s3->headObject([
	'Bucket' => 'TeamEspinozaData',
	'Key' => $object,
]);

print_r( $response );

echo "oh, hello\n";

