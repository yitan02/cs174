<?php
    //questions:
    //how to check for unique salt with password_hash?
    //do we need to account for collision with password_hash?
    //how to sanitize cookie?

    require_once 'login.php';

    define("COL_SIZE",1); 
    define('WEEK_IN_SEC', 60*60*24*7);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

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

    //sign up
    if(!empty($_POST['name']) && !empty($_POST['username']) && !empty($_POST['password'])){
        //sanitize inputs
        $name = mysql_entities_fix_string($conn,$_POST['name']);
        $username = mysql_entities_fix_string($conn,$_POST['username']);
        $password = mysql_entities_fix_string($conn,$_POST['password']);

        echo $name . "<br>";
        echo $username. "<br>";
        echo $password . "<br>";

        //store name in cookie
        storeNameInCookie($name);

        //hash the password
        $password = password_hash($password, PASSWORD_DEFAULT);

        //check if username already exists
        checkUniqueUsername($username, $conn);

        //add user to database
        addUser($username, $password, $conn);
    }
    // else{
    //     die("Please fill in all fields.");
    // }

    //login
    //can i use post instead of server?
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
            $stored_username = $row[1];
            $stored_token = $row[2];

            //check if credentials are correct
            if($username == $stored_username && password_verify($password, $stored_token)){
                if(isset($_COOKIE['name'])){
                    $name = mysql_entities_fix_string($conn, $_COOKIE['name']);
                    echo "Hello " . $name . ", you are logged in!";
                }
            }
            else{
                die("Invalid username/password combination");
            }
        } 

    }
    // else{
    //     header('WWW-Authenticate: Basic realm="Restricted Section“');
    //     header('HTTP/1.0 401 Unauthorized');
    //     die ("Please enter your username and password");
    // }


    //print data tabale
    //printTable($conn);

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

    function checkUniqueUsername($username, $conn){
        $query = "SELECT username FROM credentials WHERE username = '$username'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        echo "num of existing name:" . $num_rows . "<br>";

        if($num_rows > 0){
            die("Username already exists.");
        }

    }

    //insert title and file lines to the database
    function addUser($username, $password, $conn){
        $query = "INSERT INTO credentials (username,token) VALUES('$username','$password')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
        else{
            echo "Insert succuess!";
        }
    }

    function addComment($username, $comment, $conn){
        $query = "INSERT INTO comments (username,comment) VALUES('$username','$comment')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
        else{
            echo "Insert succuess!";
        }
    }

    function storeNameInCookie($name){
        setcookie('name', $name, time() + WEEK_IN_SEC, '/');
    }

    //prints out the data tabale
    function printTable($username,$conn){
        $query = "SELECT comment FROM comments WHERE username ='$username'";
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
