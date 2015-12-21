<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 * localhost/Eve_industry_data/display_most_profitable.php
 */
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test";


try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully" . PHP_EOL;

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
   $query = $conn->prepare("SELECT i.typeID, i.typeName, j.typeID, j.volume, j.max, j.min, j.profit FROM invtypes AS i INNER JOIN jitamarket AS J ON j.typeID = i.typeID WHERE j.volume > 500.00 ORDER BY j.profit DESC Limit 0,30");
    $query->execute();

    $profitable_items = $query->fetchAll();

    print_r($profitable_items);
?>