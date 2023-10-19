<?php
    //questions
    //how to test for connection error?
    //do we need to make a class?
    //fix_String function
    //can we use file_get_contents?
    //where to close result?

    require_once 'login.php';

    define("ERROR_MSG", "Please try again.");
    define("COL_SIZE",2);

    $conn = new mysqli($hn,$un,$pw,$db);

    if($conn->connect_error) die (ERROR_MSG);


    echo <<<_END
    <html>
    <head><title>PHP Form Upload</title></head><body>
    <h1>Homework 4</h1>
    <form method = "post" action="hw4.php" enctype="multipart/form-data">
        <label for ="title">Title:</label>
        <input type="text" id="title" name="title"><br><br>
        <label for="file"> Select File: </label><br>
        <input id="file" type="file" name="filename" size="10"><br><br>
        <button>Submit</button>
    </form>
    _END;

    $query = "CREATE TABLE IF NOT EXISTS hw4 (
        title VARCHAR(24),
        content VARCHAR(2000)
        )";
    $result = $conn->query($query);

    //array to hold the lines read from the file
    $file_lines = array();

    if ($_FILES){
        $file_line = "";

        //sanitize and read the uploaded file
        $temp = htmlentities($_FILES['filename']['tmp_name']);
        $file = fopen($temp,'r');
        //check if file was able to open
        if($file){
            //get lines of the file while it is not the end of the file
            while(!feof($file)){
                $line = fgets($file);
                if($line !== false){
                    //sanitize the line
                    $line = htmlentities($line, ENT_QUOTES, 'UTF-8');
                    
                    $file_line .= $line;
                }
            }
            //close the file
            fclose($file);

            $title = mysql_entities_fix_string($conn,$_POST['title']);

            //Check empty title
            if(!$title){
                echo "Title is empty";
            }
            // else{
            //     storeData($title,$file_lines,$conn);
            // }

            printOutTable($conn);

        }
        //print out error message if file was unable to open
        else {
            echo "File was unable to open.";
        }
    }
    echo "</body></html>";

    function mysql_entities_fix_string($conn,$string){
        return htmlentities(mysql_fix_string($conn,$string));
    }

    function mysql_fix_string($conn,$string){
        //if(get_magic_quotes_gpc()){                   //this is no longer available in php ver 8
            $string = stripslashes($string);
        //}
        return $conn->real_escape_string($string);
    }

    function storeData($title,$file_lines,$conn){
        $query = "INSERT INTO hw4 VALUES('$title','$file_lines')";
        $result = $conn->query($query);
        if(!$result) die (ERROR_MSG);
    }

    function printOutTable($conn){
        $query = "SELECT * FROM hw4";
        $result = $conn->query($query);

        $rows = $result->num_rows;
        echo "<table><tr><th>Title</th><th>Content</th></tr>";
        for ($j = 0 ; $j < $rows ; ++$j)
        {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            echo "<tr>";
            for ($k = 0 ; $k < COL_SIZE ; ++$k) echo "<td><br>$row[$k]</td>";
            echo "</tr>";
        }
        echo "</table>";

        //close connection
        $conn->close();

    }

    //close connection
    //$conn->close();
?>
