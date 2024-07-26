<?php
function fetch_quote($title){
    $data = ["title" => $title];
    $endpoint = "https://book-finder1.p.rapidapi.com/api/search";
    $isRapidAPI = true;
    $rapidAPIHost = "book-finder1.p.rapidapi.com";
    $result = get($endpoint, "BOOK_API_KEY", $data, $isRapidAPI, $rapidAPIHost);
    
    if (se($result, "status", 400, false) == 200 && isset($result["response"])) {
        $result = json_decode($result["response"], true);
    } else {
        $result = [];
    }

    $books = [];
    if (isset($result["results"]) && is_array($result["results"])) {
        foreach ($result["results"] as $book) {
            $page_count = isset($book["pageCount"]) && is_numeric($book["pageCount"]) ? intval($book["pageCount"]) : NULL;

            $books[] = [
                "title" => $book["title"] ?? "",
                "authors" => isset($book["authors"]) ? json_encode($book["authors"]) : "Unknown",
                "categories" => isset($book["categories"]) ? json_encode($book["categories"]) : "N/A",
                "page_count" => $page_count,
                "series_name" => $book["series_name"] ?? "N/A",
                "language" => $book["language"] ?? "N/A",
                "summary" => $book["description"] ?? "No description available"
            ];
        }
    }
    
    return $books;
}

?>