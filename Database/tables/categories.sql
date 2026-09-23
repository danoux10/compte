CREATE TABLE categories
(
    idCategory INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255)                NOT NULL,
    color      VARCHAR(7)                  NOT NULL,
    icon       VARCHAR(255)                NOT NULL,
    type       ENUM ('income', 'expense')  NOT NULL,
    -- NULL = catégorie crée par défaut, sinon idUser = catégorie crée par l'utilisateur
    idUser     INT UNSIGNED                NOT NULL DEFAULT NULL,
    state      ENUM ('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP                            DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP                            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_user
        FOREIGN KEY (idUser)
            REFERENCES users (idUser)
            ON DELETE CASCADE
);