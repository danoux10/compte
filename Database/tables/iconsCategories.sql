create table if not exists iconsCategories
(
    idIconsCategories int unsigned not null primary key auto_increment,
    name              varchar(255) not null,
    type enum('income', 'expense') not null,
    created_at        timestamp default current_timestamp,
    updated_at        timestamp default current_timestamp on update current_timestamp
) ENGINE = InnoDB;
