<?php
    session_start();
    require_once 'login.php';

    define("NO_DATA",0); 
    define("SESSION_VAR", 1);
    define("MAX_NAME_LENGTH", 40);
    define("ID_LENGTH", 9);
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
                const MAX_NAME_LENGTH = 40;
                const ID_LENGTH = 9;
                const MIN_PASSWORD_LENGTH = 5;

                function validateSignup(form){ 
                    fail = validateName(form.register_name.value);
                    fail += validateID(form.register_id.value);
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
                    fail = validateID(form.id_login.value);
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
                        return "Name can only be max of 40 characters. ";
                    }
                    else if (/[^a-zA-Z]/.test(field)){
                        return "Name can only have letters. ";
                    }
                    return "";
                }

                function validateID(field){
                    if (field == ""){
                        return "Please enter an ID. ";
                    }
                    else if (field.length != ID_LENGTH){
                        return "ID must have 9 digits. ";
                    }
                    else if (/[^0-9]/.test(field)){
                        return "Only numbers are allowed. ";
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

            <label for ="register_id">Student ID:</label><br>
            <input type="text" id="register_id" name="register_id"><br><br>

            <label for ="register_email">Email:</label><br>
            <input type="text" id="register_email" name="register_email"><br><br>
            
            <label for ="register_password">Password:</label><br>
            <input type="password" id="register_password" name="register_password"><br><br>
            <button>Sign Up</button>
        </form>

        <h2>Login</h2>
        <form method = "post" action="main.php" onsubmit="return validateLogin(this)">
            <label for ="id_login">Student ID:</label><br>
            <input type="text" id="id_login" name="id_login"><br><br>

            <label for ="password_login">Password:</label><br>
            <input type="password" id="password_login" name="password_login"><br><br>
            <button>Login</button>
        </form>
        
        </body></html>
    _END;
    }
    
    //sign up
    if(!empty($_POST['register_name']) && !empty($_POST['register_id']) && !empty($_POST['register_email']) && !empty($_POST['register_password'])){
        //sanitize inputs
        $student_id = mysql_entities_fix_string($conn,$_POST['register_id']);
        $name = mysql_entities_fix_string($conn,$_POST['register_name']);
        $email = mysql_entities_fix_string($conn,$_POST['register_email']);
        $password = mysql_entities_fix_string($conn,$_POST['register_password']);

        $fail = validate_id($student_id);
        $fail .= validate_name($name);
        $fail .= validate_email($email);
        $fail .= validate_password($password);

        if($fail == ""){
            //check if ID already exists
            checkUniqueID($student_id, $conn);

            //check if email already exists
            checkUniqueEmail($email,$conn);

            //hash the password
            $password = password_hash($password, PASSWORD_DEFAULT);

            //add user to database
            addUser($student_id, $name, $email, $password, $conn);

            echo "You may now log in with your credentials.";
        }
        else{
            exit;
        }
    }

    //login
    if(!empty($_POST['id_login']) && !empty($_POST['password_login'])){
        //sanitize inputs
        $student_id = mysql_entities_fix_string($conn,$_POST['id_login']);
        $password = mysql_entities_fix_string($conn,$_POST['password_login']);

        $fail = validate_id($student_id);
        $fail .= validate_password($password);

        if ($fail == ""){
            //query credentials
            $query = "SELECT * FROM credentials WHERE student_id ='$student_id'";
            $result = $conn->query($query);

            if(!$result || mysqli_num_rows($result) == NO_DATA){
            die (printError());  
            } 
            elseif ($result->num_rows){
                $row = $result->fetch_array(MYSQLI_NUM);
                $result->close();
                $stored_uid = $row[0];
                $stored_student_id = $row[1];
                $stored_name = $row[2];
                $stored_token = $row[4];

                //check if credentials are correct
                if($student_id == $stored_student_id && password_verify($password, $stored_token)){
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
                    header("Location: /cs174/hw6/home.php");
                    
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

    //check that the student id does not already exist in the database
    function checkUniqueID($student_id, $conn){
        $query = "SELECT student_id FROM credentials WHERE student_id = '$student_id'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        //if result returns more than 0 rows, then student id exists
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
    function addUser($student_id, $name, $email, $password, $conn){
        $query = "INSERT INTO credentials (student_id, name, email, token) VALUES('$student_id','$name','$email','$password')";
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
            return "Name can only be max of 40 characters.<br>";
        }
        elseif(preg_match("/[^a-zA-Z]/", $field)){
            return "Only letters allowed for name.<br>";
        }
        return "";
        
    }

    //validate student id
    function validate_id($field){
        if ($field == ""){
            return "Please enter an ID.<br>";
        }
        elseif (strlen($field) != ID_LENGTH){
            return "ID must be 9 digits.<br>";
        }
        elseif(preg_match("/[^0-9]/", $field)){
            return "Only numbers allowed for ID.<br>";
        }
        return ""; 
    }

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
