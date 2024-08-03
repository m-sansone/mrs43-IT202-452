<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

// Handle remove book action
if (isset($_GET["remove_book"]) && isset($_GET["book_id"])) {
    $book_id = (int)$_GET["book_id"];
    $db = getDB();
    $query = "DELETE FROM `IT202-S24-UserBooks` WHERE user_id = :user_id AND book_id = :book_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([
            ":user_id" => get_user_id(),
            ":book_id" => $book_id
        ]);
        flash("Successfully removed the book from your library", "success");
    } catch (PDOException $e) {
        error_log("Error removing book from library: " . var_export($e, true));
        flash("Error removing book from library", "danger");
    }
    redirect("my_books.php");
}

// Handle remove all books action
if (isset($_GET["clear_all"])) {
    $db = getDB();
    $query = "DELETE FROM `IT202-S24-UserBooks` WHERE user_id = :user_id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([
            ":user_id" => get_user_id()
        ]);
        flash("Successfully removed all books from your library", "success");
    } catch (PDOException $e) {
        error_log("Error removing all books from library: " . var_export($e, true));
        flash("Error removing all books from library", "danger");
    }
    redirect("my_books.php");
}

// Build search form
$form = [
    ["type" => "", "name" => "title", "placeholder" => "Title", "label" => "Title", "include_margin" => false],
    ["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "include_margin" => false],
    ["type" => "select", "name" => "sort", "label" => "Sort", "options" => ["title" => "Title", "page_count" => "Number of Pages", "language" => "Language"], "include_margin" => false],
    ["type" => "select", "name" => "order", "label" => "Order", "options" => ["asc" => "+", "desc" => "-"], "include_margin" => false],
    ["type" => "number", "name" => "limit", "label" => "Limit", "value" => "10", "include_margin" => false]
];

$total_records = get_total_count("`IT202-S24-BOOKS` b JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id WHERE user_id = :user_id", [":user_id" => get_user_id()]);

$query = "SELECT u.username, b.id, title, language, page_count, cover_art_url, ub.user_id FROM `IT202-S24-BOOKS` b
JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id LEFT JOIN Users u on u.id = ub.user_id
WHERE user_id = :user_id";

$params = [":user_id" => get_user_id()];
$session_key = $_SERVER["SCRIPT_NAME"];
$is_clear = isset($_GET["clear"]);
if ($is_clear) {
    session_delete($session_key);
    unset($_GET["clear"]);
    redirect($session_key);
} else {
    $session_data = session_load($session_key);
}

if (count($_GET) == 0 && isset($session_data) && count($session_data) > 0) {
    if ($session_data) {
        $_GET = $session_data;
    }
}
if (count($_GET) > 0) {
    session_save($session_key, $_GET);
    $keys = array_keys($_GET);

    foreach ($form as $k => $v) {
        if (in_array($v["name"], $keys)) {
            $form[$k]["value"] = $_GET[$v["name"]];
        }
    }

    //title
    $title = se($_GET, "title", "", false);
    if (!empty($title)) {
        $query .= " AND title like :title";
        $params[":title"] = "%$title%";
    }

    //language
    $language = se($_GET, "language", "", false);
    if (!empty($language)) {
        $query .= " AND language like :language";
        $params[":language"] = "%$language%";
    }

    //sort and order
    $sort = se($_GET, "sort", "title", false);
    if (!in_array($sort, ["title", "page_count", "language"])) {
        $sort = "title";
    }
    if ($sort === "created" || $sort === "modified") {
        $sort = "b." . $sort;
    }
    $order = se($_GET, "order", "desc", false);
    if (!in_array($order, ["asc", "desc"])) {
        $order = "desc";
    }
    $query .= " ORDER BY $sort $order";

    //limit
    try {
        $limit = (int)se($_GET, "limit", "10", false);
    } catch (Exception $e) {
        $limit = 10;
    }
    if ($limit < 1 || $limit > 100) {
        $limit = 10;
    }
    $query .= " LIMIT $limit";
}

$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll();
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching books " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

$table = ["data" => $results, "title" => "Libraries", "ignored_columns" => ["id"], "view_url" => get_url("book.php")];
if (has_role("Admin")) {
    $table["delete_url"] = get_url("delete_book.php");
}
?>
<div class="container-fluid">
    <h3>My Books</h3>
    <form method="GET">
        <div class="row mb-3" style="align-items: flex-end;">
            <?php foreach ($form as $k => $v) : ?>
                <div class="col">
                    <?php render_input($v); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php render_button(["text" => "Search", "type" => "submit", "text" => "Filter"]); ?>
        <a href="?clear" class="btn btn-secondary">Clear Filters</a>
        <a href="?clear_all" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear all books from your library?');">Remove All Books</a>
        <?php render_result_counts(count($results), $total_records); ?>
    </form>
    <div class="row w-100 row-cols-auto row-cols-sm-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-4">
        <?php foreach ($results as $book) : ?>
            <div class="col">
                <?php render_book_card($book); ?>
            </div>
        <?php endforeach; ?>
        <?php if (count($results) === 0) : ?>
            <div class="col">
                No results
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>
