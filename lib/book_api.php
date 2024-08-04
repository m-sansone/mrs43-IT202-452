<?php
// Note: we need to go up 1 more directory
function fetch_quote($title)
{
    $result = [];

    $data = ["title" => $title, "results_per_page" => 1, "page" => 1];
    $endpoint = "https://book-finder1.p.rapidapi.com/api/search";
    $isRapidAPI = true;
    $rapidAPIHost = "book-finder1.p.rapidapi.com";
    $result = get($endpoint, "BOOK_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
    if (isset($result['results'])) {
        $db = getDB();
        foreach ($result['results'] as $book) {
            try {
                // Check if book exists
                $checkQuery = "SELECT id FROM `IT202-S24-BOOKS` WHERE title = :title";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([":title" => $book['title']]);
                $existingBook = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingBook) {
                    // Update existing book
                    $updateQuery = "UPDATE `IT202-S24-BOOKS` SET 
                                    `page_count` = :page_count, 
                                    `series_name` = :series_name, 
                                    `language` = :language, 
                                    `summary` = :summary, 
                                    `cover_art_url` = :cover_art_url, 
                                    `is_api` = 1, 
                                    `modified` = CURRENT_TIMESTAMP 
                                    WHERE `id` = :id";
                    $updateStmt = $db->prepare($updateQuery);
                    $params = [
                        ":page_count" => $book['page_count'] ?? null,
                        ":series_name" => $book['series_name'] ?? "N/A",
                        ":language" => $book['language'] ?? "Unknown",
                        ":summary" => $book['summary'] ?? "N/A",
                        ":cover_art_url" => isset($book['published_works'][0]['cover_art_url']) ? $book['published_works'][0]['cover_art_url'] : null,
                        ":id" => $existingBook['id']
                    ];
                    $updateStmt->execute($params);

                    // Optionally update authors and categories
                    // ...

                    flash("Updated existing book: " . $book['title']);
                } else {
                    // Insert new book
                    $query = "INSERT INTO `IT202-S24-BOOKS` 
                                (`title`, `page_count`, `series_name`, `language`, `summary`, `cover_art_url`, `is_api`) 
                                VALUES 
                                (:title, :page_count, :series_name, :language, :summary, :cover_art_url, :is_api)";
                    $stmt = $db->prepare($query);
                    $params = [
                        ":title" => $book['title'],
                        ":page_count" => $book['page_count'] ?? null,
                        ":series_name" => $book['series_name'] ?? "N/A",
                        ":language" => $book['language'] ?? "Unknown",
                        ":summary" => $book['summary'] ?? "N/A",
                        ":cover_art_url" => isset($book['published_works'][0]['cover_art_url']) ? $book['published_works'][0]['cover_art_url'] : null,
                        ":is_api" => 1
                    ];
                    $stmt->execute($params);
                    $bookId = $db->lastInsertId();

                    // Insert authors and categories
                    // ...

                    flash("Entered new book: " . $book['title']);
                }
            } catch (PDOException $e) {
                error_log("SQL Error: " . $e->getMessage());
                flash("Database error: " . $e->getMessage(), "danger");
            } catch (Exception $e) {
                error_log("General Error: " . $e->getMessage());
                flash("An error occurred: " . $e->getMessage(), "danger");
            }
        }
    }
}
