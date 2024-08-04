<?php
function recalculate_book($book_id)
{
    $db = getDB();

    // Fetch the latest details about the book
    $query = "SELECT 
                    b.id, 
                    b.title, 
                    b.language, 
                    b.page_count, 
                    b.cover_art_url, 
                    (SELECT AVG(page_count) FROM `IT202-S24-BOOKS` AS hist WHERE hist.language = b.language) AS `average_page_count`
              FROM 
                    `IT202-S24-BOOKS` b
              WHERE 
                    b.id = :book_id";
    
    $book = [];
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":book_id" => $book_id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($r) {
            $book = $r;
        } else {
            throw new Exception("No book found with id: $book_id");
        }
    } catch (PDOException $e) {
        error_log("Error fetching book details: " . $e->getMessage());
        flash("Error fetching book data", "danger");
        return;
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        flash("No book found with id: " . $book_id, "warning");
        return;
    }
    
    if ($book) {
        // Update the book record with new stats
        $query = "UPDATE `IT202-S24-BOOKS` 
                  SET title = :title, page_count = :page_count, language = :language, average_page_count = :average_page_count
                  WHERE id = :id";
        try {
            $stmt = $db->prepare($query);
            $params = [
                ":title" => $book["title"],
                ":page_count" => $book["page_count"],
                ":language" => $book["language"],
                ":average_page_count" => $book["average_page_count"],
                ":id" => $book_id
            ];
            $stmt->execute($params);
            flash("Updated book: " . var_export($book, true));
        } catch (PDOException $e) {
            error_log("Error updating book: " . $e->getMessage());
            flash("Error updating book data", "danger");
        }
    }
}
