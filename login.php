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

// if the username-password combination is correct, returns true. otherwise false
function valid_combo($username, $password){
    global $mysqli;

    $stmt = $mysqli->prepare("select username, password from users where username = ?;");
    if(!$stmt){
        printf("Error with query: %s", $mysqli->error);
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $query_result = $stmt->get_result();

    $first_row = $query_result->fetch_assoc();
    $valid_username = $first_row['username'];
    $valid_password = $first_row['password'];

    if($valid_username == $username && crypt($password, $valid_password) == $valid_password){
        return true;
    }
    
    $stmt->close();
    return false;
}


?>

<?php
    if(empty($_POST['username'])){
        echo json_encode(array("success" => false, "message" => "No username entered."));
        exit;
    }
    else if(empty($_POST['password'])){
        echo json_encode(array("success" => false, "message" => "No password entered."));
        exit;       
    }
    else{
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $username_search_result = username_exists($username);
        $valid_combo = valid_combo($username, $password);

        if(!$username_search_result){
            echo json_encode(array("success" => false, "message" => "Username does not exist."));
            exit;
        }
        else if(!$valid_combo){
            echo json_encode(array("success" => false, "message" => "Incorrect Username or Password."));
            exit;
        }
        else{
            ini_set("session.cookie_httponly", 1);
            session_start();
            $_SESSION['current_user'] = $username;
            echo json_encode(array("success" => true));
            exit;
        }
    }
?>
