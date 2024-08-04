CREATE TABLE IF NOT EXISTS `IT202-S24-UserBooks`(
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` int,
    `book_id` int,
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`),
    FOREIGN KEY (`book_id`) REFERENCES `IT202-S24-BOOKS` (`id`),
    UNIQUE KEY `unique_title` (`user_id`, `book_id`)
)