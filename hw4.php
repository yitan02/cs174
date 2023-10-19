<?php
    require_once 'login.php';
    mysqli_report(MYSQLI_REPORT_OFF);

    $conn = @new mysqli($hn,$un,$pw,$db);
    if($conn->connect_error)
        die (mysql_fatal_error("Connection failed"));

    // try {
    //     $conn = new mysqli($hn,$un,$pw,$db);
    // } catch {
    //     die (mysql_fatal_error("connection failed"));
    // }

    echo <<<_END
    <html>
    <head><title>PHP Form Upload</title></head><body>
    <h1>Homework 4</h1>
    <form method = "post" action="hw4.php" enctype="multipart/form-data">
        <label for ="title">Title:</label>
        <input type="text" id="title name="title><br><br>
        <label for="file"> Select File: </label><br>
        <input id="file" type="file" name="filename" size="10"><br><br>
        <button>Submit</button>
    </form>
    _END;

    $query = "CREATE TABLE IF NOT EXISTS cs (
        id INT NOT NULL AUTO_INCREMENT,
        title VARCHAR(32),
        content VARCHAR(1000),
        PRIMARY KEY (id)
        )";
    $result = $conn->query($query);

    // $name = "jshfdsjkh";
    // $query ="INSERT INTO test(name,age) VALUES ('$name',10)";
    // $result = $conn->query($query);

    // $query = "INSERT INTO test(name,age) VALUES('bob',20)";
    // $result = $conn->query($query);

    // $query = "INSERT INTO test(name,age) VALUES('maria',19)";
    // $result = $conn->query($query);

    // $query = "DELETE FROM test WHERE name='maria'";
    // $result = $conn->query($query);

    // $query ="SELECT * FROM test";
    // $result = $conn->query($query);

    // $rows = $result->num_rows;
    // echo "<table><tr> <th>Id</th><th>Name</th><th>Age</th></tr>";
    // for ($j = 0 ; $j < $rows ; ++$j)
    // {
    //     $result->data_seek($j);
    //     $row = $result->fetch_array(MYSQLI_NUM);
    //     echo "<tr>";
    //     for ($k = 0 ; $k < 3 ; ++$k) echo "<td>$row[$k]</td>";
    //     echo "</tr>";
    // }
    // echo "</table>";
    

    // //array to hold the lines read from the file
    // $file_lines = array();


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

            //echo $file_line;

            // $query = "INSERT INTO cs(title,content) VALUES('Hello', '$file_line')";
            // $result = $conn->query($query);

            $query = "SELECT * FROM cs";
            $result = $conn->query($query);

            $rows = $result->num_rows;
            echo "<table><tr> <th>Id</th><th>Title</th><th>Content</th></tr>";
            for ($j = 0 ; $j < $rows ; ++$j)
            {
                $result->data_seek($j);
                $row = $result->fetch_array(MYSQLI_NUM);
                echo "<tr>";
                for ($k = 0 ; $k < 3 ; ++$k) echo "<td><br>$row[$k]</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        //print out error message if file was unable to open
        else {
            echo "File was unable to open.";
        }
    }
    echo "</body></html>";

    function mysql_fatal_error($msg){
        echo $msg;
    }

    class FindPrimes{






    }




?>
