<?php
/*
Plugin Name: Rätt Grafiska Custom Login Theme
Plugin URI: https://github.com/Ratt-Grafiska/
Update URI: https://github.com/Ratt-Grafiska/rg-custom-login
Description: A WordPress plugin that customizes the login page with a custom logo, theme colors, and styling.
Version: 1.0.7
Author: Johan Wistbacka
Author URI: https://wistbacka.se
License: GPL2
*/

// Initiera uppdateraren
if (!class_exists("RgGitUpdater")) {
  require_once plugin_dir_path(__FILE__) . "rg-git-updater.php";
}

require_once plugin_dir_path(__FILE__) . "rg-custom-login.php";
