<?php
$table_query = "CREATE TABLE IF NOT EXISTS `".${ROOX_PLUGIN . '_modules_table'}."` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
    `users_id` text COLLATE utf8mb4_general_ci NOT NULL,
    `groups_id` text COLLATE utf8mb4_general_ci NOT NULL,        
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_name_key` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
db_query($table_query);

$table_query = "CREATE TABLE IF NOT EXISTS `{$dictionary_table}` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `dict_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
    `dict_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_dict_key` (`dict_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
db_query($table_query);
db_query("
    INSERT IGNORE INTO `{$dictionary_table}` (`dict_key`, `dict_value`) VALUES
    ('TEXT_ROOX_INFO', 'Roox is a Rukovoditel plugin that gives additional feature to your application for some specific purposes. Modules can be installed separately with or without any interdependency.'),
    ('TEXT_VERSION', 'Version'),
    ('TEXT_INSTALLED_MODULES', 'Installed Modules'),
    ('TEXT_FIND_MODULES', 'Find Roox modules <a href=\"https://github.com/eddydeniro\">here</a>'),
    ('TEXT_MODULE_INSTALLER', 'Module Installer'),
    ('TEXT_MODULE_INSTALLER_INFO', 'To install a module, just download the zip file from <a href=\"https://github.com/eddydeniro\">its repository</a>, and upload it here.'),
    ('TEXT_MODULE_IS_INSTALLED', 'Module is successfully installed!'),
    ('TEXT_MODULE_INSTALL_FAILED', 'Module installation failed!'),
    ('TEXT_REINSTALL', 'Reinstall'),
    ('TEXT_REINSTALL_INFO', '\"Reinstall\" will run the required table creation and data insertion of the module. However it will not replace existing ones.'),
    ('TEXT_DICTIONARY', 'Dictionary'),
    ('TEXT_DICTIONARY_INFO', 'You can add and edit dictionary entry to use as constants in your application. Once it is added, you can only edit its value.'),
    ('TEXT_KEYWORD', 'Keyword');    
");

?>