<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 *  to test run C:\xampp\php\php -f "C:\xampp\htdocs\Eve_industry_data\updateBD.php"
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
    
    //Quick query
    $val = mysql_query('select 1 from jitamarket LIMIT 1');

    //Checking if the table exists and then creating it if it doesn't
    if ($val !== FALSE) {
        echo "Table exists" . PHP_EOL;
    } else {
        $sql = $conn->prepare("CREATE TABLE jitamarket (
        typeID BIGINT PRIMARY KEY,
        volume DOUBLE,
        max DECIMAL(19,2),
        min DECIMAL(19,2)
        )");
        $sql->execute();
        echo "Table created successfully" . PHP_EOL;
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
try {
    
    //Selecting all itemID's that are buyable on the market and are not BP's or skils
    $query = $conn->prepare("SELECT typeID FROM `invtypes` WHERE invtypes.groupID NOT IN (SELECT groupID FROM `invgroups` WHERE categoryID IN (9, 16)) AND `marketGroupID` IS NOT NULL");
    $query->execute();

    $item_ids = $query->fetchAll();

    echo 'Items retrieved from DB: ' . count($item_ids) . PHP_EOL;
} catch (PDOException $e) {
    echo "Connection failed to invtypes: " . $e->getMessage();
}

foreach ($item_ids as $value) {
    set_time_limit(30);

    $item_id = intval($value['typeID']);

    echo 'Quering market data for item: ' . $item_id . PHP_EOL;

    $curl_handle = curl_init('http://api.eve-central.com/api/marketstat?typeid=' . $item_id . '&usesystem=30000142');
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
    $http = curl_exec($curl_handle);

    curl_close($curl_handle);

    echo 'Data returned. ' . PHP_EOL;

    $xml = simplexml_load_string($http);

    //add the data returned to the BD
    if ($xml !== null) {
        $buy = (string) $xml->marketstat->type->buy->max; // max buy price
        $sell = (string) $xml->marketstat->type->sell->min;
        $buy_vol = (string) $xml->marketstat->type->buy->volume;

        try {
            $row_exists = $conn->query("SELECT * FROM `jitamarket` WHERE typeID=" . $item_id);
            if ($row_exists) {
                $sql = $conn->prepare("UPDATE jitamarket SET volume='$buy_vol', max='$buy', min='$sell'");
                $sql->execute();
                echo "Data updated for " . $item_id . PHP_EOL;
            } else {
                $sql = $conn->prepare("INSERT INTO jitamarket (typeID, volume, max, min) "
                        . "VALUES ('$item_id', '$buy_vol', '$buy', '$sell')");
                $sql->execute();
                echo "Data entered for " . $item_id . PHP_EOL;
            }
        } catch (PDOException $e) {
            echo "Cannot add entry: " . $e->getMessage();
        }
    }

}
?>