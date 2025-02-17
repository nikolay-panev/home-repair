<?php
require_once(__DIR__ . "/config.php");

$data = json_decode(file_get_contents('php://input'), true);

if($data['api']) {    
	if($data['api'] == 'address') {
		// Google Places API
		require_once(__DIR__ . "/classes/GooglePlaces.php");
		
		$api = new GooglePlaces($db, 'Production');
		die($api->getAddress($data['request']));
	}
	else if($data['api'] == 'address_details') {
		// Google Places API
		require_once(__DIR__ . "/classes/GooglePlaces.php");
		
		$api = new GooglePlaces($db, 'Production');
		die($api->getAddressDetails($data['pid']));
	}
	else if($data['api'] == 'ai') {
		// FoxyAI API
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		$api = new FoxyAI($db, 'Production');
		if($data['group_id']) {
			$report = $api->propertyReport($data['group_id'], $data['street'], $data['city'], $data['state'], $data['zip']);
			$estimate = $api->createEstimates($report, $data['tags']);
			$estimateHTML = $api->toHTML($estimate);
			
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
			
			// Send email
			$stmt = $db->prepare('SELECT * FROM `email_templates` WHERE `name` = "Project Estimate"');
			$stmt->execute();
			$template = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$message = $template['message'];
			$message = str_ireplace("{category}", $data['category'], $message);
			$message = str_ireplace("{estimate}", $estimateHTML, $message);
			
			$stmt = $db->prepare('INSERT INTO `emails`(`recipient`, `subject`, `message`, `attempts`, `created`) VALUES (:recipient, :subject, :message, :attempts, :created)');
			$stmt->bindValue(':recipient', $data['email']);
			$stmt->bindValue(':subject', $template['subject']);
			$stmt->bindValue(':message', $message);
			$stmt->bindValue(':attempts', '0');
			$stmt->bindValue(':created', date('Y-m-d H:i:s'));
			$stmt->execute();

			die(json_encode(['estimate' => $estimate]));
		}
	}
	else if($data['api'] == 'create_group') {
		// FoxyAI API
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		$group_name = date("YmdHis");
		
		$api = new FoxyAI($db, 'Production');
		$response = $api->createImageGroup($group_name, $data['formatted_address']);
		
		die(json_encode(['group_id' => $response['_id']]));
	}
	else if($data['api'] == 'create_image') {
		// FoxyAI API
		$output = [];
		
		require_once(__DIR__ . "/classes/FoxyAI.php");
		
		if($data['group_id'] && is_array($data['images']) && count($data['images']) > 0) {
			$api = new FoxyAI($db, 'Production');
			$response = $api->batchCreateImages($data['images'], $data['group_id']);
			
			$timeout = 1;
			$results = [];
			while($timeout > 0) {
				//sleep(1);
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
		
		$api = new Thumbtack($db, 'Production');
		$response = $api->getPros($data['zip'], $data['category']);
		$pros = $response['results'];
		
		if($data['min_rating'] > 0 && is_countable($pros) && count($pros) > 0) {
			$pros = array_filter($pros, function($item) { return $item['rating'] >= $data['min_rating']; });
		}
		
		die(json_encode($pros));
	}
}