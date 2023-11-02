<?php
    //questions:
    //do i need to sanitize $_SERVER?

    require_once 'login.php';

    define("COL_SIZE",1); 
    define('WEEK_IN_SEC', 60*60*24*7);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    if(!isset($_COOKIE['name'])){
        echo <<<_END
        <html>
        <head><title>Homework 5</title></head><body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <h1>Hello!</h1>
        <form method = "post" action="hw5.php">
            <label for ="name">Name:</label>
            <input type="text" id="name" name="name"><br><br>
            <label for ="username">Username:</label>
            <input type="text" id="username" name="username"><br><br>
            <label for ="password">Password:</label>
            <input type="text" id="password" name="password"><br><br>
            <button>Sign Up</button>
        </form>

        <form method = "post" action="hw5.php">
            <label for ="username_login">Username:</label>
            <input type="text" id="username_login" name="username_login"><br><br>
            <label for ="password_login">Password:</label>
            <input type="text" id="password_login" name="password_login"><br><br>
            <button>Login</button>
        </form>
        _END;

        echo "</body></html>";
    }
    
    //sign up
    if(!empty($_POST['name']) && !empty($_POST['username']) && !empty($_POST['password'])){
        //sanitize inputs
        $name = mysql_entities_fix_string($conn,$_POST['name']);
        $username = mysql_entities_fix_string($conn,$_POST['username']);
        $password = mysql_entities_fix_string($conn,$_POST['password']);

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
        $query = "SELECT * FROM credentials WHERE username ='$username'";
        $result = $conn->query($query);

        if(!$result){
          die (printError());  
        } 
        elseif ($result->num_rows){
            $row = $result->fetch_array(MYSQLI_NUM);
            $result->close();
            $stored_name = $row[1];
            $stored_username = $row[2];
            $stored_token = $row[3];

            //check if credentials are correct
            if($username == $stored_username && password_verify($password, $stored_token)){
                //set cookie
                storeNameInCookie($stored_name);

                //refresh page so it can render logged in page
                header("Location: " . $_SERVER['REQUEST_URI']);
                
            }
            else{
                die("Invalid username/password combination");
            }
        } 

    }

    if(isset($_COOKIE['name'])){
        $name = mysql_entities_fix_string($conn, $_COOKIE['name']);
        echo <<<_END
        <html>
        <body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <h1>Hello $name! </h1>
        <form method = "post" action="hw5.php">
            <label for ="comment">Comment:</label>
            <input type="text" id="comment" name="comment">
            <button>Add Comment</button>
        </form>
        _END;
    
        echo "</body></html>";

        if (!empty($_POST['comment'])) {
            //sanitize comment
            $comment = mysql_entities_fix_string($conn,$_POST['comment']);

            //add comment to database
            addComment($name,$comment,$conn);
        }

        printComments($name,$conn);

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
    function addComment($name, $comment, $conn){
        $query = "INSERT INTO comments (name,comment) VALUES('$name','$comment')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
        else{
            echo "Insert success!";
        }
    }

    //function to store name in cookie
    function storeNameInCookie($name){
        setcookie('name', $name, time() + WEEK_IN_SEC, '/');
    }

    //prints out the data tabale
    function printComments($name,$conn){
        $query = "SELECT comment FROM comments WHERE name ='$name'";
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
            for ($k = 0 ; $k < COL_SIZE ; ++$k) echo "<td><br>$row[$k]</td>";
            echo "</tr>";
        }
        echo "</table>";

        //close result
        $result->close();

        //close connection
        $conn->close();

    }

?>
