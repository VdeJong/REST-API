<?php
// localhost of
$servername = "your server";

// root
$username = "your username";

//empty
$password = "your password";

//
$dbname = "your database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
}
