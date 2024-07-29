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
try {
    $db->beginTransaction();
    
    // Delete related authors
    $authQuery = "DELETE FROM `IT202-S24-AUTHORS` WHERE book_id = :id";
    $authStmt = $db->prepare($authQuery);
    $authStmt->execute([":id" => $id]);

    // Delete related categories
    $catsQuery = "DELETE FROM `IT202-S24-CATEGORIES` WHERE book_id = :id";
    $catsStmt = $db->prepare($catsQuery);
    $catsStmt->execute([":id" => $id]);

    // Delete the book
    $query = "DELETE FROM `IT202-S24-BOOKS` WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Deleted record with id $id", "success");
    } else {
        flash("No record found with id $id", "warning");
    }

    $db->commit();
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Error deleting book $id: " . var_export($e, true));
    flash("Error deleting record", "danger");
}

die(header("Location: " . get_url("admin/list_books.php")));
?>
