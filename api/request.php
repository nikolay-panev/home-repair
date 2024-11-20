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
			//if(is_array($report['areas']) && count($report['areas']) > 0) {
				/*
					$stmt = $db->prepare('INSERT INTO `reports` (`uuid`, `category`, `address`, `email`, `images`, `customer`, `status`) VALUES (:uuid, :category, :address, :email, :images, :customer, :status)');
					$stmt->bindValue(':uuid', $report['id']);
					$stmt->bindValue(':category', $data['category']);
					$stmt->bindValue(':address', $data['formatted_address']);
					$stmt->bindValue(':email', $data['email']);
					$stmt->bindValue(':images', json_encode($data['images']));
					$stmt->bindValue(':customer', $data['customer']);
					$stmt->bindValue(':status', $report['status']);
					$stmt->execute();
					$report_id = $db->lastInsertId();
					
					if($report['status'] == "completed") {
						// Store pdf on local server
						file_put_contents(STORAGE_PATH . "/reports/" . $report['id'] . ".pdf", file_get_contents($report['downloadUrl']));
						
						$stmt = $db->prepare('UPDATE `reports` SET `report_file` = :report_file WHERE `id` = :id');
						$stmt->bindValue(':report_file', STORAGE_URL . "reports/" . $report['id'] . ".pdf");
						$stmt->bindValue(':id', $report_id);
						$stmt->execute();
					}
					
					// Get 360 report
					$info = $api->get360Report($report['id']);
					
					$stmt = $db->prepare('UPDATE `reports` SET `report_json` = :report_json WHERE `id` = :id');
					$stmt->bindValue(':report_json', json_encode($info));
					$stmt->bindValue(':id', $report_id);
					$stmt->execute();
				*/	
					$estimate = $api->createEstimates($report, $data['tags']);
					
					
			//}
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

				if($results['status'] == "completed") break;
				$timeout--;
			}
			
			foreach($results['images'] as $image) {
				$output[] = ['id' => $image['_id'], 'tag' => @$image['models']['room_classification']['results'][0]['class'], 'url' => $image['url']];
			}
			//$output = array_map(function($item) { return array_merge($item, ['tag' => null]); }, @$response['images']);
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
	else if($data['api'] == 'gpt') {
		// ChatGPT API
		require_once(__DIR__ . "/classes/ChatGPT.php");
		
		$message = "Provide a cost estimate for the following service: " . $data['category'] . " in " . $data['city'] . ", " . $data['state'].". Reply briefly with a number in USD.";
		$api = new ChatGPT($db, 'Production');
		$response = $api->sendMessage($message);
		
		die(json_encode(['estimate' => $response['choices'][0]['message']['content'], 'debug' => $response]));
	}
}