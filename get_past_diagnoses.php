<?php
	header("Content-Type: application/json");
	require 'database.php';
	ini_set("session.cookie_httponly", 1);
	session_start();

	global $mysqli;

	$username = $_SESSION["current_user"];

	
	$stmt = $mysqli->prepare("select * from diagnoses where username = ? order by date_of_diagnosis desc;");
	
	if(!$stmt){
		printf("Error with query: %s", $mysqli->error);
		exit;
	}
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$query_result = $stmt->get_result();


	$output = array();

	while($row = $query_result->fetch_assoc()){
		$patient_id = $row["patient_id"];
		$patient_first_name = $row["patient_first_name"];
		$patient_last_name = $row["patient_last_name"];
		$has_cancer = $row["has_cancer"];
		$confidence = $row["confidence"];
		$date_of_diagnosis = $row["date_of_diagnosis"];

		// append res to $output
		$res = array("patient_id" => $patient_id, "patient_first_name" => $patient_first_name,
			"patient_last_name" => $patient_last_name, "has_cancer" => $has_cancer,
			"confidence" => $confidence, "date_of_diagnosis" => $date_of_diagnosis);

		array_push($output, $res);
	}
	
	
	echo json_encode($output;
	$stmt->close();
	exit;
?>