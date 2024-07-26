<?php
// Note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

// Initialize $db here to be used in all conditions
$db = getDB();
?>

<?php
// Handle book fetch and enter
//mrs43 7-25-2024
if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $title = strtoupper(se($_POST, "title", "", false));

    if ($action === "fetch" && $title) {
        $books = fetch_quote($title);
        error_log("Data from API: " . var_export($books, true));

        if (!empty($books)) {
            foreach ($books as $book) {
                // Check if book exists
                $checkQuery = "SELECT COUNT(*) as count FROM `IT202_S24_BOOKS` WHERE title = :title";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([":title" => $book["title"]]);
                $count = $checkStmt->fetch(PDO::FETCH_ASSOC)["count"];

                if ($count > 0) {
                    flash("A book with the title '{$book["title"]}' already exists.", "warning");
                    continue;
                }

                // Check for page_count at the top level
                $page_count = isset($book["page_count"]) ? $book["page_count"] : null;
                
                // If page_count is null, check inside published_works array
                if ($page_count === null && isset($book['published_works'][0]['page_count'])) {
                    $page_count = $book['published_works'][0]['page_count'];
                    error_log("Page count from published_works: " . var_export($page_count, true));
                }
                //mrs43 7-25-2024
                // Check if summary is set
                $summary = isset($book["summary"]) ? $book["summary"] : "No description available";
                error_log("Summary: " . var_export($summary, true));

                $query = "INSERT INTO `IT202_S24_BOOKS` (title, page_count, series_name, language, summary, is_api) VALUES (:title, :page_count, :series_name, :language, :summary, :is_api)";
                $params = [
                    ":title" => $book["title"],
                    ":page_count" => $page_count,
                    ":series_name" => isset($book["series_name"]) ? $book["series_name"] : "N/A",
                    ":language" => isset($book["language"]) ? $book["language"] : "unknown",
                    ":summary" => $summary,
                    ":is_api" => 1
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
        //mrs43 7-25-2024
        // Sanitize and prepare data for insertion
        $title = se($_POST, "title", "", false);
        $page_count = is_numeric(se($_POST, "page_count", "0", false)) ? (int)se($_POST, "page_count", "0", false) : NULL;
        $series_name = se($_POST, "series_name", "", false);
        $language = se($_POST, "language", "", false);
        $summary = se($_POST, "summary", "", false);

        // Check if book exists
        $checkQuery = "SELECT COUNT(*) as count FROM `IT202_S24_BOOKS` WHERE title = :title";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([":title" => $title]);
        $count = $checkStmt->fetch(PDO::FETCH_ASSOC)["count"];

        if ($count > 0) {
            flash("A book with this title already exists, please try another or edit it", "warning");
        } else {
            $query = "INSERT INTO `IT202_S24_BOOKS` (title, page_count, series_name, language, summary, is_api) VALUES (:title, :page_count, :series_name, :language, :summary, :is_api)";
            $params = [
                ":title" => $title,
                ":page_count" => $page_count,
                ":series_name" => $series_name,
                ":language" => $language,
                ":summary" => $summary,
                ":is_api" => 0
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
