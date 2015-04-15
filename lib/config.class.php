<?php
 /**
  * PHP INI CONFIGURATION CLASS
  *
  * This class is used to encapuslate and serve the configuration settings from a 
  * config.ini file to the script or class using it.
  *
  * PHP 5
  *
  * LICENSE: This file and it's contents are licensed under the MIT license, which is
  * available at the following URL: http://creativecommons.org/licenses/MIT/
  * Basically, you're free to do whatever you like with the code.  I just ask to leave
  * some sort of credit to the author in the re-written/derivative work.
  *
  * @category   Configuration
  * @package    PHP INI Configuration Class
  * @author     David Miles <david@amereservant.com>
  * @copyright  ©2010 David Miles
  * @license    http://creativecommons.org/licenses/MIT/ MIT License
  * @version    1.0
  * @since      File available since Release 1.0 (Apr. 02, 2010)
  * @link       http://www.webdesignparlor.com/ Web Design Parlor - Author Site
  * @link       http://github.com/amereservant/PHP-Configuration-INI-Parser-Class.git
  */
final class Config {
    
   /**
    * config.ini Data
    *
    * This property contains all of the parsed data from the config.ini file and
    * the values are called by using the {@link getParam()} method statically.
    *
    * @var      array       An array containing all of the values from the parsed
    *                       ini file.
    * @static
    * @access   private
    */
    private static $data;
    
   /**
    * Get Config Parameters
    *
    * This method is used to retrieve the functions contained in the {@link $data}
    * property.  It also sets the {@link $data} property if it hasn't already been set.
    * It is provided two parameters:
    *   section - The section of the configuration file the setting is under.
    *             Ex. [database]
    *      name - The name of the parameter.  Ex. errmode
    *
    * @param    string      $section    The name of the section in the config.ini
    *                                   you're wanting to get the setting for.
    * @param    string      $name       The name of the parameter you're wanting the
    *                                   value of.
    * @return   bool|string|array       Returns bool and throws an Exception on failure,
    *                                   string if $name parameter is used and matches,
    *                                   and an array is $name parameter is null.
    * @static
    * @access   public
    */
    public static function get($section, $name=null)
    {
        // Check if data has already been set
        if(self::$data === null)
        {
            // Try to parse the config.ini file
            self::$data = parse_ini_file('config.ini', true);
            
            // If config.ini couldn't be parsed, throw an exception
            if(self::$data === false)
            {
                $this->handleError('Configuration file missing/corrupt.');
            }
        }
       
        // Check if the section in in the {@link $data} array
        if(array_key_exists($section, self::$data))
        {
            // Check if the {@link $name} parameter is in the array if it's defined
            if($name && array_key_exists($name, self::$data[$section]))
            {
                // Return the data string
                return self::$data[$section][$name];
            }
            // Try to return the array
            else
            {
                return self::$data[$section];
            }
        }
        else
        {
            // No exception is thrown here since it will be handled in the
            // by the script calling it instead.
            return false;
        }
    }
    
   /**
    * Class Error Handler
    *
    * This function is used to handle the error for this class.
    *
    * @todo     Add error handling for this class.
    * @param    string      $string     The error message.
    * @static
    * @access   private
    */
    private static function handleError($string)
    {
        throw new Exception($string);
    }
}
/** Example Test Usage **/
// echo Config::getParam('database', 'user');
?>
