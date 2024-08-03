<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    redirect("home.php");
}

// Handle form submission and filtering
$form = [
    ["type" => "", "name" => "title", "placeholder" => "Title of Book", "label" => "Title", "include_margin" => false],
    ["type" => "", "name" => "username", "placeholder" => "Username", "label" => "Username", "include_margin" => false]
];

// Initialize query components
$book_query = "SELECT b.id, 
                      title, 
                      language, 
                      page_count, 
                      cover_art_url, 
                      GROUP_CONCAT(DISTINCT u.username SEPARATOR ', ') as usernames,
                      GROUP_CONCAT(DISTINCT u.id SEPARATOR ',') as user_ids
               FROM `IT202-S24-BOOKS` b
               LEFT JOIN `IT202-S24-UserBooks` ub ON b.id = ub.book_id 
               LEFT JOIN Users u ON u.id = ub.user_id 
               WHERE 1=1";

$user_query = "SELECT id, username FROM Users WHERE 1=1";

$book_params = [];
$user_params = [];

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

    // Title filter for books
    $title = se($_GET, "title", "", false);
    if (!empty($title)) {
        $book_query .= " AND title LIKE :title";
        $book_params[":title"] = "%$title%";
    }

    // Username filter for users
    $username = se($_GET, "username", "", false);
    if (!empty($username)) {
        $user_query .= " AND username LIKE :username";
        $user_params[":username"] = "%$username%";
    }
}

$book_query .= " GROUP BY b.id LIMIT 25";

// Prepare and execute the book query
$db = getDB();
$stmt = $db->prepare($book_query);
$results = [];
try {
    // Execute with the correct parameters for the book query
    $stmt->execute($book_params);
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching books " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

// Prepare and execute the user query
$users_stmt = $db->prepare($user_query);
try {
    // Execute with the correct parameters for the user query
    $users_stmt->execute($user_params);
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}

// Handle form submission for applying associations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_book_ids = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $selected_user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];

    if (empty($selected_book_ids) || empty($selected_user_ids)) {
        flash("Please select at least one book and one user.", "warning");
    } else {
        try {
            $db = getDB();
            $db->beginTransaction();

            // Get existing associations
            $existing_stmt = $db->prepare("SELECT user_id, book_id FROM `IT202-S24-UserBooks` WHERE user_id IN (" . implode(',', array_fill(0, count($selected_user_ids), '?')) . ") AND book_id IN (" . implode(',', array_fill(0, count($selected_book_ids), '?')) . ")");
            $existing_stmt->execute(array_merge($selected_user_ids, $selected_book_ids));
            $existing_associations = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);
            $existing_pairs = [];
            foreach ($existing_associations as $association) {
                $existing_pairs[$association['user_id'] . '-' . $association['book_id']] = true;
            }            

            // Insert new associations
            $insert_stmt = $db->prepare("INSERT IGNORE INTO `IT202-S24-UserBooks` (user_id, book_id) VALUES (:user_id, :book_id)");

            foreach ($selected_user_ids as $user_id) {
                foreach ($selected_book_ids as $book_id) {
                    $pair_key = $user_id . '-' . $book_id;
                    if (isset($existing_pairs[$pair_key])) {
                        // Remove existing association
                        $delete_stmt = $db->prepare("DELETE FROM `IT202-S24-UserBooks` WHERE user_id = :user_id AND book_id = :book_id");
                        $delete_stmt->execute([':user_id' => $user_id, ':book_id' => $book_id]);
                    } else {
                        // Add new association
                        $insert_stmt->execute([':user_id' => $user_id, ':book_id' => $book_id]);
                    }
                }
            }

            $db->commit();
            flash("Associations applied successfully!", "success");
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error applying associations " . var_export($e, true));
            flash("Unhandled error occurred: " . $e->getMessage(), "danger");
        }
    }
}
?>
<div class="container-fluid">
    <h3>Manage User Libraries</h3>
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

    <form method="POST">
        <h3>Users</h3>
        <div class="row w-100 row-cols-auto row-cols-sm-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-4">
            <?php foreach ($users as $user) : ?>
                <div class="col">
                    <div class="card mx-auto" style="width: 18rem;">
                        <div class="card-body">
                            <h5 class="card-title"><?php se($user, "username", "Unknown"); ?> </h5>
                            <input type="checkbox" name="user_ids[]" value="<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>"> Select
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <h3>Books</h3>
        <div class="row w-100 row-cols-auto row-cols-sm-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 row-cols-xxl-5 g-4">
            <?php if (!empty($results)) : ?>
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
                                            $user_ids = isset($book['user_ids']) ? explode(',', $book['user_ids']) : [];
                                            $usernames = isset($book['usernames']) ? explode(', ', $book['usernames']) : [];
                                            foreach ($usernames as $index => $username) {
                                                echo "<a href='../profile.php?id=" . $user_ids[$index] . "'>" . htmlspecialchars($username) . "</a>";
                                                if ($index < count($usernames) - 1) {
                                                    echo ", ";
                                                }
                                            }
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                                <input type="checkbox" name="book_ids[]" value="<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>"> Select
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col">
                    No results
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success mt-3">Update Associations</button>
    </form>
</div>

<?php
// Note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>
