<?php
$a1 = [
    ["id" => 1, "make" => "Toyota", "model" => "Camry", "year" => 2010],
    ["id" => 2, "make" => "Honda", "model" => "Civic", "year" => 2005]
];

$a2 = [
    ["id" => 3, "make" => "Ford", "model" => "Mustang", "year" => 1995],
    ["id" => 4, "make" => "Chevrolet", "model" => "Impala", "year" => 2000]
];

$a3 = [
    ["id" => 5, "make" => "Nissan", "model" => "Altima", "year" => 2015],
    ["id" => 6, "make" => "BMW", "model" => "3 Series", "year" => 2018]
];

$a4 = [
    ["id" => 7, "make" => "Mercedes", "model" => "C Class", "year" => 2011],
    ["id" => 8, "make" => "Audi", "model" => "A4", "year" => 1990]
];

function processCars($cars) {
    echo "<br>Processing Array:<br><pre>" . var_export($cars, true) . "</pre>";
    echo "<br>New Properties Output:<br>";
    
    // Note: use the $cars variable to iterate over, don't directly touch $a1-$a4
    // TODO add logic here to create a new array with original properties plus age and isClassic
    $currentYear = null; // determine current year
    $processedCars = []; // result array
    $classic_age = 25; // don't change this value
    // Start edits
   
    // End edits
    echo "<pre>" . var_export($processedCars, true) . "</pre>";
    
}

echo "Problem 2: Getting Classy<br>";
?>
<table>
    <thead>
        <tr>
            <th>A1</th>
            <th>A2</th>
            <th>A3</th>
            <th>A4</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <?php processCars($a1); ?>
            </td>
            <td>
                <?php processCars($a2); ?>
            </td>
            <td>
                <?php processCars($a3); ?>
            </td>
            <td>
                <?php processCars($a4); ?>
            </td>
        </tr>
    </tbody>
</table>
<style>
    table {
        border-spacing: 2em 3em;
        border-collapse: separate;
    }

    td {
        border-right: solid 1px black;
        border-left: solid 1px black;
    }
</style>