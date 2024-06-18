<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required />
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

        if(!email){//checking if empty
            flash("Email must not be empty","warning");
            isValid = false;
        }
        if(!isValidEmail(email)){
            flash("Invalid email address", "warning");
            isValid = false;
        }
        if(!username){//Checking if empty this might need to be applied server-side as well
            flash("Username must not be empty","warning");
            isValid = false;
        }
        if(!isValidUsername(username)){
            flash("Username must only contain 3-16 characters a-z, 0-9, _, or -","warning");
            isValid = false;
        }
        if(!password){//checking if empty
            flash("Password must not be empty","warning");
            isValid = false;
        }
        if(!confirm){//checking if empty
            flash("Confirm password must not be empty","warning");
            isValid = false;
        }
        if(!isValidPassword(password)){
            flash("Password must be a minimum of eight characters", "warning");
            isValid = false;
        }
        if(password && !isEqual(password,confirm)){
            flash("Passwords must match", "warning");
            isValid = false;
        }

        return true;
    }
</script>
<?php
 //TODO 2: add PHP Code
 if(isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"])){
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];
    //TODO 3: validate/use
    
 }
?>