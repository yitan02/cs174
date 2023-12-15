<?php
    session_start();
    require_once 'login.php';

    define("NO_DATA",0); 
    define("SESSION_VAR", 1);
    define("MAX_NAME_LENGTH", 40);
    define("MAX_USERNAME_LENGTH", 30);
    define("MIN_PASSWORD_LENGTH", 5);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    //sign up and login forms
    if(!isset($_SESSION['user_id'])){
        echo <<<_END
        <html>
        <head>
            <title>Homework 6</title>
            <script>
                const MAX_NAME_LENGTH = 100;
                const MAX_USERNAME_LENGTH = 30;
                const MIN_PASSWORD_LENGTH = 5;

                function validateSignup(form){ 
                    fail = validateName(form.register_name.value);
                    fail += validateUsername(form.register_username.value);
                    fail += validateEmail(form.register_email.value);
                    fail += validatePassword(form.register_password.value);

                    if (fail == ""){
                        return true;
                    }
                    else{
                        alert(fail);
                        return false;
                    }
                }

                function validateLogin(form){ 
                    fail = validateUsername(form.username_login.value);
                    fail += validatePassword(form.password_login.value);

                    if (fail == ""){
                        return true;
                    }
                    else{
                        alert(fail);
                        return false;
                    }
                }

                function validateName(field){
                    if (field == ""){
                        return "Please enter a name. ";
                    }
                    else if (field.length > MAX_NAME_LENGTH){
                        return "Name can only be max of 100 characters. ";
                    }
                    else if (/[^a-zA-Z]/.test(field)){
                        return "Name can only have letters. ";
                    }
                    return "";
                }

                function validateUsername(field){
                    if (field == ""){
                        return "Please enter a username. ";
                    }
                    else if (field.length > MAX_USERNAME_LENGTH){
                        return "Username can only be up to 30 characters. ";
                    }
                    return "";
                }

                function validateEmail(field){
                    if (field == ""){
                        return "Please enter an email. ";
                    }
                    else if (!(/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/.test(field))){
                        return "Please enter a valid email. ";
                    }
                    return ""
                }

                function validatePassword(field){
                    if (field == ""){
                        return "Please enter a password. ";
                    }
                    else if (field.length < MIN_PASSWORD_LENGTH){
                        return "Password must be more than 5 characters. ";
                    }

                    return "";
                }


            </script>
        </head>
        
        <body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <h1>Hello!</h1>
        <h2>Sign Up</h2>
        <form method = "post" action="main.php" onsubmit="return validateSignup(this)">
            <label for ="register_name">Name:</label><br>
            <input type="text" id="register_name" name="register_name"><br><br>

            <label for ="register_username">Username:</label><br>
            <input type="text" id="register_username" name="register_username"><br><br>

            <label for ="register_email">Email:</label><br>
            <input type="text" id="register_email" name="register_email"><br><br>
            
            <label for ="register_password">Password:</label><br>
            <input type="password" id="register_password" name="register_password"><br><br>
            <button>Sign Up</button>
        </form>

        <h2>Login</h2>
        <form method = "post" action="main.php" onsubmit="return validateLogin(this)">
            <label for ="username_login">Username:</label><br>
            <input type="text" id="username_login" name="username_login"><br><br>

            <label for ="password_login">Password:</label><br>
            <input type="password" id="password_login" name="password_login"><br><br>
            <button>Login</button>
        </form>
        
        </body></html>
    _END;
    }
    
    //sign up
    if(!empty($_POST['register_name']) && !empty($_POST['register_username']) && !empty($_POST['register_email']) && !empty($_POST['register_password'])){
        //sanitize inputs
        $name = mysql_entities_fix_string($conn,$_POST['register_name']);
        $username = mysql_entities_fix_string($conn,$_POST['register_username']);
        $email = mysql_entities_fix_string($conn,$_POST['register_email']);
        $password = mysql_entities_fix_string($conn,$_POST['register_password']);

        //validate inputs
        $fail = validate_name($name);
        $fail .= validate_username($username);
        $fail .= validate_email($email);
        $fail .= validate_password($password);

        if($fail == ""){
            //check if ID already exists
            checkUniqueUsername($username, $conn);

            //check if email already exists
            checkUniqueEmail($email,$conn);

            //hash the password
            $password = password_hash($password, PASSWORD_DEFAULT);

            //add user to database
            addUser($name, $username, $email, $password, $conn);

            echo "You may now log in with your credentials.";
        }
        else{
            exit;
        }
    }

    //login
    if(!empty($_POST['username_login']) && !empty($_POST['password_login'])){
        //sanitize inputs
        $username = mysql_entities_fix_string($conn,$_POST['username_login']);
        $password = mysql_entities_fix_string($conn,$_POST['password_login']);

        //validate inputs
        $fail = validate_username($username);
        $fail .= validate_password($password);

        if ($fail == ""){
            //query credentials
            $query = "SELECT * FROM credentials WHERE username ='$username'";
            $result = $conn->query($query);

            if(!$result || mysqli_num_rows($result) == NO_DATA){
                die (printError());  
            } 
            elseif ($result->num_rows){
                $row = $result->fetch_array(MYSQLI_NUM);
                $result->close();
                $stored_uid = $row[0];
                $stored_name = $row[1];
                $stored_username = $row[2];
                $stored_token = $row[4];

                //check if credentials are correct
                if($username == $stored_username && password_verify($password, $stored_token)){
                    //store name of the user
                    $_SESSION['name'] = $stored_name;

                    //store user id
                    $_SESSION['user_id'] = $stored_uid;

                    //regenerate new session id each time user logs in
                    if(!isset($_SESSION['initiated'])){
                        session_regenerate_id();
                        $_SESSION['initiated'] = SESSION_VAR;
                    }

                    //direct user to home page 
                    header("Location: /cs174/final/home.php");
                    
                }
                else{
                    die("Invalid username/password combination");
                }
            }
        }
        else{
            printError();
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
            die("User already exists.");
        }

        //close result
        $result->close();
    }

    //check that the email does not already exist in the database
    function checkUniqueEmail($email, $conn){
        $query = "SELECT email FROM credentials WHERE email = '$email'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        //if result returns more than 0 rows, then email exists
        if($num_rows > NO_DATA){
            die("Email already exists.");
        }

        //close result
        $result->close();
    }

    //insert user to the database
    function addUser($name, $username, $email, $password, $conn){
        $query = "INSERT INTO credentials (name, username, email, token) VALUES('$name','$username','$email','$password')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
    }

    //validate name
    function validate_name($field){
        if ($field == ""){
            return "Please enter a name.<br>";
        }
        elseif (strlen($field) > MAX_NAME_LENGTH){
            return "Name can only be max of 100 characters.<br>";
        }
        elseif(preg_match("/[^a-zA-Z]/", $field)){
            return "Only letters allowed for name.<br>";
        }
        return "";
        
    }

    //validate username
    function validate_username($field){
        if ($field == ""){
            return "Please enter a username.<br>";
        }
        elseif (strlen($field) > MAX_USERNAME_LENGTH){
            return "Username can only be up to 30 characters.<br>";
        }
        return ""; 
    }

    //validate email
    function validate_email($field){
        if ($field == ""){
            return "Please enter an email.<br>";
        }
        elseif(!(preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/", $field))){
            return "Only valid email allowed.<br>";
        }
        return "";
    }

    //validate password
    function validate_password($field){
        if ($field == ""){
            return "Please enter a password.<br>";
        }
        elseif(strlen($field) < MIN_PASSWORD_LENGTH){
            return "Password must be more than 5 characters.<br>";
        }
        return "";    
    }

    //close connection
    $conn->close();

?>
