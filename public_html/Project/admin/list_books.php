<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Define the get_total_count function if not already defined
if (!function_exists('get_total_count')) {
    function get_total_count($table_refs, $params = []) {
        $table_refs = preg_replace('/[^a-zA-Z0-9_\-.`\s=:()]/', '', $table_refs);
        error_log("Table refs $table_refs");
        error_log("Params: " . var_export($params, true));
        foreach ($params as $k => $v) {
            if (!str_starts_with($k, ":")) {
                $params[":$k"] = $v;
                unset($params[$k]);
            }
        }
        $query = "SELECT COUNT(DISTINCT b.id) as totalCount FROM $table_refs";
        try {
            $db = getDB();
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue("$key", $value);
                error_log("Binding value for $key: $value");
            }
            $stmt->execute();
            $r = $stmt->fetch();
            if ($r) {
                return (int)$r["totalCount"];
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Error getting count for " . var_export($query, true) . ": " . var_export($e, true));
            flash("Error getting count", "danger");
        }
        return -1;
    }
}

// Build search form
$form = [
    ["type" => "", "name" => "title", "placeholder" => "Title", "label" => "Title", "include_margin" => false],
    ["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "include_margin" => false],
    ["type" => "select", "name" => "sort", "label" => "Sort", "options" => ["title" => "Title", "page_count" => "Number of Pages", "language" => "Language"], "include_margin" => false],
    ["type" => "select", "name" => "order", "label" => "Order", "options" => ["asc" => "+", "desc" => "-"], "include_margin" => false],
    ["type" => "number", "name" => "limit", "label" => "Limit", "value" => "10", "include_margin" => false],
];
error_log("Form data: " . var_export($form, true));

$total_records = get_total_count("`IT202-S24-BOOKS` b");

$query = "SELECT id, title, language, page_count FROM `IT202-S24-BOOKS` WHERE 1=1";
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

$table = ["data" => $results, "title" => "Latest Searched Books", "ignored_columns" => ["id"], "view_url" => get_url("admin/view_book.php")];
if (has_role("Admin")) {
    $table["edit_url"] = get_url("admin/edit_books.php");
    $table["delete_url"] = get_url("admin/delete_book.php");
}
?>
<div class="container-fluid">
    <h2>List Books</h2>
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
    </form>
    <?php render_result_counts(count($results), $total_records); ?>
    <?php render_table($table); ?>
</div>

<?php
// Note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>
