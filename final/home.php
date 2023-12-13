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

        <div>
            <h2>Upload your questions:</h2>
            <form method = "post" action="home.php" enctype="multipart/form-data">
                <label for="file"> Select File: </label><br>
                <input id="file" type="file" name="filename"><br><br>
                <button>Submit</button>
            </form>
        </div>

        _END;
        if ($_FILES){
            $sanitized_lines = "";
    
            //sanitize the uploaded file
            $temp = mysql_entities_fix_string($conn, $_FILES['filename']['tmp_name']);

            //check if file was uploaded
            if($temp !== ""){
                //get file type
                $file_type = $_FILES['filename']['type'];
        
                $file = fopen($temp,'r');
                //check if file was able to open
                if($file){
                    //get file size
                    $file_size = $_FILES['filename']['size'];
        
                    //check if empty file
                    if($file_size != 0){
                        //check if file is a .txt file
                        if($file_type == "text/plain"){
                            //get lines of the file while it is not the end of the file
                            while(!feof($file)){
                                $line = fgets($file);
                                if($line !== false){
                                    //sanitize the line
                                    $line = mysql_entities_fix_string($conn, $line);

                                }
                            }
        
                            //close the file
                            fclose($file);
        
                        }
                        else{
                            echo "File is not a text/plain file.";
                        }
                    }
                    else{
                        echo "File is empty.";
                    }
                }
                //print out error message if file was unable to open
                else {
                    echo "File was unable to open.";
                }
            }
            else{
                echo "Please upload a file.";
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

    //check duplicated questions
    function checkDuplicate($question, $conn){
        $query = "SELECT question FROM questions WHERE question = '$question'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        //if result returns more than 0 rows, then question exists
        if($num_rows > NO_DATA){
            echo $result . " already exists and will not be added.<br>";
        }

        //close result
        $result->close();
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
        header("Location: /cs174/final/main.php");
    }

?>
