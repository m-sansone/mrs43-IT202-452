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
    if (isset($_GET["remove_connections"]) && $_GET["remove_connections"] !== '') {
        if (isset($_GET["remove_book_id"])) {
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
        } elseif (isset($_GET["remove"])) {
            $query = "DELETE FROM `IT202-S24-UserBooks`";
            try {
                $stmt = $db->prepare($query);
                $stmt->execute();
                flash("Successfully cleared all libraries", "success");
            } catch (PDOException $e) {
                error_log("Error clearing all libraries: " . var_export($e, true));
                flash("Error clearing all libraries", "danger");
            }
        }
    }
}

// Build search form
$form = [
    ["type" => "text", "name" => "username", "placeholder" => "Username", "label" => "Username", "include_margin" => false],
    ["type" => "text", "name" => "title", "placeholder" => "Title", "label" => "Title", "include_margin" => false],
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
WHERE 1=1";

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
        $query .= " AND u.username LIKE :username";
        $params[":username"] = "%$username%";
    }

    //title
    $title = se($_GET, "title", "", false);
    if (!empty($title)) {
        $query .= " AND title LIKE :title";
        $params[":title"] = "%$title%";
    }

    //language
    $language = se($_GET, "language", "", false);
    if (!empty($language)) {
        $query .= " AND language LIKE :language";
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
    $query .= " GROUP BY b.id ORDER BY $sort $order";
    
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
} else {
    // Ensure the query has a default GROUP BY and ORDER BY clause to avoid errors
    $query .= " GROUP BY b.id ORDER BY title DESC LIMIT 10";
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
    <h2>All Associations</h2>
    <div>
        <a href="?remove_connections=1&remove" onclick="!confirm('Are you sure?')?event.preventDefault():''" class="btn btn-danger">Clear All Libraries</a>
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
        <a href="?clear" class="btn btn-secondary">Clear Filters</a>
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
                                <li class="list-group-item">Count: <?php se($book, "user_count", 0); ?></li>
                                <li class="list-group-item">
                                    <?php
                                    $usernames = se($book, "usernames", "Unknown", false);
                                    $user_ids = se($book, "user_ids", "Unknown", false);
                                    $users = explode(",", $usernames);
                                    $userids = explode(",", $user_ids);
                                    $params = [];
                                    for ($i = 0; $i < count($users); $i++) {
                                        $user = $users[$i];
                                        $uid = (int)$userids[$i];
                                        if ($uid > -1) {
                                            $params[] = "<a href='" . get_url("profile.php?id=$uid") . "'>$user</a>";
                                        } else {
                                            $params[] = $user;
                                        }
                                    }
                                    echo implode(", ", $params);
                                    ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="../book.php?id=<?= se($book['id'], null) ?>" class="btn btn-secondary">View</a>
                        <a class="btn btn-danger" href="?remove_connections=1&remove_book_id=<?php se($book, 'id'); ?>" onclick="return confirm('Are you sure?');">Remove</a>
                    </div>
                </div>
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
require_once(__DIR__ . "/../../../partials/flash.php");
?>
