CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('admin','customer') DEFAULT 'customer',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

INSERT INTO `categories` (`name`) VALUES 
('game'), ('console'), ('accessories'), ('digital account'), ('subscription'),
('VR Headsets'), ('Controllers'), ('Racing Wheels');

CREATE TABLE `gadgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `daily_price` decimal(10,2) NOT NULL,
  `category_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
);

CREATE TABLE IF NOT EXISTS `gadget_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`gadget_id`) REFERENCES `gadgets`(`id`) ON DELETE CASCADE
);

CREATE TABLE `hardware_units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `status` enum('Available','Rented','Under Maintenance') DEFAULT 'Available',
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  FOREIGN KEY (`gadget_id`) REFERENCES `gadgets`(`id`) ON DELETE CASCADE
);

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `return_date` date NOT NULL,
  `total_days` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `status` enum('Pending','Confirmed','Dispatched','Delivered','Returned','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_id` (`booking_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE `booking_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `hardware_unit_id` int(11) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`hardware_unit_id`) REFERENCES `hardware_units`(`id`)
);

-- Default admin with plain text password
INSERT INTO `users` (`role`, `name`, `email`, `phone`, `password`, `is_verified`) 
VALUES ('admin', 'Admin', 'admin@admin.com', '1234567890', 'password', 1);
