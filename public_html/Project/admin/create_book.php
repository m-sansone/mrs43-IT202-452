<?php
// Note: we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'] ?? '';
    $page_count = $_POST['page_count'] ?? '';
    $series_name = $_POST['series_name'] ?? '';
    $language = $_POST['language'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $authors = isset($_POST['authors']) ? array_map('trim', explode("\n", $_POST['authors'])) : [];
    $categories = isset($_POST['categories']) ? array_map('trim', explode("\n", $_POST['categories'])) : [];
    $cover_art_url = $_POST['cover_art_url'] ?? '';

    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    if (empty($page_count)) {
        $errors[] = "Page count is required.";
    } elseif (!is_numeric($page_count) || $page_count <= 0) {
        $errors[] = "Page count must be a positive number.";
    }
    if (empty($language)) {
        $errors[] = "Language is required.";
    }
    if (empty($summary)) {
        $errors[] = "Summary is required.";
    }

    if (empty($errors)) {
        try {
            $db = getDB();

            // Check if book exists
            $checkQuery = "SELECT COUNT(*) as count FROM `IT202-S24-BOOKS` WHERE title = :title";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([":title" => $title]);
            $count = $checkStmt->fetch(PDO::FETCH_ASSOC)["count"];

            if ($count > 0) {
                flash("A book with this title already exists, please try another or edit it", "warning");
            } else {
                // Insert book details
                $query = "INSERT INTO `IT202-S24-BOOKS` (`title`, `page_count`, `series_name`, `language`, `summary`, `cover_art_url`, `is_api`) VALUES (:title, :page_count, :series_name, :language, :summary, :cover_art_url, :is_api)";
                $stmt = $db->prepare($query);
                $params = [
                    ":title" => $title,
                    ":page_count" => $page_count,
                    ":series_name" => $series_name,
                    ":language" => $language,
                    ":summary" => $summary,
                    ":cover_art_url" => $cover_art_url, // Add cover_art_url to the params
                    ":is_api" => 0
                ];

                $stmt->execute($params);
                $bookId = $db->lastInsertId();

                // Insert authors
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        if (!empty($author)) {
                            $query = "INSERT INTO `IT202-S24-AUTHORS` (`book_id`, `author`) VALUES (:book_id, :author)";
                            $stmt = $db->prepare($query);
                            $result = $stmt->execute([":book_id" => $bookId, ":author" => $author]);
                            if (!$result) {
                                error_log("Failed to insert author: " . $author);
                            }
                        }
                    }
                }

                // Insert categories
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        if (!empty($category)) {
                            $query = "INSERT INTO `IT202-S24-CATEGORIES` (`book_id`, `category`) VALUES (:book_id, :category)";
                            $stmt = $db->prepare($query);
                            $result = $stmt->execute([":book_id" => $bookId, ":category" => $category]);
                            if (!$result) {
                                error_log("Failed to insert category: " . $category);
                            }
                        }
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
    } else {
        foreach ($errors as $error) {
            flash($error, "warning");
        }
    }
}
?>

<div class="container-fluid">
    <h1>Enter New Book</h1>
    <form method="POST" action="">
        <div class="form-group">
            <label>Book Title</label>
            <input name="title" class="form-control" required />
        </div>
        <div class="form-group">
            <label>Authors (one per line)</label>
            <textarea name="authors" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <label>Categories (one per line)</label>
            <textarea name="categories" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <label>Page Count</label>
            <input name="page_count" type="number" class="form-control" required />
        </div>
        <div class="form-group">
            <label>Series Name</label>
            <input name="series_name" class="form-control" />
        </div>
        <div class="form-group">
            <label>Language</label>
            <input name="language" class="form-control" required />
        </div>
        <div class="form-group">
            <label>Summary</label>
            <textarea name="summary" class="form-control" required></textarea>
        </div>
        <div class="form-group">
            <label>Cover Image Url</label>
            <textarea name="cover_art_url" class="form-control"></textarea>
        </div>
        <input type="submit" value="Add Book" class="btn btn-primary" />
    </form>
</div>
<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
