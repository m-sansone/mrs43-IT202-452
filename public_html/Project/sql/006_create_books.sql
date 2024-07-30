CREATE TABLE IF NOT EXISTS `IT202-S24-BOOKS` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `page_count` INT,
    `series_name` VARCHAR(255) DEFAULT NULL,
    `language` VARCHAR(25) NOT NULL,
    `summary` VARCHAR(5000) NOT NULL,
    `cover_art_url` VARCHAR(255) DEFAULT NULL,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
