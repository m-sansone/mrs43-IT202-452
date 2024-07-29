CREATE TABLE `IT202-S24-Libraries`(
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `reader_id` int DEFAULT NULL,
    `title` varchar(255) NOT NULL,
    `books` int DEFAULT '1',
    `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_title` (`reader_id`, `title`),
    FOREIGN KEY (`reader_id`) REFERENCES `Users` (`id`),
    CONSTRAINT `book_min` CHECK ((`books` > 0))
)