<?php

function pageDifinitions() {
    return array(
        'fawrycallback' => array(
            'title' => __('fawrycallback', 'fawry_pay'),
            'content' => ''
        ),
    );
}

function my_faw_activate() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'my_fawry_callback';
    $wpdb_collate = $wpdb->collate;
    $createSQL = "CREATE TABLE {$table_name} (
         `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`date_called` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`data_rec`  TEXT NOT NULL ,
	PRIMARY KEY (`id`)
        )
         COLLATE {$wpdb_collate}";
    //echo $createSQL;die();
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    dbDelta($createSQL);


    flush_rewrite_rules(); //automatic flushing of the WordPress rewrite rules for cpt
     //activate the schedule
    wp_schedule_event(time(), 'hourly', 'woocommerce_cancel_unpaid_submitted');
    //run immediate with http://localhost/wpshop/wp-cron.php?doing_wp_cron
}

function my_faw_deactivate() {
    //remove the schedule
    wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_submitted' );
}
