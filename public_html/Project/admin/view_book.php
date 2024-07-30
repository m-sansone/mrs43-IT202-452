<?php
// Note: we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

$id = se($_GET, "id", -1, false);
$book = [];
$authors = [];
$categories = [];

if ($id > -1) {
    // Fetch book details
    $db = getDB();
    $query = "SELECT title, page_count, series_name, language, summary, cover_art_url, is_api FROM `IT202-S24-BOOKS` WHERE id = :id";
    $authQuery = "SELECT author FROM `IT202-S24-AUTHORS` WHERE book_id = :id";
    $catsQuery = "SELECT category FROM `IT202-S24-CATEGORIES` WHERE book_id = :id";
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $book = $r;
        }

        // Fetch authors
        $stmt2 = $db->prepare($authQuery);
        $stmt2->execute([":id" => $id]);
        $authors = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        // Fetch categories
        $stmt3 = $db->prepare($catsQuery);
        $stmt3->execute([":id" => $id]);
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
?>

<div class="container-fluid">
    <h3>View Book</h3>
    <div>
        <a href="<?php echo get_url("admin/list_books.php"); ?>" class="btn btn-secondary">Back</a>
        <?php if (has_role("Admin")): ?>
            <a href="<?php echo get_url("admin/edit_books.php?id=" . $id); ?>" class="btn btn-success">Edit</a>
            <a href="<?php echo get_url("admin/delete_book.php?id=" . $id); ?>" class="btn btn-danger">Delete</a>
        <?php endif; ?>
        <ul class="list-group list-group-flush mt-3">
            <li class="list-group-item">
                <img src="<?php echo $cover_art_url; ?>" alt="cover">
            </li>
            <li class="list-group-item">
                <strong>Title: </strong><?php echo $title; ?>
            </li>
            <li class="list-group-item list-group-item-success">
                <strong>Series Name: </strong><?php echo $series_name; ?>
            </li>
            <li class="list-group-item">
                <strong>Authors: </strong>
                <?php echo !empty($authors) ? implode(', ', $authors) : 'No authors found'; ?>
            </li>
            <li class="list-group-item list-group-item-success">
                <strong>Categories: </strong>
                <?php echo !empty($categories) ? implode(', ', $categories) : 'No categories found'; ?>
            </li>
            <li class="list-group-item">
                <strong>Language: </strong><?php echo $language; ?>
            </li>
            <li class="list-group-item list-group-item-success">
                <strong>Number of Pages: </strong><?php echo $page_count; ?>
            </li>
            <li class="list-group-item">
                <strong>Summary: </strong><?php echo $summary; ?>
            </li>
            <li class="list-group-item list-group-item-success">
                <strong>User or API Data: </strong><?php echo $is_api ? 'API' : 'User'; ?>
            </li>
        </ul>
    </div>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
