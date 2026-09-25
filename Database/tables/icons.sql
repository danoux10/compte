CREATE TABLE IF NOT EXISTS icons
(
    idIcon   INT  UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100) NOT NULL,
    path     VARCHAR(255) NOT NULL,
    /* NULL = icon crée par défaut
    sinon idUser = icon ajouter par l'utilisateur */
    user_id  INT UNSIGNED  NULL DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_icons_user
        FOREIGN KEY (user_id)
            REFERENCES users (idUser)
            ON DELETE CASCADE
)ENGINE = InnoDB;