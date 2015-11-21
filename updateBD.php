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
    $val = $conn->prepare('select 1 from jitamarket LIMIT 1');
    $val->execute();

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
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
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

//chunk array into smaller arrays


$chunked_array = array_chunk($item_ids, 100, TRUE);

//for each smaller array construct the url
foreach ($chunked_array as $chunk) {
//$chunk = $chunked_array[0];
    set_time_limit(30);
    $item_string = '';
    foreach ($chunk as $value) {
        $item_string .= 'typeid=' . intval($value['typeID']) . '&';
    }

    echo 'Quering market data for ' . count($chunk) . ' items' . PHP_EOL;

    $curl_handle = curl_init('http://api.eve-central.com/api/marketstat?' . $item_string . 'usesystem=30000142');
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true); // Fetch the contents too
    $http = curl_exec($curl_handle);

    curl_close($curl_handle);

    echo 'Webpage returned.' . PHP_EOL;

    $xml = simplexml_load_string($http);
    echo 'Data returned ' . $xml->children()->children()->count() . PHP_EOL;

//enter returned values into DB
    foreach ($xml->children()->children() as $item) {

        $item_id = $item->attributes();

        //add the data returned to the BD
        if ($xml !== FALSE) {
            $buy = implode($item->xpath("/buy/max"));
            $sell = implode($item->xpath("/sell/min"));
            $buy_vol = implode($item->xpath("/buy/volume"));

            try {
                $sql = $conn->query("INSERT INTO jitamarket (typeID, volume, max, min) "
                        . "VALUES ('$item_id', '$buy_vol', '$buy', '$sell') ON DUPLICATE KEY "
                        . "UPDATE volume=VALUES(volume), max=VALUES(max), min=VALUES(min)");
                $sql->execute();

                echo "Data entered for " . $item_id . PHP_EOL;
                
            } catch (PDOException $e) {
                echo "Cannot add entry: " . $e->getMessage();
            }
        }
    }
}
?>