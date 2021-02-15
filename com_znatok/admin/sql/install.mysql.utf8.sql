/*
 * @package     Znatok Package
 * @subpackage  com_znatok
 * @version     __DEPLOY_VERSION__
 * @author      Delo Design - delo-design.ru
 * @copyright   Copyright (c) 2021 Delo Design. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://delo-design.ru/
 */

CREATE TABLE IF NOT EXISTS `#__znatok_urls`
(
    `id`       int(11)    NOT NULL AUTO_INCREMENT,
    `url`      text       NOT NULL,
    `data`     mediumtext NOT NULL,
    `created`  datetime   NOT NULL,
    `modified` datetime   NOT NULL,
    PRIMARY KEY `id` (`id`),
    KEY `idx_created` (`created`),
    KEY `idx_modified` (`modified`)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    DEFAULT COLLATE = utf8mb4_unicode_ci
    AUTO_INCREMENT = 0;