<?php
    session_start();
    require_once 'login.php';

    define("COL_SIZE", 3); 
    define('WEEK_IN_SEC', 60 * 60 * 24 * 7);
    define("NO_DATA",0);

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    //check if there's a session
    if(isset($_SESSION['user_id'])){
        $id = mysql_entities_fix_string($conn, $_SESSION['user_id']);
        $name = mysql_entities_fix_string($conn, $_SESSION['name']);

        echo <<<_END
        <html>
        <head>
            <title>Home</title>
        </head>
        <body>
        <h1>Hello $name! </h1>
        <form method="post" action="">
            <input type="submit" name="logout" value="Logout">
        </form>

        <h2>Find your advisor:</h2>
        <div class="upload-form">
            <form method = "post" action="home.php">
                <label for ="name">Name:</label>
                <input type="text" id="name" name="name"><br><br>

                <label for ="id">Student ID:</label>
                <input type="text" id="id" name="id"><br><br>

                <button>Submit</button>
            </form>
        </div>

        _END;

        if(!empty($_POST['id']) && !empty($_POST['name'])){
            $input_id = mysql_entities_fix_string($conn, $_POST['id']);
            $input_name = mysql_entities_fix_string($conn, $_POST['name']);

            $query = "SELECT * FROM credentials WHERE student_id ='$input_id'";
            $result = $conn->query($query);

            if(!$result || mysqli_num_rows($result) == NO_DATA){
                printError();  
            } 
            elseif ($result->num_rows){
                $row = $result->fetch_array(MYSQLI_NUM);
                $result->close();
                $stored_student_id = $row[1];
                $stored_name = $row[2];

                //to prevent user from looking up other student's advisor other than their own
                if(($stored_student_id == $input_id) && ($stored_name == $input_name)){
                    $input_id = substr($input_id, strlen($input_id) - 2 ,strlen($input_id));
                    getAdvisor($input_id, $conn);
                }
                else{
                    printError();
                }
            }
        }

        //close connection
        $conn->close();

        echo "</body></html>";

    }
    else{
        echo "Please <a href='main.php'> click here</a> to log in.";
        $conn->close();
    }

    //destroy session if logout button is clicked
    if(isset($_POST['logout'])){
        destroy_session_and_data();
    }

    //generic error msg function
    function printError(){
        echo "Please try again.";
    }

    //sanitize user input
    function mysql_entities_fix_string($conn,$string){
        return htmlentities(mysql_fix_string($conn,$string));
    }

    //sanitize user input
    function mysql_fix_string($conn,$string){
        return $conn->real_escape_string($string);
    }

    //prints out the data table
    function getAdvisor($id,$conn){
        $query = "SELECT telephone, email, name FROM advisor WHERE '$id' >= lower_id AND '$id' <= upper_id";
        $result = $conn->query($query);

        $rows = $result->num_rows;
        echo "<table>
                <tr>
                    <th>Telephone</th>
                    <th>Email</th>
                    <th>Advisor Name</th>
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

    }

    //destroy session
    function destroy_session_and_data(){
        $_SESSION = array();
        setcookie(session_name(),'',time() - WEEK_IN_SEC, '/');
        session_destroy();
        header("Location: /cs174/hw6/main.php");
    }

?>
