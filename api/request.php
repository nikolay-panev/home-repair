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
		if(count($data['images']) > 0) {
			$group_name = $data['customer'] . "-" . date("YmdHis");
			$response = $api->createImageGroup($group_name, $data['formatted_address']);
			$group_id = $response['id'];
			
			if($group_id) {
				$images = [];
				foreach($data['images'] as $image) {
					$response = $api->create360Image($image, $group_id);
					$images[] = $response['url'];
				}
				
				$report = $api->create360Report($group_id, $data['formatted_address']);
				
				if($report['id']) {
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
					
					$renovation = [];
					foreach($info['renovation']['areas'] as $area) {
						foreach($area['remodelProjects'] as $project) {
							foreach($project['repairItems'] as $item) {
								$renovation[] = ['area' => $area['label'], 'name' => $project['name'], 'description' => $item['description'], 'quantity' => $item['quantity'], 'ppu' => $item['ppu'], 'total_cost' => $item['totalCost'], 'diy_cost' => $item['diyAmount']];
							}
						}
					}
					
					die(json_encode($renovation));
				}
			}
		}
	}
	else if($data['api'] == 'pros') {
		// Thumbtack API
		require_once(__DIR__ . "/classes/Thumbtack.php");
		
		$api = new Thumbtack($db, 'Sandbox');
		$response = json_decode($api->getPros($data['zip'], $data['category']), true);
		$pros = $response['response']['results'];
		
		if($data['min_rating'] > 0 && is_countable($pros) && count($pros) > 0) {
			$pros = array_filter($pros, function($item) { return $item['rating'] > $data['min_rating']; });
		}
		
		die(json_encode($pros));
	}
}