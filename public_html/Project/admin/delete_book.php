<?php

session_start();
require(__DIR__ . "/../../../lib/functions.php");
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$id = se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid id passed to delete", "danger");
    die(header("Location: " . get_url("admin/list_books.php")));
}

$db = getDB();
$query = "DELETE FROM `IT202_S24_BOOKS` WHERE id = :id";
try {
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);
    if($stmt->rowCount() > 0){
        flash("Deleted record with id $id", "success");
    } else{
        flash("No record found with id $id", "warning");
    }
    
} catch (Exception $e) {
    error_log("Error deleting book $id" . var_export($e, true));
    flash("Error deleting record", "danger");
}
die(header("Location: " . get_url("admin/list_books.php")));