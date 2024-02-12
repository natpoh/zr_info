<?php

namespace OpenApi\Fd;

/**
 * @author brahman
 */
class Credentials extends \AbstractDBFda {

    private $db;
    private $default_key = 1;
    
    public function __construct() {

        $this->db = array(
            'key' => 'api_key',
            'ip_restrictions' => 'api_ip_restrictions',
            'site_restrictions' => '	api_site_restrictions',
            'settings' => 'api_settings',
            'tariff' => 'api_tariff',
            'trans_log' => 'api_trans_log',
        );
    }
    
    public function validateApiKey($query_args, $update_log=true) {
        // If no key, set default key        
        $key_name = htmlspecialchars(strip_tags($query_args['api_key']));
        if (!$key_name){
            $key_data = $this->get_key($this->default_key);
        } else {        
            $key_data = $this->get_key_data($key_name);
        }
        
        if ($key_data){
            // TODO Check restrictions
            if ($update_log){
                // Update key data to db
                
            }
            return 1;
        } 
        return 0;               
    }
    
    public function get_key_data($key_name) {
        $sql = sprintf("SELECT * FROM {$this->db['key']} WHERE apikey = '%s'", $key_name);
        $results = $this->db_fetch_row($sql);
        return $results;
    }
    
        public function get_key($id) {
        $sql = sprintf("SELECT * FROM {$this->db['key']} WHERE id = %d", $id);
        $results = $this->db_fetch_row($sql);
        return $results;
    }
}
