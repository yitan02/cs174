<?php
    session_start();
    require_once 'login.php';

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
        <link rel="stylesheet" type="text/css" href="style.css">
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

        <div>
            <h2>Get a random question!</h2>
            <form method = "post" action="home.php">
                <button name="get-question">Get Question</button>
            </form>
        </div>

        <br>

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

                                    //check for duplicate questions
                                    if(isDuplicate($id, $line, $conn)){
                                        echo $line . " is already in database and will not be added.<br>";
                                    }
                                    else{
                                        addQuestion($id, $line, $conn);
                                        echo $line . " added to database.<br>";
                                    }
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

        echo "</body></html>";

        //get random question when button is clicked
        if(isset($_POST['get-question'])){
            getQuestion($id, $conn);
        }

        //close connection
        $conn->close();
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
    function isDuplicate($id, $question, $conn){
        $query = "SELECT question FROM questions WHERE question = '$question' AND uid = '$id'";
        $result = $conn->query($query);
        $num_rows = mysqli_num_rows($result);

        //close result
        $result->close();

        //if result returns more than 0 rows, then question exists
        if($num_rows > NO_DATA){
            return true;
        }
        else{
            return false;
        }

    }

    //add question to database
    function addQuestion($id, $question, $conn){
        $query = "INSERT INTO questions (uid, question) VALUES('$id','$question')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError();
        }
    }

    //get a random question
    function getQuestion($id,$conn){
        $query = "SELECT question FROM questions WHERE uid = '$id' ORDER BY RAND() LIMIT 1";
        $result = $conn->query($query);

        if ($result && $result->num_rows > NO_DATA){
            $row = $result->fetch_assoc();
            $random_question = $row['question'];

            echo $random_question;
        }
        else{
            echo "No question found.";
        }

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
