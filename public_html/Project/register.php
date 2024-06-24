<?php
require_once(__DIR__ . "/../../partials/nav.php");
?>
<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required />
    </div>
    <div>
        <label for="username">Username</label>
        <input id="username" type="text" name="username" required maxlength="30"/>
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <div>
        <label for="confirm">Confirm</label>
        <input type="password" name="confirm" required minlength="8" />
    </div>
    <input type="submit" value="Register" />
</form>
<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation
        //ensure it returns false for an error and true for success
        let isValid = true;
        
        let email = form.email.value;
        let username = form.username.value;
        let password = form.password.value;
        let confirm = form.confirm.value;

        if(!email){
            isValid = false;
        }
        if(!isValidEmail(email)){
            isValid = false;
        }
        if(!username){
            isValid = false;
        }
        if(!isValidUsername(username)){
            isValid = false;
        }
        if(!password){
            isValid = false;
        }
        if(!confirm){
            isValid = false;
        }
        if(!isValidPassword(password)){
            isValid = false;
        }
        if(password && !isEqual(password,confirm)){
            isValid = false;
        }

        return true;
    }
</script>
<?php
 //TODO 2: add PHP Code
 if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"]) && isset($_POST["username"])) {
    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    $confirm = se($_POST, "confirm", "", false);
    $username = se($_POST, "username", "", false);
    $hasError = false;
    if (empty($email)) {
        flash("Email must not be empty");
        $hasError = true;
    }
    //sanitize
    //$email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $email = sanitize_email($email);
    //validate
    /*if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash("Please enter a valid email <br>");
        $hasError = true;
    }*/
    if(!is_valid_email($email)){
        flash("Please enter a valid email <br>");
        $hasError = true;
    }
    if(!preg_match('/^[a-z0-9_-]{3,30}$/', $username)){
        flash("Username must only contain lower case letters, numbers, hyphens, and/or underscores and be between 3-30 characters <br>");
        $hasError = true;
    }
    if (empty($password)) {
        flash("Password must be provided <br>");
        $hasError = true;
    }
    if (empty($confirm)) {
        flash("Confirm Password must not be empty");
        $hasError = true;
    }
    if (strlen($password) < 8) {
        flash("Password must be >8 characters");
        $hasError = true;
    }
    if (strlen($password) > 0 && $password !== $confirm) {
        flash("Passwords must match");
        $hasError = true;
    }
    if (!$hasError) {
        //TODO 4
        //flash("Welcome, $email");
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES(:email, :password, :username)");
        try {
            $stmt->execute([":email" => $email, ":password" => $hash, ":username" => $username]);
            flash("Successfully registered!");
        } catch (Exception $e) {
            flash("There was a problem registering");
            flash("<pre>" . var_export($e, true) . "</pre>");
        }
    }
 }
?>
<?php require_once(__DIR__."/../../partials/flash.php");