<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}
?>

<?php
// Handle book fetch and enter
if (isset($_POST["action"])) {
    error_log("API response: " . var_export($books, true));
    $action = $_POST["action"];
    $title = strtoupper(se($_POST, "title", "", false));

    if ($action === "fetch" && $title) {
        $books = fetch_quote($title);
        error_log("Data from API: " . var_export($books, true));

        // Insert each book entry into the database
        if (!empty($books)) {
            $db = getDB();
            foreach ($books as $book) {
                $query = "INSERT INTO `IT202_S24_BOOKS` (title, page_count, series_name, language, summary) VALUES (:title, :page_count, :series_name, :language, :summary)";
                $params = [
                    ":title" => $book["title"],
                    ":page_count" => is_numeric($book["page_count"]) ? (int)$book["page_count"] : NULL,
                    ":series_name" => $book["series_name"],
                    ":language" => $book["language"],
                    ":summary" => $book["summary"]
                ];

                error_log("Query: " . $query);
                error_log("Params: " . var_export($params, true));

                try {
                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    flash("Inserted record " . $db->lastInsertId(), "success");
                } catch (PDOException $e) {
                    error_log("Something broke with the query: " . var_export($e, true));
                    flash("An error occurred", "danger");
                }
            }
        }
    } else if ($action === "enter") {
        // Sanitize and prepare data for insertion
        $db = getDB();
        $query = "INSERT INTO `IT202_S24_BOOKS` (title, page_count, series_name, language, summary) VALUES (:title, :page_count, :series_name, :language, :summary)";
        
        // Ensure we only include valid fields
        $params = [
            ":title" => se($_POST, "title", "", false),
            ":page_count" => is_numeric(se($_POST, "page_count", "0", false)) ? (int)se($_POST, "page_count", "0", false) : NULL,
            ":series_name" => se($_POST, "series_name", "", false),
            ":language" => se($_POST, "language", "", false),
            ":summary" => se($_POST, "summary", "", false)
        ];

        error_log("Query: " . $query);
        error_log("Params: " . var_export($params, true));

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            flash("Inserted record " . $db->lastInsertId(), "success");
        } catch (PDOException $e) {
            error_log("Something broke with the query: " . var_export($e, true));
            flash("An error occurred", "danger");
        }
    }
}
?>

<div class="container-fluid">
    <h3>Enter or Fetch Book Information</h3>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link text-light bg-success" href="#" onclick="switchTab('fetch')">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-light bg-success" href="#" onclick="switchTab('enter')">Enter</a>
        </li>
    </ul>
    <div id="fetch" class="tab-target">
        <form method="POST">
            <?php render_input(["type" => "search", "name" => "title", "placeholder" => "Title", "rules"=> ["required" => "required"]]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "fetch"]); ?>
            <?php render_button(["text" => "Search", "type" => "submit"]); ?>
        </form>
    </div>
    <div id="enter" style="display: none;" class="tab-target">
        <form method="POST">
            <?php render_input(["type" => "text", "name" => "title", "placeholder" => "Title", "label" => "Title", "rules"=> ["required" => "required"]]); ?>
            <?php render_input(["type" => "number", "name" => "page_count", "placeholder" => "Number of Pages", "label" => "Number of Pages", "rules"=> ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "series_name", "placeholder" => "Series", "label" => "Series"]); ?>
            <?php render_input(["type" => "text", "name" => "language", "placeholder" => "Language", "label" => "Language", "rules"=> ["required" => "required"]]); ?>
            <?php render_input(["type" => "text", "name" => "summary", "placeholder" => "Summary", "label" => "Summary", "rules"=> ["required" => "required"]]); ?>
            <?php render_input(["type" => "hidden", "name" => "action", "value" => "enter"]); ?>
            <?php render_button(["text" => "Enter", "type" => "submit"]); ?>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        let target = document.getElementById(tab);
        if (target) {
            let eles = document.getElementsByClassName("tab-target");
            for (let ele of eles) {
                ele.style.display = (ele.id === tab) ? "block" : "none";
            }
        }
    }
</script>

<?php
// Note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>
