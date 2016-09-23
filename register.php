<?php
header("Content-Type: application/json");
require 'database.php';

// if username exists in the database already, returns true
function username_exists($username){
	global $mysqli;

	$query="select username, password from users where username = ?";
	$stmt = $mysqli->prepare($query);

	if(!$stmt){
		printf("Error with query: %s", $mysqli->error);
		exit;
	}

	$stmt->bind_param('s', $username);
	$stmt->execute();
	$query_result = $stmt->get_result();
	$row = $query_result->fetch_assoc();

	if(!empty($row["username"])){
		return true;
	}

	$stmt->close();

	return false;
}

// function creates a new user and password in the database
function create_new_user($first_name, $last_name, $username, $password){
	global $mysqli;

	$stmt = $mysqli->prepare("insert into users (first_name, last_name, username, password) values (?, ?, ?, ?);");

	if(!$stmt){
		printf("Query Prep Failed: %s\n", $mysqli->error);
		exit;
	}

	$stmt->bind_param('ssss', $first_name, $last_name, $username, $password);
	$stmt->execute();
	$stmt->close();
}


$name = $_POST['name'];
if(empty($name)){
	echo json_encode(array("success" => false, "message" => "No name entered."));
	exit;
}
else{
	$first_and_last_name = explode(" ", $name); // if a name is provided, want to make sure it has a first and last name

	if(count($first_and_last_name) != 2){
		echo json_encode(array("success" => false, "message" => "Please enter a first and last name."));
		exit;
	}
}


if(empty($_POST['username'])){
	echo json_encode(array("success" => false, "message" => "No email entered."));
	exit;
}
else{
	// want to make sure it is an email
	$username = trim($_POST["username"]);
	if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
	  	echo json_encode(array("success" => false, "message" => "Invalid email format."));
		exit;
	}
}


if(empty($_POST['password'])){
	echo json_encode(array("success" => false, "message" => "No password entered."));
	exit;
}


$username = trim($_POST['username']);
$password = trim($_POST['password']);
$encrypted_password = crypt($password);


$username_exists = username_exists($username);

// if username already exists, asks them to choose another username
if($username_exists){
	echo json_encode(array("success" => false, "message" => "Username already exists. Please try another."));
	exit;
}
else{
	$first_and_last_name = explode(" ", $name);
	$first_name = $first_and_last_name[0];
	$last_name = $first_and_last_name[1];

	create_new_user($first_name, $last_name, $username, $encrypted_password);


	ini_set("session.cookie_httponly", 1);
	session_start();
	$_SESSION['current_user'] = $username;


	echo json_encode(array("success" => true));
	exit;
}

?>