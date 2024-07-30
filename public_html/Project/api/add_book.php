<?php
require(__DIR__ . "/../../../lib/functions.php");
session_start();
if(isset($_GET["book_id"]) && is_logged_in()){
    $db = getDB();
    $query = "INSERT INTO `IT202-S24-UserBooks` (`user_id`, `book_id`) VALUES (:user_id, :book_id)";
    try{
        $stmt = $db->prepare($query);
        $stmt->execute([":user_id" => get_user_id(), ":book_id" => $_GET["book_id"]]);
        flash("Successfully added to library", "success");
    } catch(PDOException $e){
        if($e->errorInfo[1] === 1062){
            flash("This book isn't available", "danger");
        } else{
            flash("Unhandled error occurred", "danger");
        }
        error_log("Error adding book: " . var_export($e, true));
    }
}

redirect("books.php");
