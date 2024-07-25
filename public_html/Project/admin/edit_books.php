<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
$id = se($_GET, "id", -1, false);
//TODO handle book fetch
if (isset($_POST["title"])) {
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ["title", "page_count", "series_name", "language", "summary"])) {
            unset($_POST[$k]);
        }
        $quote = $_POST;
        error_log("Cleaned up POST: " . var_export($quote, true));
    }
    //insert data
    $db = getDB();
    $query = "UPDATE `IT202_S24_BOOKS` SET ";

    $params = [];
    //per record
    foreach ($quote as $k => $v) {

        if ($params) {
            $query .= ",";
        }
        //be sure $k is trusted as this is a source of sql injection
        $query .= "$k=:$k";
        $params[":$k"] = $v;
    }

    $query .= " WHERE id = :id";
    $params[":id"] = $id;
    error_log("Query: " . $query);
    error_log("Params: " . var_export($params, true));
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated record ", "success");
    } catch (PDOException $e) {
        error_log("Something broke with the query" . var_export($e, true));
        flash("An error occurred", "danger");
    }
}

$book = [];
if ($id > -1) {
    //fetch
    $db = getDB();
    $query = "SELECT title, page_count, series_name, language, summary FROM `IT202_S24_BOOKS` WHERE id = :id";
    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch();
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
if ($book) {
    $form = [
        ["type" => "text", "name" => "title", "placeholder" => "Title", "label" => "Title", "rules"=> ["required" => "required"]],
        ["type" => "number", "name" => "page_count", "placeholder" => "Number of Pages", "label" => "Number of Pages", "rules"=> ["required" => "required"]],
        ["type" => "text", "name" => "series_name", "placeholder" => "Series", "label" => "Series"],
        ["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "rules"=> ["required" => "required"]],
        ["type" => "text", "name" => "summary", "placeholder" => "Summary", "label" => "Summary", "rules"=> ["required" => "required"]],

    ];
    $keys = array_keys($book);

    foreach ($form as $k => $v) {
        if (in_array($v["name"], $keys)) {
            $form[$k]["value"] = $book[$v["name"]];
        }
    }
}
//TODO handle manual create book
?>
<div class="container-fluid">
    <h3>Edit Book</h3>
    <form method="POST">
        <?php foreach ($form as $k => $v) {

            render_input($v);
        } ?>
        <?php render_button(["text" => "Search", "type" => "submit", "text" => "Update"]); ?>
    </form>

</div>


<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>