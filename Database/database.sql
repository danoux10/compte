DROP DATABASE IF EXISTS compte;
CREATE DATABASE IF NOT EXISTS compte
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

use compte;

CREATE TABLE IF NOT EXISTS users
(
    idUser     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    lastname   VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

create table if not exists iconsCategories
(
    idIconsCategories int unsigned not null primary key auto_increment,
    name              varchar(255) not null,
    type              enum('income', 'expense') not null,
    created_at        timestamp default current_timestamp,
    updated_at        timestamp default current_timestamp on update current_timestamp
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS icons
(
    idIcon          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    path            VARCHAR(255) NOT NULL,
    /*
     Chaque icône doit obligatoirement
     appartenir à une catégorie.
    */
    iconCategory_id INT UNSIGNED NOT NULL,
    /* NULL = icon crée par défaut
    sinon idUser = icon ajouter par l'utilisateur */
    user_id         INT UNSIGNED NULL DEFAULT NULL,
    created_at      DATETIME          DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME          DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_icons_iconCategory
        FOREIGN KEY (iconCategory_id)
            REFERENCES iconsCategories (idIconsCategories)
            ON DELETE CASCADE,
    CONSTRAINT fk_icons_user
        FOREIGN KEY (user_id)
            REFERENCES users (idUser)
            ON DELETE CASCADE
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS categories
(
    idCategory INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255)                NOT NULL,
    color      VARCHAR(7)                  NOT NULL,
    icon       INT UNSIGNED                NOT NULL,
    type       ENUM ('income', 'expense')  NOT NULL,
    /* NULL = catégorie crée par défaut
     sinon idUser = catégorie crée par l'utilisateur */
    idUser     INT UNSIGNED                NULL     DEFAULT NULL,
    state      ENUM ('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP                            DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP                            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_icon
        FOREIGN KEY (icon)
            REFERENCES icons (idIcon)
            ON DELETE CASCADE,
    CONSTRAINT fk_categories_user
        FOREIGN KEY (idUser)
            REFERENCES users (idUser)
            ON DELETE CASCADE
) ENGINE = InnoDB;