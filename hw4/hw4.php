<?php
    require_once 'login.php';

    define("COL_SIZE",2); 

    //create new mysql connection
    $conn = new mysqli($hn,$un,$pw,$db);

    //check if error connection 
    if($conn->connect_error) die (printError("Please try again."));

    echo <<<_END
    <html>
    <head><title>PHP Form Upload</title></head><body>
    <link rel="stylesheet" type="text/css" href="style.css">
    <h1>Homework 4</h1>
    <form method = "post" action="hw4.php" enctype="multipart/form-data">
        <label for ="title">Title:</label>
        <input type="text" id="title" name="title"><br><br>
        <label for="file"> Select File: </label><br>
        <input id="file" type="file" name="filename" size="10"><br><br>
        <button>Submit</button>
    </form>
    _END;

    if ($_FILES){
        $sanitized_lines = "";

        //sanitize and read the uploaded file
        $temp = htmlentities($_FILES['filename']['tmp_name']);

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
                            $line = htmlentities($line, ENT_QUOTES, 'UTF-8');
                            //append sanitized line to file line
                            $sanitized_lines .= $line;
                        }
                    }

                    //close the file
                    fclose($file);

                    //get user input title and sanitize it
                    $title = mysql_entities_fix_string($conn,$_POST['title']); 

                    //Check if empty title
                    if($title == ""){
                        printError("Title is empty. Please enter a title.<br>");
                    }
                    else{
                        //store data if title is not empty
                        storeData($title, $sanitized_lines, $conn);
                    }

                }
                else{
                    printError("File is not a text/plain file.");
                }
            }
            else{
                printError("File is empty.");
            }

        }
        //print out error message if file was unable to open
        else {
            printError("File was unable to open.");
        }
    }
    echo "</body></html>";

    //print data tabale
    printTable($conn);

    //for printing error messages
    function printError($msg){
        echo $msg;
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

    //insert title and file lines to the database
    function storeData($title,$file_lines,$conn){
        $query = "INSERT INTO hw4 (title,content) VALUES('$title','$file_lines')";
        $result = $conn->query($query);

        //check if query failed
        if(!$result){
            printError("Please try again.");
        }
        else{
            echo "Insert succuess!";
        }
    }

    //prints out the data tabale
    function printTable($conn){
        $query = "SELECT * FROM hw4";
        $result = $conn->query($query);

        $rows = $result->num_rows;
        echo "<table>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
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
