<?php
require_once(__DIR__ . "/../../../partials/nav.php");
require_once(__DIR__ . "/../../../lib/db.php");

$db = getDB();

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Handle book removal
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET["remove_connections"]) && $_GET["remove_connections"] !== '' && isset($_GET["remove_book_id"])) {
        $book_id = (int)$_GET["remove_book_id"];
        if ($book_id > 0) {
            $query = "DELETE FROM `IT202-S24-UserBooks` WHERE book_id = :book_id";
            try {
                $stmt = $db->prepare($query);
                $stmt->execute([":book_id" => $book_id]);
                flash("Successfully removed book from all libraries", "success");
            } catch (PDOException $e) {
                error_log("Error removing book associations: " . var_export($e, true));
                flash("Error removing book associations", "danger");
            }
        } else {
            flash("Invalid book ID", "warning");
        }
    } 
    
    //redirect("admin/book_associations.php");
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

$total_records = get_total_count("`IT202-S24-BOOKS` b JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id");

$query = "SELECT b.id, title, language, page_count, cover_art_url, GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as usernames, GROUP_CONCAT(DISTINCT u.id SEPARATOR ',') as user_ids, COUNT(DISTINCT u.id) as user_count
FROM `IT202-S24-BOOKS` b
JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id 
JOIN Users u on u.id = ub.user_id
GROUP BY b.id";

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
        $params[":username"] = "%$username%";
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
    <h3>All Associations</h3>
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
                <div class="card mx-auto" style="width: 18rem;">
                    <img src="<?php se($book, "cover_art_url", "Unknown"); ?>" class="card-img-top img-thumbnail w-auto p-2" style="height: 400px" alt="...">
                    <div class="card-body">
                        <h5 class="card-title"><?php se($book, "title", "Unknown"); ?> </h5>
                        <div class="card-text">
                            <ul class="list-group">
                                <li class="list-group-item">Number of Pages: <?php se($book, "page_count", "Unknown"); ?></li>
                                <li class="list-group-item">Language: <?php se($book, "language", "Unknown"); ?></li>
                                <li class="list-group-item">Saved by: 
                                    <?php
                                    $user_ids = explode(',', $book['user_ids']);
                                    $usernames = explode(', ', $book['usernames']);
                                    foreach ($usernames as $index => $username) {
                                        echo "<a href='../profile.php?id=" . $user_ids[$index] . "'>" . htmlspecialchars($username) . "</a>";
                                        if ($index < count($usernames) - 1) {
                                            echo ", ";
                                        }
                                    }
                                    ?>
                                </li>
                                <li class="list-group-item">Total Users: <?php se($book, "user_count", 0); ?></li>
                            </ul>
                        </div>
                        <a href="<?php echo get_url("admin/view_book.php?id=" . se($book, "id", "", false)); ?>" class="btn btn-primary">View</a>
                        <form method="GET" action="book_associations.php" onsubmit="return confirm('Are you sure you want to remove this book from all libraries?');">
                            <input type="hidden" name="remove_book_id" value="<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" name="remove_connections" value="1" class="btn btn-danger">Remove from Library</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
require(__DIR__ . "/../../../partials/flash.php");
?>
