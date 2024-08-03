<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

// Build search form
$form = [
    ["type" => "", "name" => "title", "placeholder" => "Title", "label" => "Title", "include_margin" => false],
    ["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "include_margin" => false],
    ["type" => "select", "name" => "sort", "label" => "Sort", "options" => ["title" => "Title", "page_count" => "Number of Pages", "language" => "Language"], "include_margin" => false],
    ["type" => "select", "name" => "order", "label" => "Order", "options" => ["asc" => "+", "desc" => "-"], "include_margin" => false],
    ["type" => "number", "name" => "limit", "label" => "Limit", "value" => "10", "include_margin" => false]
];
error_log("Form data: " . var_export($form, true));

$total_records = get_total_count("`IT202-S24-BOOKS` b LEFT JOIN `IT202-S24-UserBooks` ub on b.id = ub.book_id");

$query = "SELECT b.id, 
                 MIN(u.username) as username, 
                 title, 
                 language, 
                 page_count, 
                 cover_art_url, 
                 MIN(ub.user_id) as user_id 
          FROM `IT202-S24-BOOKS` b
          LEFT JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id 
          LEFT JOIN Users u ON u.id = ub.user_id 
          WHERE 1=1";

// Initialize parameters
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

// Load session data if no GET parameters are present
if (count($_GET) == 0 && isset($session_data) && count($session_data) > 0) {
    if ($session_data) {
        $_GET = $session_data;
    }
}

// Apply filters
if (count($_GET) > 0) {
    session_save($session_key, $_GET);
    $keys = array_keys($_GET);

    foreach ($form as $k => $v) {
        if (in_array($v["name"], $keys)) {
            $form[$k]["value"] = $_GET[$v["name"]];
        }
    }

    // Title filter
    $title = se($_GET, "title", "", false);
    if (!empty($title)) {
        $query .= " AND title LIKE :title";
        $params[":title"] = "%$title%";
    }

    // Language filter
    $language = se($_GET, "language", "", false);
    if (!empty($language)) {
        $query .= " AND language LIKE :language";
        $params[":language"] = "%$language%";
    }

    // Sort and Order
    $sort = se($_GET, "sort", "title", false);
    if (!in_array($sort, ["title", "page_count", "language"])) {
        $sort = "title";
    }
    // Tell MySQL to consider the data from table "b"
    if ($sort === "created" || $sort === "modified") {
        $sort = "b." . $sort;
    }
    $order = se($_GET, "order", "desc", false);
    if (!in_array($order, ["asc", "desc"])) {
        $order = "desc";
    }
    $query .= " GROUP BY b.id, title, language, page_count, cover_art_url"; // Group by non-aggregated columns
    $query .= " ORDER BY $sort $order";
    
    // Limit
    try {
        $limit = (int)se($_GET, "limit", "10", false);
    } catch (Exception $e) {
        $limit = 10;
    }
    if ($limit < 1 || $limit > 100) {
        $limit = 10;
    }
    $query .= " LIMIT $limit";
} else {
    // Ensure GROUP BY is still included if no filters are applied
    $query .= " GROUP BY b.id, title, language, page_count, cover_art_url";
}

$db = getDB();
$stmt = $db->prepare($query);
$results = [];
try {
    $stmt->execute($params);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <h3>Explore Books</h3>
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
require_once(__DIR__ . "/../../partials/flash.php");
?>
