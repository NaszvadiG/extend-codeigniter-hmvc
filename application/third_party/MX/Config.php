<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Config class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Config.php
 *
 * @copyright	Copyright (c) 2011 Wiredesignz, 2015 cconnect
 *
 * function load_db_items, save_db_item, remove_db_item, create_table
 * @author Neil Strey
 *
 * @version 	5.4
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Config extends CI_Config 
{	
    /**
     * CodeIgniter instance
     *
     * @var object
     */
    private $CI = NULL;

    /**
     * Database table name
     *
     * @var string
     */
    private $table = 'settings';
    
    public function load($file = 'config', $use_sections = FALSE, $fail_gracefully = FALSE, $_module = '') {
		
    	if (in_array($file, $this->is_loaded, TRUE)) return $this->item($file);

	$_module OR $_module = CI::$APP->router->fetch_module();
	list($path, $file) = Modules::find($file, $_module, 'config/');
		
	if ($path === FALSE) {
		parent::load($file, $use_sections, $fail_gracefully);					
		return $this->item($file);
	}  
	
	if ($config = Modules::load_file($file, $path, 'config')) {
		
		/* reference to the config array */
		$current_config =& $this->config;

		if ($use_sections === TRUE)	{
			
			if (isset($current_config[$file])) {
				$current_config[$file] = array_merge($current_config[$file], $config);
			} else {
				$current_config[$file] = $config;
			}
			
		} else {
			$current_config = array_merge($current_config, $config);
		}
		$this->is_loaded[] = $file;
		unset($config);
		return $this->item($file);
	}
     }
        
     /**
     * Load config items from database
     *
     * @return void
     */
    public function load_db_items()
    {
        if (is_null($this->CI)) $this->CI = get_instance();

        if (!$this->CI->db->table_exists($this->table))
        {
           $this->create_table();
        }
        $this->CI->db->cache_delete('booking', 'send');
        $query = $this->CI->db->get($this->table);

        foreach ($query->result() as $row)
        {
            $this->set_item($row->key, $row->value);
        }

    }

    /**
     * Save config item to database
     *
     * @return bool
     * @param string $key
     * @param string $value
     */
    public function save_db_item($key, $value)
    {
        if (is_null($this->CI)) $this->CI = get_instance();

        $where = array('key' => $key);
        $found = $this->CI->db->get_where($this->table, $where, 1);

        if ($found->num_rows > 0)
        {
            return $this->CI->db->update($this->table, array('value' => $value), $where);
        }
        else
        {
            return $this->CI->db->insert($this->table, array('key' => $key, 'value' => $value));
        }
    }

    /**
     * Remove config item from database
     *
     * @return bool
     * @param string $key
     */
    public function remove_db_item($key)
    {
        if (is_null($this->CI)) $this->CI = get_instance();

        return $this->CI->db->delete($this->table, array('key' => $key));
    }

    /**
     * Create database table (using "IF NOT EXISTS")
     *
     * @return void
     */
    public function create_table()
    {
        if (is_null($this->CI)) $this->CI = get_instance();

        $this->CI->load->dbforge();

        $this->CI->dbforge->add_field("`id` int(11) NOT NULL auto_increment");
        $this->CI->dbforge->add_field("`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP");
        $this->CI->dbforge->add_field("`key` varchar(255) NOT NULL");
        $this->CI->dbforge->add_field("`value` text NOT NULL");

        $this->CI->dbforge->add_key('id', TRUE);

        $this->CI->dbforge->create_table($this->table, TRUE);
    }
}
