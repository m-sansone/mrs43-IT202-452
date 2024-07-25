<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

$id = se($_GET, "id", -1, false);
$book = [];
if ($id > -1) {
    //fetch
    $db = getDB();
    $query = "SELECT title, page_count, series_name, language, summary, is_api FROM `IT202_S24_BOOKS` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $book = $r;
        }
    } catch (PDOException $e) {
        error_log("Error fetching record: " . var_export($e, true));
        flash("Error fetching record", "danger");
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location:" . get_url("admin/list_books.php")));
}

$title = htmlspecialchars($book['title'] ?? '');
$page_count = htmlspecialchars($book['page_count'] ?? '');
$series_name = htmlspecialchars($book['series_name'] ?? '');
$language = htmlspecialchars($book['language'] ?? '');
$summary = htmlspecialchars($book['summary'] ?? '');
$is_api = $book['is_api'] ?? 0;
?>

<div class="container-fluid">
    <h3>View Book</h3>
    <div>
        <a href="<?php echo get_url("admin/list_books.php");?>" class="btn btn-secondary">Back</a>
        <?php if (has_role("Admin")): ?>
            <a href="<?php echo get_url("admin/edit_books.php?id=" . $id);?>" class="btn btn-success">Edit</a>
            <a href="<?php echo get_url("admin/delete_book.php?id=" . $id);?>" class="btn btn-danger">Delete</a>
        <?php endif; ?>
        <ul class="list-group list-group-flush mt-3">
            <li class="list-group-item">
                <strong>Title: </strong><?php echo $title; ?>
            </li>
            <li class="list-group-item list-group-item-success">
                <strong>Series Name: </strong><?php echo $series_name; ?>
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
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>
