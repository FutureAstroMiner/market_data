<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  
 * 
 * C:\xampp\php\php -f "C:\xampp\htdocs\Eve_industry_data\competition.php
 */
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test";

//if ($_GET["sellVol"] != null) {
//    $sellVol = (int) $_GET["sellVol"];}
// else {
    $sellVol = 50000;
//}
//if ($_GET["buyVol"] != null) {
//    $buyVol = (int) $_GET["sellVol"];}
// else {
    $buyVol = 50000;
//}
//if ($_GET["delta"] != null) {
//    $delta = (int) $_GET["sellVol"];}
// else {
    $delta = 1.5;
//}


try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully" . PHP_EOL;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
}
$query = $conn->prepare(
        "SELECT i.typeID, i.typeName, j.typeID, j.sellVolume, j.buyVolume, j.max, j.min, j.profit, j.delta "
        . "FROM invtypes AS i "
        . "INNER JOIN jitamarket AS J ON j.typeID = i.typeID "
        . "WHERE j.buyVolume > {$buyVol} AND j.sellVolume > {$sellVol} "
        . "AND j.delta > {$delta} "
        . "AND i.marketGroupID NOT IN (SELECT `marketGroupID` FROM `invmarketgroups` "
                . "WHERE `parentGroupID` = '19' ORDER BY `marketGroupName`) " //market group 19 is trade goods so removing it
        . "ORDER BY j.profit DESC Limit 0,30");
$query->execute();

$profitable_items = $query->fetchAll();
//echo "<pre>" . print_r($profitable_items) . "</pre>";

foreach ($profitable_items as $row) {
    $item = $row['typeID'];
    $curl_handle = curl_init('http://api.eve-central.com/api/quicklook?typeid=' . $item . '&usesystem=30000142');
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
    $http = curl_exec($curl_handle);

    curl_close($curl_handle);

    echo 'Webpage returned.' . PHP_EOL;

//    processWebpage($http);
    $xml = simplexml_load_string($http);
    echo 'Data returned ' . $xml->quicklook->sell_orders->order->count() . PHP_EOL;
    
    $numOfItems = $xml->quicklook->sell_orders->order->count();
    
    global $conn;
    try {
        $sql = $conn->query("INSERT INTO jitamarket (typeID, orders) "
                . "VALUES ('$item', '$numOfItems') "
                . "ON DUPLICATE KEY "
                . "UPDATE orders=VALUES(orders)");
        $sql->execute();

        echo "Data entered for " . $item . PHP_EOL;
    } catch (PDOException $e) {
        echo "Cannot add entry: " . $e->getMessage();
    }
}


?>