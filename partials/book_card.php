<?php
if (!isset($book)) {
    error_log("Using book partial without data");
    flash("Dev Alert: book called without data", "danger");
}
?>

<?php if (isset($book)) : ?>
    <div class="card mx-auto" style="width: 18rem;">
        <img src="<?php se($book, "cover_art_url", "Unknown"); ?>" class="card-img-top" alt="...">
        <div class="card-body">
            <h5 class="card-title"><?php se($book, "title", "Unknown"); ?> </h5>
            <div class="card-text">
                <ul class="list-group">
                    <li class="list-group-item">Number of Pages: <?php se($book, "page_count", "Unknown"); ?></li>
                    <li class="list-group-item">Language: <?php se($book, "language", "Unknown"); ?></li>
                </ul>

            </div>

            <?php if (!isset($book["user_id"]) || $book["user_id"] === "N/A") : ?>
                <div class="card-body">
                    <a href="<?php echo get_url('api/add_book.php?book_id=' . $book["id"]); ?>" class="card-link">Add to library</a>
                </div>
            <?php else : ?>
                <div class="card-body">
                    <div class="bg-warning text-dark text-center">book not available</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>