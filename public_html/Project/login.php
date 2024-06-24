<?php
require_once(__DIR__ . "/../../partials/nav.php");
?>
<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <input type="submit" value="Login" />
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
 if (isset($_POST["email"]) && isset($_POST["password"])) {
    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
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
    if (empty($password)) {
        flash("Password must be provided <br>");
        $hasError = true;
    }
    if (strlen($password) < 8) {
        flash("Password must be at least 8 characters long <br>");
        $hasError = true;
    }
    
    if (!$hasError) {
        //TODO 4
        $db = getDB();
        $stmt = $db->prepare("SELECT email, password from Users where email = :email");
        try {
            $r = $stmt->execute([":email" => $email]);
            if ($r) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $hash = $user["password"];
                    unset($user["password"]);
                    if (password_verify($password, $hash)) {
                        flash("Welcome $email");
                        $_SESSION["user"] = $user;
                        die(header("Location: home.php"));
                    } else {
                        flash("Invalid password");
                    }
                } else {
                    flash("Email not found");
                }
            }
        } catch (Exception $e) {
            flash("<pre>" . var_export($e, true) . "</pre>");
        }
    }
 }
?>
<?php require_once(__DIR__."/../../partials/flash.php");