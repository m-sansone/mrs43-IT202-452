<?php
// Note: we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Handle book fetch
$result = [];
if (isset($_GET["title"])) {
    $data = ["title" => $_GET["title"]];
    $endpoint = "https://book-finder1.p.rapidapi.com/api/search";
    $isRapidAPI = true;
    $rapidAPIHost = "book-finder1.p.rapidapi.com";
    $result = get($endpoint, "BOOK_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
    if (isset($result['results']) && count($result['results']) > 0) {
        // Only process the first book
        $book = $result['results'][0];
        
        $db = getDB();
        try {
            // Check if book exists
            $checkQuery = "SELECT COUNT(*) as count FROM `IT202-S24-BOOKS` WHERE title = :title";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([":title" => $book['title']]);
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)["count"];

            if ($count > 0) {
                flash("A book with this title already exists, please try another or edit it", "warning");
            } else {
                // Insert book details
                $query = "INSERT INTO `IT202-S24-BOOKS` (`title`, `page_count`, `series_name`, `language`, `summary`, `cover_art_url`, `is_api`) VALUES (:title, :page_count, :series_name, :language, :summary, :cover_art_url, :is_api)";
                $stmt = $db->prepare($query);
                $params = [
                    ":title" => $book['title'],
                    ":page_count" => $book['page_count'] ?? null,
                    ":series_name" => $book['series_name'] ?? "N/A",
                    ":language" => $book['language'] ?? "Unknown",
                    ":summary" => $book['summary'] ?? "N/A",
                    ":is_api" => 1
                ];

                if (isset($book['published_works']) && is_array($book['published_works'])) {
                    $temp = $book['published_works'][0];
                    $params[":cover_art_url"]=$temp["cover_art_url"];
                }

                $stmt->execute($params);
                $bookId = $db->lastInsertId();

                // Insert authors
                if (isset($book['authors']) && is_array($book['authors'])) {
                    foreach ($book['authors'] as $author) {
                        $query = "INSERT INTO `IT202-S24-AUTHORS` (`book_id`, `author`) VALUES (:book_id, :author)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([":book_id" => $bookId, ":author" => $author]);
                    }
                }

                // Insert categories
                if (isset($book['categories']) && is_array($book['categories'])) {
                    foreach ($book['categories'] as $category) {
                        $query = "INSERT INTO `IT202-S24-CATEGORIES` (`book_id`, `category`) VALUES (:book_id, :category)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([":book_id" => $bookId, ":category" => $category]);
                    }
                }

                flash("Entered new book");
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
?>

<div class="container-fluid">
    <h2>Fetch New Book</h2>
    <form>
        <div>
            <label>Book Title</label>
            <input name="title" class="form-control" />
            <input type="submit" value="Fetch Book" class="btn btn-success" />
        </div>
    </form>
</div>
<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
