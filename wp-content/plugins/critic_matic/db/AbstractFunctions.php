<?php

/*
 * Get and set functions for Abstract db
 */

/**
 * Description of AbstractPdo
 *
 * @author brahman
 */
class AbstractFunctions {

    public function link_hash($link) {
        $link = preg_replace('/^http(?:s|)\:\/\//', '', $link);
        return sha1($link);
    }

    public function getInsertId($id, $from) {
        $sql = "SELECT $id FROM $from ORDER BY $id DESC limit 1";
        $ret = $this->db_get_var($sql);
        return $ret;
    }

    public function fieldExist($field, $id, $from) {
        $sql = "SELECT $field FROM $from WHERE $field=$id limit 1";
        $ret = $this->db_get_var($sql);
        return $ret;
    }

    public function sync_insert_data($data, $db, $sync_client = true, $sync_data = true, $priority = 5) {
        $update = false;
        $id = 0;

        if ($sync_client) {
            // Client mode
            // Get id
            $id = $this->get_remote_id($db);
            if (!$id) {
                $ex_msg = 'Can not get id from the Server. Table:' . $db;
                throw new Exception($ex_msg);
                exit;
            }
            // Field exist
            if ($this->fieldExist('id', $id, $db)) {
                $update = true;
                $this->db_update($data, $db, $id);
            }
            $data['id'] = (int) $id;
        }

        if (!$update) {
            $last_id = $this->getInsertId('id', $db);
            $this->db_insert($data, $db);
            $id = $this->getInsertId('id', $db);
            if ($id == $last_id) {
                // Insert error
                $id = 0;
            }
        }

        if ($id && $sync_data) {
            $this->create_commit_update($id, $db, $priority);
        }

        return $id;
    }

    public function sync_update_data($data, $id, $db, $sync_data = true, $priority = 5) {

        $this->db_update($data, $db, $id);

        if ($sync_data) {
            $this->create_commit_update($id, $db, $priority);
        }

        return $id;
    }

    public function sync_delete_multi($request, $db, $sync_data = true, $priority = 5) {
        $fileds = array();
        foreach ($request as $key => $value) {
            $fileds[]="$key=$value";
        }

        $sql = "DELETE FROM {$db} WHERE ". implode(' AND ', $fileds);
  
        $this->db_query($sql);

        if ($sync_data) {
            $this->create_commit_delete_multi($request, $db, $priority);
        }

    }
    
    public function sync_delete_data($id, $db, $sync_data = true, $priority = 5, $table_row='id') {

        $sql = sprintf("DELETE FROM {$db} WHERE `%s` = %d", $table_row, (int) $id);
        $this->db_query($sql);

        if ($sync_data) {
            $this->create_commit_delete($id, $db, $priority, $table_row);
        }

        return $id;
    }

    // Sync
    public function get_remote_id($db = '') {

        if (!class_exists('Import')) {
            include ABSPATH . "analysis/export/import_db.php";
        }

        $array = array('table' => $db, 'column' => 'id');
        $id_array = Import::get_remote_id($array);
        $rid = $id_array['id'];

        return $rid;
    }

    public function create_commit_update($id = 0, $db = '', $priority = 6) {
        if (!class_exists('Import')) {
            include ABSPATH . "analysis/export/import_db.php";
        }
        $request = array('id' => $id);
        $commit_id = Import::create_commit($commit_id, 'update', $db, $request, $db, $priority);
        return $commit_id;
    }

    public function create_commit_delete($id = 0, $db = '', $priority = 10, $table_row='id') {
        if (!class_exists('Import')) {
            include ABSPATH . "analysis/export/import_db.php";
        }
        $skip='';
        if ($table_row != 'id') {
            $skip = ['skip' => ['id']];
        }
        $request = array($table_row => $id);
        $commit_id = Import::create_commit('', 'delete', $db, $request, 'delete_'.$db, $priority, $skip);
        return $commit_id;
    }
    
    
    public function create_commit_delete_multi($request = array(), $db = '', $priority = 10) {
        if (!class_exists('Import')) {
            include ABSPATH . "analysis/export/import_db.php";
        }
        $skip = ['skip' => ['id']]; 
        $commit_id = Import::create_commit('', 'delete', $db, $request, 'delete_'.$db, $priority, $skip);
        return $commit_id;
    }

    public function get_option($option, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$option])) {
                return $dict[$option];
            }
        }

        $data = '';

        // New api 02.09.2022
        $sql = sprintf("SELECT val FROM options WHERE type = '%s'", $option);
        $data = Pdo_an::db_get_var($sql);
        if ($data) {
            if ($this->is_serialized($data)) { // Don't attempt to unserialize data that wasn't serialized going in.
                $data = unserialize(trim($data));
            }
        } else {
            // Old api
            if (function_exists('get_option')) {
                // Wp get option
                $data = get_option($option);
            } else {
                // Front get option
                $sql = sprintf("SELECT option_value FROM " . DB_PREFIX_WP . "options WHERE option_name = '%s'", $option);
                $data = Pdo_wp::db_get_var($sql);
                if ($data) {
                    if ($this->is_serialized($data)) { // Don't attempt to unserialize data that wasn't serialized going in.
                        $data = unserialize(trim($data));
                    }
                }
            }
            if ($data) {
                // Add to new api
                $this->update_option($option, $data);
            }
        }

        if ($cache) {
            $dict[$option] = $data;
        }
        return $data;
    }

    public function update_option($option, $value, $autoload = null) {
        // New api 02.09.2022
        $sql = sprintf("SELECT id FROM options WHERE type = '%s'", $option);
        $option_id = Pdo_an::db_get_var($sql);
        $serialized_value = $this->maybe_serialize($value);

        $data = array(
            'type' => $option,
            'val' => $serialized_value,
        );

        if ($option_id) {
            Pdo_an::db_update($data, "options", $option_id);
        } else {
            Pdo_an::db_insert($data, "options");
        }

        // Old api
        $update_old_db = false;

        if ($update_old_db) {
            if (function_exists('update_option')) {
                // Wp update option
                update_option($option, $value, $autoload);
            } else {

                $sql = sprintf("SELECT option_id FROM " . DB_PREFIX_WP . "options WHERE option_name = '%s'", $option);
                $option_id = Pdo_wp::db_get_var($sql);
                $serialized_value = $this->maybe_serialize($value);

                $data = array(
                    'option_name' => $option,
                    'option_value' => $serialized_value,
                );

                if ($option_id) {
                    Pdo_wp::db_update($data, DB_PREFIX_WP . "options", $option_id, "option_id");
                } else {
                    Pdo_wp::db_insert($data, DB_PREFIX_WP . "options");
                }
            }
        }
    }

    /**
     * Serialize data, if needed.
     *
     * @since 2.0.5
     *
     * @param string|array|object $data Data that might be serialized.
     * @return mixed A scalar data.
     */
    function maybe_serialize($data) {
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }

        /*
         * Double serialization is required for backward compatibility.
         * See https://core.trac.wordpress.org/ticket/12930
         * Also the world will end. See WP 3.6.1.
         */
        if ($this->is_serialized($data, false)) {
            return serialize($data);
        }

        return $data;
    }

    /**
     * Check value to find if it was serialized.
     *
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @since 2.0.5
     *
     * @param string $data   Value to check to see if was serialized.
     * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
     * @return bool False if not serialized and true if it was.
     */
    function is_serialized($data, $strict = true) {
        // If it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            // Or else fall through.
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
        }
        return false;
    }

    public function curr_time() {

        $add_time = 0;

        $opt_time = $this->get_option('gmt_offset');
        if ($opt_time) {
            $add_time = $opt_time * 3600;
        }
        return time() + $add_time;
    }

    public function curr_date($time = '') {
        if (!$time) {
            $time = $this->curr_time();
        }
        $date = gmdate('Y-m-d H:i:s', $time);
        return $date;
    }

    function timer_start() { // if called like timer_stop(1), will echo $timetotal
        global $timestart;
        if (!$timestart) {
            $timestart = microtime(1);
        }
    }

    function timer_stop($precision = 3) {
        global $timestart;
        $mtime = microtime(1);
        $timetotal = $mtime - $timestart;
        $r = number_format($timetotal, $precision);

        return $r;
    }

    public function is_int($variable) {
        if (filter_var($variable, FILTER_VALIDATE_INT) === false) {
            return false;
        }
        return true;
    }

}

class QueryADB {

    private $query = array();

    public function __construct() {
        $this->clear();
    }

    public function add_query($key, $value) {
        $this->query[$key] = $value;
    }

    public function get_query() {
        return $this->query;
    }

    public function clear() {
        $this->query = array();
    }

}
