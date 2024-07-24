<?php
require(__DIR__ . "/../../partials/nav.php");

$result = [];
if (isset($_GET["symbol"])) {
    //function=GLOBAL_QUOTE&symbol=MSFT&datatype=json
    $data = ["q" => "sandwich","type" => "public", "beta" => "true", "random" => "True"];
    $endpoint = "https://edamam-recipe-search.p.rapidapi.com/api/recipes/v2";
    $isRapidAPI = true;
    $rapidAPIHost = "edamam-recipe-search.p.rapidapi.com";
    $result = get($endpoint, "RECIPE_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    //example of cached data to save the quotas, don't forget to comment out the get() if using the cached data for testing
    /* $result = ["status" => 200, "response" => '{
    "Global Quote": {
        "01. symbol": "MSFT",
        "02. open": "420.1100",
        "03. high": "422.3800",
        "04. low": "417.8400",
        "05. price": "421.4400",
        "06. volume": "17861855",
        "07. latest trading day": "2024-04-02",
        "08. previous close": "424.5700",
        "09. change": "-3.1300",
        "10. change percent": "-0.7372%"
    }
}'];*/
    error_log("Response: " . var_export($result, true));
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }
}

$quote = $result["sandwich"];
$quote = array_reduce(
    array_keys($quote),
    function($temp, $key) use ($quote){
        $k = explode(" ", $key)[1];
        $temp[$key] = $quote[$key];
        return $temp;
    }
);
$result = [$quote];

$db = getDB();
$query = "INSERT INTO `IT202_S24_RECIPES` ";
$columns = [];
$params = [];
//per record
foreach($quote as $k=>$v){
    array_push($columns, "`$k`");
    array_push($params, [":$k"=>$v]);
}
$query .= "(" . join(",", $columns) . ")";
$query .= "VALUES (" . join(",", array_keys($params)) . ")";

$db->prepare();

?>
<div class="container-fluid">
    <h1>Stock Info</h1>
    <p>Remember, we typically won't be frequently calling live data from our API, this is merely a quick sample. We'll want to cache data in our DB to save on API quota.</p>
    <form>
        <div>
            <label>Symbol</label>
            <input name="symbol" />
            <input type="submit" value="Fetch Stock" />
        </div>
    </form>
    <div class="row ">
        <?php if (isset($result)) : ?>
            <?php foreach ($result as $stock) : ?>
                <pre>
                    <?php var_export($stock);?>
                </pre>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php
require(__DIR__ . "/../../partials/flash.php");