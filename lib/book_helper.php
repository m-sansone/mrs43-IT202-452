<?php
function recalculate_book($book_id)
{
    // Fetch the latest details about the book
    $query = "SELECT 
                    b.id, 
                    b.title, 
                    b.language, 
                    b.page_count, 
                    b.cover_art_url, 
                    ub.user_id,
                    (SELECT AVG(page_count) FROM `IT202-S24-BOOKS` AS hist WHERE hist.language = b.language) AS `average_page_count`
              FROM 
                    `IT202-S24-BOOKS` b
              INNER JOIN 
                    `IT202-S24-UserBooks` ub 
              ON 
                    b.id = ub.book_id 
              WHERE 
                    b.id = :book_id";
    
    $db = getDB();
    $book = [];
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":book_id" => $book_id]);
        $r = $stmt->fetch();
        if ($r) {
            $book = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching book details: " . var_export($e, true));
        flash("Error fetching book data", "danger");
    }
    
    if ($book) {
        error_log("Processing Book Data");

        // Fetch additional details if needed
        $query = "SELECT title, page_count, language, created, modified FROM `IT202-S24-BOOKS` WHERE id = :id";
        $book_details = ["title" => "temp"];
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([":id" => $book_id]);
            $r = $stmt->fetch();
            if ($r) {
                $book_details = $r;
            }
        } catch (PDOException $e) {
            error_log("Error fetching book record: " . var_export($e, true));
            flash("Error fetching book record", "danger");
        }

        // Process and calculate new book stats
        $book_details["average_page_count"] = $book["average_page_count"];

        // Update the book record with new stats
        $query = "UPDATE `IT202-S24-BOOKS` 
                  SET title = :title, page_count = :page_count, language = :language 
                  WHERE id = :id";
        try {
            $stmt = $db->prepare($query);
            $params = [
                ":title" => $book_details["title"],
                ":page_count" => $book_details["page_count"],
                ":language" => $book_details["language"],
                ":id" => $book_id
            ];
            $stmt->execute($params);
            flash("Updated book: " . var_export($book_details, true));
        } catch (PDOException $e) {
            error_log("Error updating book " . var_export($e, true));
        }

        // Update the author table
        $query = "UPDATE `IT202-S24-Authors` 
                  SET last_updated = CURRENT_TIMESTAMP 
                  WHERE id IN (SELECT author_id FROM `IT202-S24-BookAuthors` WHERE book_id = :book_id)";
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([":book_id" => $book_id]);
            flash("Updated author information for book ID: " . $book_id);
        } catch (PDOException $e) {
            error_log("Error updating author " . var_export($e, true));
        }

        // Update the category table
        $query = "UPDATE `IT202-S24-Categories` 
                  SET last_updated = CURRENT_TIMESTAMP 
                  WHERE id IN (SELECT category_id FROM `IT202-S24-BookCategories` WHERE book_id = :book_id)";
        try {
            $stmt = $db->prepare($query);
            $stmt->execute([":book_id" => $book_id]);
            flash("Updated category information for book ID: " . $book_id);
        } catch (PDOException $e) {
            error_log("Error updating category " . var_export($e, true));
        }
    }
}
