<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$query = "SELECT id, title, page_count, series_name, language, summary FROM `IT202_S24_BOOKS` ORDER BY created DESC LIMIT 25";
$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching books " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = ["data" => $results, "title" => "Latest Searched Books", "ignored_columns" => ["id"], "edit_url" => get_url("admin/edit_books.php")];
?>
<div class="container-fluid">
    <h3>List Books</h3>
    <?php render_table($table); ?>
</div>

?>

<?php
// Note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>
