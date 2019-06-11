<?php

require './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

use Aws\S3\S3Client;
$key 		= getenv('S3_KEY');
$secret 	= getenv('S3_SECRET');
$bucket 	= getenv('S3_BUCKET');
$region 	= getenv('S3_REGION');
$prefix		= getenv('S3_PREFIX');
$db_host 	= getenv('DB_HOST');
$db_user 	= getenv('DB_USER');
$db_pass 	= getenv('DB_PASS');
$db_table 	= getenv('DB_TABLE');

$prefixes = [];
array_push($prefixes, $prefix);

$credentials = new Aws\Credentials\Credentials($key, $secret);

$s3 = new Aws\S3\S3Client([
	'version' => 'latest',
	'region' => 'us-east-1',
	'credentials' => $credentials 
]);

$mysqli = new mysqli($db_host, $db_user,$db_pass,$db_table);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
//======================================================================

$processed = 0;
$skipped = 0;
$error = 0;

foreach ( $prefixes as $prefix ) {
	echo 'Getting files for ' . $prefix . PHP_EOL;

 	try {
		$maxIteration = 10;
		$iteration = 0;
		$marker = '';

		$results = $s3->getPaginator('ListObjects', [
    		'Bucket' => $bucket,
   			 'Prefix' => $prefix,
   			 'Marker' => 'Photos/2006/12/23/2006_12_23_18_29.jpg',
   			]);

		foreach ($results as $result) {
    		foreach ($result['Contents'] as $file) {
				// check for extension
				if ( "/" == substr($file['Key'], -1)) {
					echo $file['Key'] . " is a directory." . PHP_EOL;
					$skipped++;
				} else {
					$parts = pathinfo( $file['Key'] );	

					$object = $file['Key'];
					$head_response = $s3->headObject([
						'Bucket' => $bucket,
						'Key' => $object,
					]);
					$fileinfo = $head_response->toArray();

					$filename 		= $parts['basename'];
					$last_modified  = $fileinfo['LastModified']->__toString();
					$content_type 	= $fileinfo['ContentType'];
					$effective_uri  = $fileinfo['@metadata']['effectiveUri'];
					$content_length = $fileinfo['ContentLength'];
					$private 		= 1;
					$bucket 		= $bucket;
					$bucket_path 	= $parts['dirname'];
					$hash 			= '';
					$latitude 		= '';
					$longitude 		= '';
					$extension 		= $parts['extension'];

					$sql = "INSERT INTO photos (filename, last_modified, content_type, effective_uri, content_length, private, bucket, bucket_path, file_hash, latitude, longitude) 
						values ('" . $filename . "','" . $last_modified . "','" . $content_type . "','" . $effective_uri . "','" . $content_length . "','" . $private . "','" . $bucket . "','" . $bucket_path . "','" . $hash . "','" . $latitude . "','" . $longitude . "');";
	
					if ( ! $mysqli->query($sql) ) {
						echo "error:(" . $mysqli->errno . ") " . $mysqli->error . PHP_EOL;
						$error++;
					} else {
						echo 'inserted ' . $parts['basename'] . PHP_EOL;
						$processed++;
					}
				
				//print_r( $head_response );
				}
			}
		}
	} catch (S3Exception $e) {
		echo $e->getMessage() . PHP_EOL;
		$error++;
	}

}


mysqli_close($mysqli);

echo 'TOTALS' . PHP_EOL;
echo 'processed: ' . $processed . PHP_EOL;
echo 'skipped  : ' . $skipped . PHP_EOL;
