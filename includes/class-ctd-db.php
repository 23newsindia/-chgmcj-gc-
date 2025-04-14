<?php
class CTD_DB {
    private static $table_name = 'tshirt_discount_rules';

    public static function create_tables() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            rule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            categories TEXT NOT NULL,
            excluded_products TEXT NOT NULL,
            quantity INT NOT NULL,
            discount_price DECIMAL(10,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (rule_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Check if table was created
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            error_log('Failed to create table: ' . $table);
            return false;
        }

        return true;
    }

    public static function check_tables() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return self::create_tables();
        }
        
        return true;
    }

    public static function get_all_rules() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        
        // Ensure table exists before querying
        if (self::check_tables()) {
            return $wpdb->get_results(
                "SELECT * FROM $table 
                WHERE status = 'active' 
                ORDER BY created_at DESC"
            );
        }
        
        return [];
    }

    public static function get_rule($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        
        if (!self::check_tables()) {
            return null;
        }
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE rule_id = %d",
            $id
        ));
    }

    public static function save_rule($data) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        
        if (!self::check_tables()) {
            return false;
        }
        
        $defaults = [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        if (isset($data['rule_id'])) {
            $id = $data['rule_id'];
            unset($data['rule_id']);
            return $wpdb->update($table, $data, ['rule_id' => $id]);
        } else {
            return $wpdb->insert($table, $data);
        }
    }

    public static function delete_rule($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        
        if (!self::check_tables()) {
            return false;
        }
        
        return $wpdb->update(
            $table,
            ['status' => 'deleted'],
            ['rule_id' => $id]
        );
    }
}