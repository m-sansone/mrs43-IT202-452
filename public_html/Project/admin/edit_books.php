<?php
// Note: we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

$id = se($_GET, "id", -1, false);
$book = [];
$authors = [];
$categories = [];

if ($id > -1) {
    // Fetch book details
    $db = getDB();
    $query = "SELECT title, page_count, series_name, language, summary, cover_art_url, is_api FROM `IT202-S24-BOOKS` WHERE id = :id";
    $authQuery = "SELECT author FROM `IT202-S24-AUTHORS` WHERE book_id = :book_id";
    $catsQuery = "SELECT category FROM `IT202-S24-CATEGORIES` WHERE book_id = :book_id";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch authors
        $stmt2 = $db->prepare($authQuery);
        $stmt2->execute([":book_id" => $id]);
        $authors = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        // Fetch categories
        $stmt3 = $db->prepare($catsQuery);
        $stmt3->execute([":book_id" => $id]);
        $categories = $stmt3->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    redirect("admin/list_books.php");
}

$title = htmlspecialchars($book['title'] ?? '');
$page_count = htmlspecialchars($book['page_count'] ?? '');
$series_name = htmlspecialchars($book['series_name'] ?? '');
$language = htmlspecialchars($book['language'] ?? '');
$summary = htmlspecialchars($book['summary'] ?? '');
$cover_art_url = htmlspecialchars($book['cover_art_url'] ?? '');
$is_api = $book['is_api'] ?? 0;

// Handle form submission for updating authors and categories
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_authors = isset($_POST['authors']) ? array_map('trim', explode("\n", $_POST['authors'])) : [];
    $new_categories = isset($_POST['categories']) ? array_map('trim', explode("\n", $_POST['categories'])) : [];

    try {
        $db->beginTransaction();

        // Update authors
        $deleteAuthQuery = "DELETE FROM `IT202-S24-AUTHORS` WHERE book_id = :book_id";
        $db->prepare($deleteAuthQuery)->execute([":book_id" => $id]);

        foreach ($new_authors as $author) {
            if (!empty($author)) {
                $query = "INSERT INTO `IT202-S24-AUTHORS` (`book_id`, `author`) VALUES (:book_id, :author)";
                $stmt = $db->prepare($query);
                $stmt->execute([":book_id" => $id, ":author" => $author]);
            }
        }

        // Update categories
        $deleteCatsQuery = "DELETE FROM `IT202-S24-CATEGORIES` WHERE book_id = :book_id";
        $db->prepare($deleteCatsQuery)->execute([":book_id" => $id]);

        foreach ($new_categories as $category) {
            if (!empty($category)) {
                $query = "INSERT INTO `IT202-S24-CATEGORIES` (`book_id`, `category`) VALUES (:book_id, :category)";
                $stmt = $db->prepare($query);
                $stmt->execute([":book_id" => $id, ":category" => $category]);
            }
        }

        $db->commit();
        flash("Updated authors and categories successfully");
        redirect("admin/view_book.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("SQL Error: " . $e->getMessage());
        flash("Database error: " . $e->getMessage(), "danger");
    } catch (Exception $e) {
        $db->rollBack();
        error_log("General Error: " . $e->getMessage());
        flash("An error occurred: " . $e->getMessage(), "danger");
    }
}
?>

<div class="container-fluid">
    <h3>Edit Book</h3>
    <div>
        <a href="<?php echo get_url("admin/list_books.php"); ?>" class="btn btn-secondary">Back</a>
        <?php if (has_role("Admin")): ?>
            <a href="<?php echo get_url("admin/edit_books.php?id=" . $id); ?>" class="btn btn-success">Edit</a>
            <a href="<?php echo get_url("admin/delete_book.php?id=" . $id); ?>" class="btn btn-danger">Delete</a>
        <?php endif; ?>
        <form method="POST" class="mt-3">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo $title; ?>" />
            </div>
            <div class="form-group">
                <label for="series_name">Series Name</label>
                <input type="text" id="series_name" name="series_name" class="form-control" value="<?php echo $series_name; ?>"/>
            </div>
            <div class="form-group">
                <label for="authors">Authors (one per line)</label>
                <textarea id="authors" name="authors" class="form-control" rows="4"><?php echo implode("\n", $authors); ?></textarea>
            </div>
            <div class="form-group">
                <label for="categories">Categories (one per line)</label>
                <textarea id="categories" name="categories" class="form-control" rows="4"><?php echo implode("\n", $categories); ?></textarea>
            </div>
            <div class="form-group">
                <label for="page_count">Number of Pages</label>
                <input type="number" id="page_count" name="page_count" class="form-control" value="<?php echo $page_count; ?>" />
            </div>
            <div class="form-group">
                <label for="language">Language</label>
                <input type="text" id="language" name="language" class="form-control" value="<?php echo $language; ?>"/>
            </div>
            <div class="form-group">
                <label for="summary">Summary</label>
                <textarea id="summary" name="summary" class="form-control" rows="4" readonly><?php echo $summary; ?></textarea>
            </div>
            <div class="form-group">
                <label for="cover_art_url">Cover Image Url</label>
                <input type="text" id="cover_art_url" name="cover_art_url" class="form-control" value="<?php echo $cover_art_url; ?>"/>
            </div>
            <div class="form-group">
                <label for="is_api">User or API Data</label>
                <input type="text" id="is_api" name="is_api" class="form-control" value="<?php echo $is_api ? 'API' : 'User'; ?>" readonly />
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
