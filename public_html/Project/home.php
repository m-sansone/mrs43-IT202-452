<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<div class="container-fluid">
    <h2>Home</h2>
</div>

<?php

if (is_logged_in(true)) {
    //comment this out if you don't want to see the session variables
    error_log("Session data: " . var_export($_SESSION, true));
}
?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>