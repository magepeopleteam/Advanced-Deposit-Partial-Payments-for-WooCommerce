<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if(!class_exists('Mepp_Admin_Menu')) {
    class Mepp_Admin_Menu
    {
        protected $menu_title;

        public function __construct()
        {
            $this->menu_title = 'Mage Partial';

            add_action('admin_init', array($this, 'init'));
        }

        public function init()
        {
            add_menu_page( $this->menu_title, $this->menu_title, 'manage_options', 'mage-partial', array($this, 'display') );
        }
    }
    
    new Mepp_Admin_Menu;
}