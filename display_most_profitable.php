<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  
 * 
 * localhost/Eve_industry_data/display_most_profitable.php
 */
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "test";

$sellVol = (int) $_GET["sellVol"];
$buyVol = (int) $_GET["buyVol"];
$delta = (int) $_GET["delta"];


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

echo '<table>
    <tr>
    <td>Item Name</td>
    <td>Sell Volume</td>
    <td>Buy Volume</td>
    <td>Max Buy price</td>
    <td>Min Sell price</td>
    <td>Delta</td>
    <td>Total Potential Profit</td>
  </tr>';
foreach ($profitable_items as $item) {
    echo "<tr>"
    . "<td>{$item['typeName']}</td>"
    . "<td>{$item['sellVolume']}</td>"
    . "<td>{$item['buyVolume']}</td>"
    . "<td>{$item['max']}</td>"
    . "<td>{$item['min']}</td>"
    . "<td>{$item['delta']}</td>"
    . "<td>{$item['profit']}</td>"
    . "</tr>";
}
echo "</table>";
?>