<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 *  <table style="width:100%">
  <tr>
    <td>Jill</td>
    <td>Smith</td>
    <td>50</td>
  </tr>
  <tr>
    <td>Eve</td>
    <td>Jackson</td>
    <td>94</td>
  </tr>
</table> 
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
   $query = $conn->prepare(
           "SELECT i.typeID, i.typeName, j.typeID, j.sellVolume, j.buyVolume, j.max, j.min, j.profit "
           . "FROM invtypes AS i "
           . "INNER JOIN jitamarket AS J ON j.typeID = i.typeID "
           . "WHERE j.buyVolume > 500.00 AND j.sellVolume > 500.00 "
           . "ORDER BY j.profit DESC Limit 0,30");
    $query->execute();

    $profitable_items = $query->fetchAll();
    echo '<table>
    <tr>
    <td>Item Name</td>
    <td>Sell Volume</td>
    <td>Buy Volume</td>
    <td>Max Buy price</td>
    <td>Min Sell price</td>
    <td>Total Potential Profit</td>
  </tr>';
    foreach ($profitable_items as $item) {
        echo "<tr>"
        . "<td>{$item['typeName']}</td>";
        echo "<td>{$item['sellVolume']}</td>";
        echo "<td>{$item['buyVolume']}</td>";
        echo "<td>{$item['max']}</td>";
        echo "<td>{$item['min']}</td>";
        echo "<td>{$item['profit']}</td>"
        . "</tr>";
    }
    echo "</table>";
    
?>