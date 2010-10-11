<?php

/**
 * Configuration Management API
 *
 * @package Cotonti
 * @version 0.7.0
 * @author Trustmaster
 * @copyright Copyright (c) Cotonti Team 2010
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

/**
 * Generic text configuration. Is displayed as textarea. Contains text.
 * Is used by default.
 */
define('COT_CONFIG_TYPE_TEXT', 0);
/**
 * A string, max length is 255 chars. Is displayed as a single line of input.
 * The list of variants is ignored for this type.
 */
define('COT_CONFIG_TYPE_STRING', 1);
/**
 * Selection from the list of possible variants. Is displayed as a dropdown.
 */
define('COT_CONFIG_TYPE_SELECT', 2);
/**
 * Radio yes/no selection.
 */
define('COT_CONFIG_TYPE_RADIO', 3);
/**
 * Callback function type
 */
define('COT_CONFIG_TYPE_CALLBACK', 4);
/**
 * Hidden config. It is actually a text string, but it is not displayed anywhere
 */
define('COT_CONFIG_TYPE_HIDDEN', 5);

/**
 * Registers a set of configuration entries at once.
 *
 * Example:
 * <code>
 * $config_options = array(
 *     array(
 *         'name' => 'disable_test',
 *         'type' => COT_CONFIG_TYPE_RADIO,
 *         'default' => '0'
 *     ),
 *     array(
 *         'name' => 'test_selection',
 *         'type' => COT_CONFIG_TYPE_SELECT,
 *         'default' => '20',
 *         'variants' => '5,10,15,20,25,30,35,40,50'
 *     ),
 *     array(
 *         'name' => 'test_value',
 *         'type' => COT_CONFIG_TYPE_STRING,
 *         'default' => 'something'
 *     ),
 *     array(
 *         'name' => 'not_visible',
 *         'type' => COT_CONFIG_TYPE_HIDDEN,
 *         'default' => 'test23'
 *     )
 * );
 *
 * cot_config_add('test', $config_options, true);
 * </code>
 *
 * @param string $name Extension name (code)
 * @param array $options An associative array of configuration entries.
 * Each entry of the arrray has the following keys:
 * 'name' => Option name, alphanumeric and _. Must be unique for a module/plugin
 * 'type' => Option type, see COT_CONFIG_TYPE_* constants
 * 'default' => Default and initial value, by default is an empty string
 * 'variants' => A comma separated (without spaces) list of possible values,
 * 		only for SELECT options.
 * 'order' => A string that determines position of the option in the list,
 * 		e.g. '04'. Or will be assigned automatically if omitted
 * 'text' => Textual description. It is usually omitted and stored in langfiles
 * @param bool $is_module Flag indicating if it is module or plugin config
 * @return bool Operation status
 */
function cot_config_add($name, $options, $is_module = false)
{
    global $cfg, $db_config;
    $cnt = count($options);
    $type = $is_module ? 'module' : 'plug';
    // Check the arguments
    if (!$cnt)
    {
        return false;
    }
    // Build the SQL query
    $query = "INSERT INTO `$db_config` (`config_owner`, `config_cat`,
		`config_order`, `config_name`, `config_type`, `config_value`,
		`config_default`, `config_variants`, `config_text`) VALUES ";
    for ($i = 0; $i < $cnt; $i++)
    {
        if ($i > 0)
		{
            $query .= ',';
		}
        $order = isset($options[$i]['order'])
			? cot_db_prep($options[$i]['order'])
			: str_pad($i, 2, 0, STR_PAD_LEFT);
        $query .= "('$type', '$name', '$order', '"
			. cot_db_prep($options[$i]['name']) . "', "
			. (int) $options[$i]['type'] . ", '"
			. cot_db_prep($options[$i]['default']) . "', '"
            . cot_db_prep($options[$i]['default']) . "', '"
			. cot_db_prep($options[$i]['variants']) . "', '"
            . cot_db_prep($options[$i]['text']) . "')";
    }
    cot_db_query($query);
    return cot_db_affectedrows() == $cnt;
}

/**
 * Loads config structure from database into an array
 * @param string $name Extension code
 * @param bool $is_module TRUE if module, FALSE if plugin
 * @return array Config options structure
 * @see cot_config_add()
 */
function cot_config_load($name, $is_module = false)
{
	global $db_config;
	$options = array();
	$type = $is_module ? 'module' : 'plug';

	$res = cot_db_query("SELECT config_name, config_type, config_value,
			config_default, config_variants, config_order
		FROM $db_config WHERE config_owner = '$type' AND config_cat = '$name'");
	while ($row = cot_db_fetchassoc($res))
	{
		$options[] = array(
			'name' => $row['config_name'],
			'type' => $row['config_type'],
			'order' => $row['config_order'],
			'value' => $row['config_value'],
			'default' => $row['config_default'],
			'variants' => $row['config_variants']
		);
	}
	cot_db_freeresult($res);

	return $options;
}

/**
 * Updates config map properties in the database for given options
 *
 * @param string $name Extension code
 * @param array $options Configuration entries
 * @param bool $is_module TRUE if module, FALSE if plugin
 * @return int Number of entries updated
 */
function cot_config_modify($name, $options, $is_module = false)
{
	global $db_config;
	$type = $is_module ? 'module' : 'plug';
	$affected = 0;

	foreach ($options as $opt)
	{
		$config_name = $opt['name'];
		unset($opt['name']);
		$affected += cot_db_update($db_config, $opt, "config_owner = '$type'
			AND config_cat = '$name' AND config_name = '$config_name'", 'config_');
	}

	return $affected;
}

/**
 * Parses array of setup file configuration entries into array representation
 *
 * @param array $info_cfg Setup file config entries
 * @return array Config options
 */
function cot_config_parse($info_cfg)
{
    $options = array();
	if (is_array($info_cfg))
	{
		foreach ($info_cfg as $i => $x)
		{
			$line = explode(':', $x);
			if (is_array($line) && !empty($line[1]) && !empty($i))
			{
				switch ($line[1])
				{
					case 'string':
						$line['Type'] = COT_CONFIG_TYPE_STRING;
						break;
					case 'select':
						$line['Type'] = COT_CONFIG_TYPE_SELECT;
						break;
					case 'radio':
						$line['Type'] = COT_CONFIG_TYPE_RADIO;
						break;
					case 'hidden':
						$line['Type'] = COT_CONFIG_TYPE_HIDDEN;
						break;
					default:
						$line['Type'] = COT_CONFIG_TYPE_TEXT;
						break;
				}
				$options[] = array(
					'name' => $i,
					'order' => $line[0],
					'type' => $line['Type'],
					'variants' => $line[2],
					'default' => $line[3],
					'text' => $line[4]
				);
			}
		}
	}
    return $options;
}

/**
 * Unregisters configuration option(s).
 *
 * @param string $name Extension name (code)
 * @param bool $is_module Flag indicating if it is module or plugin config
 * @param mixed $option String name of a single configuration option.
 * Or pass an array of option names to remove them at once. If empty or omitted,
 * all options from selected module/plugin will be removed
 * @return int Number of options actually removed
 */
function cot_config_remove($name, $is_module = false, $option = '')
{
    global $db_config;
    $type = $is_module ? 'module' : 'plug';
    $where = "config_owner = '$type' AND config_cat = '$name'";
    if (is_array($option))
    {
        $cnt = count($option);
        if ($cnt == 1)
        {
            $option = $option[0];
        }
        else
        {
            $where .= " AND config_name IN (";
            for ($i = 0; $i < 0; $i++)
            {
                if ($i > 0)
                    $where .= ',';
                $where .= "'" . cot_db_prep($option[$i]) . "'";
            }
            unset($option);
        }
    }
    if (!empty($option))
    {
        $where .= " AND config_name = '" . cot_db_prep($option) . "'";
    }
    return cot_db_delete($db_config, $where);
}

/**
 * Updates configuration values
 *
 * Example:
 * <code>
 * $config_values = array(
 *     'disable_test' => '0',
 *     'hidden_test' => 'test45',
 * );
 *
 * cot_config_set($config_values, 'test', true);
 * </code>
 *
 * @param string $name Extension name config belongs to
 * @param array $options Array of options as 'option name' => 'option value'
 * @param bool $is_module Flag indicating if it is module or plugin config
 * @return int Number of entries updated
 */
function cot_config_set($name, $options, $is_module = false)
{
    global $db_config;
    $type = $is_module ? 'module' : 'plug';
    $upd_cnt = 0;
    foreach ($options as $key => $val)
    {
        $where = "config_owner = '$type' AND config_name = '"
			. cot_db_prep($key) . "'";
        if (!empty($name))
            $where .= " AND config_cat = '$name'";
        $upd_cnt += cot_db_update($db_config, $where, array('value' => $val),
			'config_');
    }
    return $upd_cnt;
}

/**
 * Updates existing configuration map removing obosolete options, adding new
 * options and tweaking options which need to be updated.
 *
 * @param string $name Extension code
 * @param array $options Configuration options
 * @param bool $is_module TRUE for modules, FALSE for plugins
 * @return int Number of entries affected
 */
function cot_config_update($name, $options, $is_module = false)
{
	$affected = 0;
	$old_options = cot_config_load($name, $is_module);

	// Find and remove options which no longer exist
	$remove_opts = array();
	foreach ($old_options as $old_opt)
	{
		$keep = false;
		foreach ($options as $opt)
		{
			if ($opt['name'] == $old_opt['name'])
			{
				$keep = true;
				break;
			}
		}
		if (!$keep)
		{
			$remove_opts[] = $old_opt['name'];
		}
	}
	if (count($remove_opts) > 0)
	{
		$affected += cot_config_remove($name, $is_module, $remove_opts);
	}

	// Find new options and options which have been modified
	$new_options = array();
	$upd_options = array();
	foreach ($options as $opt)
	{
		$existed = false;
		foreach ($old_options as $old_opt)
		{
			if ($opt['name'] == $old_opt['name'])
			{
				$changed = array_diff($opt, $old_opt);
				foreach ($changed as $key => $val)
				{
					if ($key != 'value')
					{
						// Values for modified options are set to default
						$opt['value'] = $opt['default'];
						$upd_options[] = $opt;
					}
				}
				$existed = true;
				break;
			}
		}
		if (!$existed)
		{
			$new_options[] = $opt;
		}
	}
	if (count($new_options) > 0)
	{
		$affected += cot_config_add($name, $new_options, $is_module);
	}
	if (count($upd_options) > 0)
	{
		$affected += cot_config_modify($name, $upd_options, $is_module);
	}

	return $affected;
}
?>