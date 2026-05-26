CREATE DATABASE IF NOT EXISTS `absensi_piket`;
USE `absensi_piket`;

CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin` (`email`, `password`) VALUES ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') ON DUPLICATE KEY UPDATE `email`=`email`;

CREATE TABLE IF NOT EXISTS `siswa` (
  `nisn` VARCHAR(20) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `kelas` VARCHAR(50) NOT NULL,
  `hari_piket` VARCHAR(20) NOT NULL DEFAULT '',
  `rfid_uid` VARCHAR(100) NOT NULL,
  `foto_profil` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`nisn`),
  UNIQUE KEY `rfid_uid` (`rfid_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `absensi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nisn` VARCHAR(20) NOT NULL,
  `hari` VARCHAR(20) NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam` TIME NOT NULL,
  `foto` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`nisn`) REFERENCES `siswa` (`nisn`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
