<?php
    session_start();
    require_once 'login.php';

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError());

    if(isset($_SESSION['user_id'])){
        $user_id = mysql_entities_fix_string($conn, $_SESSION['user_id']);
        $name = mysql_entities_fix_string($conn, $_SESSION['name']);

        echo <<<_END
        <html>
        <body>
        <link rel="stylesheet" type="text/css" href="style.css">
        <title>Home</title></head>
        <h1>Hello $name! </h1>
        <form method="post" action="">
            <input type="submit" name="logout" value="Logout">
        </form>
        <form method = "post" action="home.php">
            <label for ="thread_name">Title:</label>
            <input type="text" id="thread_name" name="thread_name"><br><br>
            <label for="file"> Select File: </label><br>
            <input id="file" type="file" name="filename" size="10"><br><br>
            <button>Submit</button>
        </form>
        _END;

        if ($_FILES){
            $sanitized_lines = "";
    
            //sanitize and read the uploaded file
            $temp = mysql_entities_fix_string($_FILES['filename']['tmp_name']);
    
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
                                $line = mysql_entities_fix_string($line);
                                //append sanitized line to file line
                                $sanitized_lines .= $line;
                            }
                        }
    
                        //close the file
                        fclose($file);
    
                        //get user input thread name and sanitize it
                        $thread_name = mysql_entities_fix_string($conn,$_POST['thread_name']); 
    
                        //Check if empty thread name
                        if($thread_name == ""){
                            echo "Thread name is empty. Please enter a thread name.<br>";
                        }
                        else{
                            //store data if thread name is not empty
                            storeData($thread_name, $sanitized_lines, $conn);
                        }
    
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
    
        echo "</body></html>";


    }
    else{
        echo "Please <a href='main.php'> click here</a> to log in.";
    }

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

    //insert thread name and file lines to the database
    function storeData($thread_name,$file_lines,$conn){
        $query = "INSERT INTO midterm2 (thread_name,content) VALUES('$thead_name','$file_lines')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError("Please try again.");
        }
        else{
            echo "Insert succuess!";
        }
    }
?>
