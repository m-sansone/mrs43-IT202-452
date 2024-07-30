<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");
$db = getDB();
if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

if(isset($_GET["remove"])){
    $query = "DELETE FROM `IT202-S24-UserBooks` WHERE user_id = :user_id";
    try{
        $stmt = $db->prepare($query);
        $stmt->execute([":user_id"=>get_user_id()]);
        flash("Successfully cleared all libraries", "success");
    } catch (PDOException $e){
        error_log("Error removing book associations: " . var_export($e, true));
        flash("Error removing book associations", "danger");
    }

    redirect("my_books.php");
}

// Build search form
$form = [
    ["type" => "", "name" => "username", "placeholder" => "Username", "label" => "Username", "include_margin" => false],
    ["type" => "", "name" => "title", "placeholder" => "Title", "label" => "Title", "include_margin" => false],
    ["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "include_margin" => false],
    ["type" => "select", "name" => "sort", "label" => "Sort", "options" => ["title" => "Title", "page_count" => "Number of Pages", "language" => "Language"], "include_margin" => false],
    ["type" => "select", "name" => "order", "label" => "Order", "options" => ["asc" => "+", "desc" => "-"], "include_margin" => false],
    ["type" => "number", "name" => "limit", "label" => "Limit", "value" => "10", "include_margin" => false]
];
//error_log("Form data: " . var_export($form, true));

$total_records = get_total_count("`IT202-S24-BOOKS` b JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id");

$query = "SELECT u.username, b.id, title, language, page_count, cover_art_url FROM `IT202-S24-BOOKS` b
JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id JOIN Users u on u.id = ub.user_id";
$params = [];
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

    //username
    $username = se($_GET, "username", "", false);
    if (!empty($username)) {
        $query .= " AND u.username like :username";
        $params[ ":username"] = "%$username%";
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
    //tell mysql I care about the data from table "b"
    if ($sort === "created" || $sort === "modified") {
        $sort = "b." . $sort;
    }
    $order = se($_GET, "order", "desc", false);
    if (!in_array($order, ["asc", "desc"])) {
        $order = "desc";
    }
    //IMPORTANT make sure you fully validate/trust $sort and $order (sql injection possibility)
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
    //IMPORTANT make sure you fully validate/trust $limit (sql injection possibility)
    $query .= " LIMIT $limit";
}


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

$table = ["data" => $results, "title" => "Libraries", "ignored_columns" => ["id"], "view_url" => get_url("view_book.php")];
if (has_role("Admin")) {
    $table["edit_url"] = get_url("edit_book.php");
    $table["delete_url"] = get_url("delete_book.php");
}
?>
<div class="container-fluid">
    <h3>My Books</h3>
    <div>
        <a href="?remove" onclick="!confirm('Are you sure?')?event.preventDefault():''" class="btn btn-danger">Clear All Libraries</a>
    </div>
    <form method="GET">

        <div class="row mb-3" style="align-items: flex-end;">

            <?php foreach ($form as $k => $v) : ?>
                <div class="col">
                    <?php render_input($v); ?>
                </div>
            <?php endforeach; ?>

        </div>
        <?php render_button(["text" => "Search", "type" => "submit", "text" => "Filter"]); ?>
        <a href="?clear" class="btn btn-secondary">Clear</a>
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
// Note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>