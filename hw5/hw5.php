<?php
    require_once 'login.php';

    define("COL_SIZE",1); 
    define("NO_DATA",0); 
    define('WEEK_IN_SEC', 60 * 60 * 24 * 7);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    //sign up and login forms
    if(!isset($_COOKIE['name'])){
        echo <<<_END
        <html>
        <head><title>Homework 5</title></head><body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <h1>Hello!</h1>
        <form method = "post" action="hw5.php">
            <label for ="register_name">Name:</label><br>
            <input type="text" id="register_name" name="register_name"><br><br>
            <label for ="register_username">Username:</label><br>
            <input type="text" id="register_username" name="register_username"><br><br>
            <label for ="register_password">Password:</label><br>
            <input type="text" id="register_password" name="register_password"><br><br>
            <button>Sign Up</button>
        </form>

        <form method = "post" action="hw5.php">
            <label for ="username_login">Username:</label><br>
            <input type="text" id="username_login" name="username_login"><br><br>
            <label for ="password_login">Password:</label><br>
            <input type="text" id="password_login" name="password_login"><br><br>
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
                //set cookie for name of the user
                storeNameInCookie($stored_name);

                //set cookie for user id
                storeUserIdInCookie($user_id);

                //refresh page so it can render logged in page
                header("Location: " . $_SERVER['REQUEST_URI']);
                
            }
            else{
                die("Invalid username/password combination");
            }
        }

        //close result
        //$result->close();
    }

    //logged in
    if(isset($_COOKIE['name'])){
        if(isset($_COOKIE['user_id'])){
            //sanitize the cookies
            $name = mysql_entities_fix_string($conn, $_COOKIE['name']);
            $user_id = mysql_entities_fix_string($conn, $_COOKIE['user_id']);

            echo <<<_END
            <html>
            <body>
            <link rel="stylesheet" type="text/css" href="style.css">
            <title>Homework 5</title></head>
            <h1>Hello $name! </h1>
            <form method = "post" action="hw5.php">
                <label for ="comment">Comment:</label><br>
                <input type="text" id="comment" name="comment"><br><br>
                <button>Add Comment</button>
            </form>
            _END;
        
            echo "</body></html>";

            //check if user has entered a comment
            if (!empty($_POST['comment'])) {
                //sanitize comment
                $comment = mysql_entities_fix_string($conn,$_POST['comment']);

                //add comment to database
                addComment($user_id,$comment,$conn);
            }

            //print comments made by user
            printComments($user_id,$conn);
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
        if($num_rows > 0){
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

    //add comment to database
    function addComment($user_id, $comment, $conn){
        $query = "INSERT INTO comments (uid,comment) VALUES('$user_id','$comment')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
    }

    //function to store name in cookie
    function storeNameInCookie($name){
        setcookie('name', $name, time() + WEEK_IN_SEC, '/');
    }

    //function to store username in cookie
    function storeUserIdInCookie($user_id){
        setcookie('user_id', $user_id, time() + WEEK_IN_SEC, '/');
    }

    //prints out the data tabale
    function printComments($user_id,$conn){
        $query = "SELECT comment FROM comments WHERE uid ='$user_id'";
        $result = $conn->query($query);

        $rows = $result->num_rows;
        echo "<table>
                <tr>
                    <th>Comments</th>
                </tr>";
        for ($j = 0 ; $j < $rows ; ++$j)
        {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            echo "<tr>";
            for ($k = 0 ; $k < COL_SIZE ; ++$k) 
            echo "<td><br>$row[$k]</td>";
            echo "</tr>";
        }
        echo "</table>";

        //close result
        $result->close();

        //close connection
        $conn->close();

    }

?>
