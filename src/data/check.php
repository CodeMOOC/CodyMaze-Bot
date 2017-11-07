<?php
require ("../config.php");

if( $_GET["uuid"]) {
    $uuid = $_GET["uuid"];

    $error_result = 'false';
    $success_result = 'true';

    try {
        // connect to the mysql database
        $link = mysqli_connect(DATABASE_HOST, DATABASE_USERNAME, DATABASE_PASSWORD, DATABASE_NAME);

        $sql = "SELECT * FROM certificates_list WHERE certificate_id LIKE '%" . $uuid . "%'";

        // excecute SQL statement
        $result = mysqli_query($link, $sql);
        $rows = mysqli_num_rows($result);

        // Close connection
        mysqli_close($link);

        // die if SQL statement failed
        if ($rows == false || $rows < 1) {
            http_response_code(404);
            echo json_encode($error_result);
        } else {
            echo json_encode($success_result);
        }

    } catch (Exception $e) {
        Logger::debug("error executing query: " . $e);
        echo json_encode($error_result);
    }
}
