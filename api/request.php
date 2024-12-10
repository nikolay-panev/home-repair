<?php
require_once(__DIR__ . "/config.php");

$data = json_decode(file_get_contents('php://input'), true);

if($data['api']) {
    file_put_contents(__DIR__ . "/storage/log.txt", "HTTP_X_FORWARDED_FOR:" . print_r($_SERVER['HTTP_X_FORWARDED_FOR'], true) . "; REMOTE ADDR: ". $_SERVER['REMOTE_ADDR']);
    
	if($data['api'] == 'address') {
		// Google Places API
		require_once(__DIR__ . "/classes/GooglePlaces.php");
		
		$api = new GooglePlaces($db, 'Sandbox');
		die($api->getAddress($data['request']));
	}
	else if($data['api'] == 'address_details') {
		// Google Places API
		require_once(__DIR__ . "/classes/GooglePlaces.php");
		
		$api = new GooglePlaces($db, 'Sandbox');
		die($api->getAddressDetails($data['pid']));
	}
	else if($data['api'] == 'ai') {
		// FoxyAI API
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		$api = new FoxyAI($db, 'Sandbox');
		if($data['group_id']) {
			$report = $api->propertyReport($data['group_id'], $data['street'], $data['city'], $data['state'], $data['zip']);
			$estimate = $api->createEstimates($report, $data['tags']);
			
			// Save the report in compressed format
			$stmt = $db->prepare('INSERT INTO `reports` (`report_id`, `image_group_id`, `customer`, `category`, `areas`, `address`, `email`, `data`, `created`) VALUES (:report_id, :image_group_id, :customer, :category, :areas, :address, :email, :data, :created)');
			$stmt->bindValue(':report_id', $report['_id']);
			$stmt->bindValue(':image_group_id', $data['group_id']);
			$stmt->bindValue(':customer', ($data['customer'] ? $data['customer'] : "RECONai"));
			$stmt->bindValue(':category', $data['category']);
			$stmt->bindValue(':areas', implode(",", $data['tags']));
			$stmt->bindValue(':address', $data['formatted_address']);
			$stmt->bindValue(':email', $data['email']);
			$stmt->bindValue(':data', gzcompress(json_encode($report)));
			$stmt->bindValue(':created', date("Y-m-d H:i:s"));
			$stmt->execute();

			die(json_encode(['estimate' => $estimate]));
		}
	}
	else if($data['api'] == 'create_group') {
		// FoxyAI API
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		$group_name = date("YmdHis");
		
		$api = new FoxyAI($db, 'Sandbox');
		$response = $api->createImageGroup($group_name, $data['formatted_address']);
		
		die(json_encode(['group_id' => $response['_id']]));
	}
	else if($data['api'] == 'create_image') {
		// FoxyAI API
		$output = [];
		
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		if($data['group_id'] && is_array($data['images']) && count($data['images']) > 0) {
			$api = new FoxyAI($db, 'Sandbox');
			$response = $api->batchCreateImages($data['images'], $data['group_id']);
			
			$timeout = 3;
			$results = [];
			while($timeout > 0) {
				sleep(1);
				$results = $api->getImageGroupResults($data['group_id']);

				if(@$results['status'] == "completed") break;
				$timeout--;
			}
			
			if(@$results['status'] == "completed") {
				foreach($results['images'] as $image) {
					$tag = is_array($image['models']['room_classification']['results']) ? $image['models']['room_classification']['results'][0]['class'] : null;
					
					$output[] = ['id' => $image['_id'], 'tag' => $tag, 'url' => $image['url']];
				}
			}
			else {
				foreach($data['images'] as $image) {
					$output[] = ['id' => null, 'tag' => null, 'url' => $image];
				}
			}
		}
		die(json_encode($output));
	}
	else if($data['api'] == 'pros') {
		// Thumbtack API
		require_once(__DIR__ . "/classes/Thumbtack.php");
		
		$api = new Thumbtack($db, 'Sandbox');
		$response = $api->getPros($data['zip'], $data['category']);
		$pros = $response['results'];
		
		if($data['min_rating'] > 0 && is_countable($pros) && count($pros) > 0) {
			$pros = array_filter($pros, function($item) { return $item['rating'] > $data['min_rating']; });
		}
		
		die(json_encode($pros));
	}
}