<?php
    session_start();
    require_once 'login.php';

    define("NO_DATA",0); 
    define("SESSION_VAR", 1);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    //sign up and login forms
    if(!isset($_SESSION['name'])){
        echo <<<_END
        <html>
        <head><title>Midterm 2</title></head><body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <h1>Hello!</h1>
        <h2>Sign Up</h2>
        <form method = "post" action="main.php">
            <label for ="register_name">Name:</label><br>
            <input type="text" id="register_name" name="register_name"><br><br>
            <label for ="register_username">Username:</label><br>
            <input type="text" id="register_username" name="register_username"><br><br>
            <label for ="register_password">Password:</label><br>
            <input type="password" id="register_password" name="register_password"><br><br>
            <button>Sign Up</button>
        </form>

        <h2>Login</h2>
        <form method = "post" action="main.php">
            <label for ="username_login">Username:</label><br>
            <input type="text" id="username_login" name="username_login"><br><br>
            <label for ="password_login">Password:</label><br>
            <input type="password" id="password_login" name="password_login"><br><br>
            <button>Login</button>
        </form>
        _END;

        echo "</body></html>";
    }
    
    //sign up
    if(!empty($_POST['register_name']) && !empty($_POST['register_username']) && !empty($_POST['register_password'])){
        //sanitize inputs
        $name = mysql_entities_fix_string($conn,$_POST['register_name']);
        $username = mysql_entities_fix_string($conn,$_POST['register_username']);
        $password = mysql_entities_fix_string($conn,$_POST['register_password']);

        //check if username already exists
        checkUniqueUsername($username, $conn);

        //hash the password
        $password = password_hash($password, PASSWORD_DEFAULT);

        //add user to database
        addUser($name, $username, $password, $conn);

        echo "You may now log in with your credentials.";

    }

    //login
    if(!empty($_POST['username_login']) && !empty($_POST['password_login'])){
        //sanitize inputs
        $username = mysql_entities_fix_string($conn,$_POST['username_login']);
        $password = mysql_entities_fix_string($conn,$_POST['password_login']);

        //query credentials
        $query = "SELECT * FROM credentials WHERE username ='$username'";
        $result = $conn->query($query);

        if(!$result || mysqli_num_rows($result) == NO_DATA){
          die (printError());  
        } 
        elseif ($result->num_rows){
            $row = $result->fetch_array(MYSQLI_NUM);
            $result->close();
            $user_id = $row[0];
            $stored_name = $row[1];
            $stored_username = $row[2];
            $stored_token = $row[3];

            //check if credentials are correct
            if($username == $stored_username && password_verify($password, $stored_token)){
                //store name of the user
                $_SESSION['name'] = $stored_name;

                //store user id
                $_SESSION['user_id'] = $user_id;

                //regenerate new session id each time user logs in
                if(!isset($_SESSION['initiated'])){
                    session_regenerate_id();
                    $_SESSION['initiated'] = SESSION_VAR;
                }

                //refresh page so it can render home page
                header("Location: /cs174/midterm2/home.php");
                
            }
            else{
                die("Invalid username/password combination");
            }
        }

    }


    //for printing error messages
    function printError(){
        echo "Please try again.";
    }

    //sanitize user input
    function mysql_entities_fix_string($conn,$string){
        return htmlentities(mysql_fix_string($conn,$string));
    }

    //sanitize user input
    function mysql_fix_string($conn,$string){
        $string = stripslashes($string);
        return $conn->real_escape_string($string);
    }

    //check that the username does not already exist in the database
    function checkUniqueUsername($username, $conn){
        $query = "SELECT username FROM credentials WHERE username = '$username'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        //if result returns more than 0 rows, then username exists
        if($num_rows > NO_DATA){
            die("Username already exists.");
        }

        //close result
        $result->close();
    }

    //insert title and file lines to the database
    function addUser($name, $username, $password, $conn){
        $query = "INSERT INTO credentials (name, username,token) VALUES('$name','$username','$password')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
    }

    //close connection
    $conn->close();

?>
