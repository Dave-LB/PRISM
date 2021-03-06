<?php

function console($line, $EOL = true, $fgcolor = "light_gray", $bgcolor = "black")
{
    // example:
    // console("this is my exampletext", true, "light_green", "black");
    //
    // Windows: You need to download ansicon first; http://adoxa.altervista.org/ansicon/
    // first run ansicon -l on the command line.. Then the dosbox console switches to ANSI supported mode.
    //
    // In Linux use a ANSI supported terminal like for example SecureCRT

    $ansi_fgcolor_arr = array(
                            "black"         => "0;30",
                            "dark_gray"     => "1;30",
                            "red"           => "0;31",
                            "light_red"     => "1;31",
                            "green"         => "0;32",
                            "light_green"   => "1;32",
                            "brown"         => "0;33",
                            "yellow"        => "1;33",
                            "blue"          => "0;34",
                            "light_blue"    => "1;34",
                            "purple"        => "0;35",
                            "light_purple"  => "1;35",
                            "cyan"          => "0;36",
                            "light_cyan"    => "1;36",
                            "light_gray"    => "0;37",
                            "white"         => "1;37"
                            );

    $ansi_bgcolor_arr = array(
                            "black"         => "40",
                            "red"           => "41",
                            "green"         => "42",
                            "yellow"        => "43",
                            "blue"          => "44",
                            "magenta"       => "45",
                            "cyan"          => "46",
                            "light_gray"    => "47"
                            );

    echo "\033[" . $ansi_fgcolor_arr[$fgcolor] . "m\033[" . $ansi_bgcolor_arr[$bgcolor] . "m" . $line . "\033[0m" . (($EOL) ? PHP_EOL : '');
}

function translateEngine($lang_subdirectory, $languageID, $messageID, $args = array(), $fallback = 'en')
{
    $lang_array = array("en", "de", "pt", "fr", "fi", "nn", "nl", "ca", "tr", "es", "it", "da", "cs", "ru", "et", "sr", "el", "pl", "hr", "hu", "br", "sv", "sk", "gl", "sl", "be", "lv", "lt", "zh", "cn", "ja", "ko", "bg", "mx", "uk", "id", "ro");
    if(!isset($lang_array[$languageID])){
        return "Unknown Language Selected";
    }
    $lang_folder = ROOTPATH . "/data/langs/{$lang_subdirectory}";
    if(!is_readable($lang_folder)){
        console("Language Folder for {$lang_subdirectory} is missing or not readable.");
        return "Language Folder for {$lang_subdirectory} is missing or not readable.";
    }
    $lang_file = "{$lang_folder}/{$lang_array[$languageID]}.ini";
    if(is_readable($lang_file)){
        $LANG = parse_ini_file ($lang_file);
    } else {
        $lang_file = "{$lang_folder}/{$fallback}.ini";
        if(is_readable($lang_file)){
            $LANG = parse_ini_file ($lang_file);
            console("Language File for {$lang_array[$languageID]} in {$lang_subdirectory} is missing or not readable.");
        } else {
            console("Language File for {$lang_array[$languageID]} in {$lang_subdirectory} is missing or not readable. No Fallback Available.");
            return "Language File for {$lang_array[$languageID]} in {$lang_subdirectory} is missing or not readable. No Fallback Available.";
        }
    }

    if(isset($LANG[$messageID])){
        return vsprintf ( $LANG[$messageID], $args);
    } else {
        console("Missing Language Entry: {$messageID} in {$lang_array[$languageID]}");
        if($languageID != $fallback) {
            return translateEngine($lang_subdirectory, $fallback, $messageID, $args, $fallback);
        } else {
            return "Missing Language Entry: {$messageID} in {$lang_array[$languageID]}";
        }
    }
}

function get_dir_structure($path, $recursive = TRUE, $ext = NULL)
{
    $return = NULL;
    if (!is_dir($path))
    {
        trigger_error('$path is not a directory!', E_USER_WARNING);
        return FALSE;
    }
    if ($handle = opendir($path))
    {
        while (FALSE !== ($item = readdir($handle)))
        {
            if ($item != '.' && $item != '..')
            {
                if (is_dir($path . $item))
                {
                    if ($recursive)
                    {
                        $return[$item] = get_dir_structure($path . $item . '/', $recursive, $ext);
                    }
                    else
                    {
                        $return[$item] = array();
                    }
                }
                else
                {
                    if ($ext != null && strrpos($item, $ext) !== FALSE)
                    {
                        $return[] = $item;
                    }
                }
            }
        }
        closedir($handle);
    }
    return $return;
}

// check if path1 is part of path2 (ie. if path1 is a base path of path2)
function isDirInDir($path1, $path2)
{
    $p1 = explode('/', $path1);
    $p2 = explode('/', $path2);

    foreach ($p1 as $index => $part)
    {
        if ($part === '')
            continue;
        if (!isset($p2[$index]) || $part != $p2[$index])
            return false;
    }

    return true;
}

function findPHPLocation($windows = false)
{
    $phpLocation = '';

    if ($windows)
    {
        console('Trying to find the location of php.exe');

        // Search in current dir first.
        $exp = explode("\r\n", shell_exec('dir /s /b php.exe'));
        if (preg_match('/^.*\\\php\.exe$/', $exp[0]))
        {
            $phpLocation = $exp[0];
        }
        else
        {
            // Do a recursive search on this whole drive.
            chdir('/');
            $exp = explode("\r\n", shell_exec('dir /s /b php.exe'));
            if (preg_match('/^.*\\\php\.exe$/', $exp[0]))
                $phpLocation = $exp[0];
            chdir(ROOTPATH);
        }
    }
    else
    {
        $exp = explode(' ', shell_exec('whereis php'));
        $count = count($exp);
        if ($count == 1)                // Some *nix's output is only the path
            $phpLocation = $exp[0];
        else if ($count > 1)            // FreeBSD for example has more info on the line, like :
            $phpLocation = $exp[1];        // php: /user/local/bin/php /usr/local/man/man1/php.1.gz
    }

    return $phpLocation;
}

function validatePHPFile($file)
{
    // Validate script
    $fileContents = file_get_contents($file);
    if (!eval('return true;'.preg_replace(array('/^<\?(php)?/', '/\?>$/'), '', $fileContents)))
        return array(false, array('Errors parsing '.$file));

    // Validate any require_once or include_once files.
//    $matches = array();
//    preg_match_all('/(include_once|require_once)\s*\(["\']+(.*)["\']+\)/', $fileContents, $matches);
//
//    foreach ($matches[2] as $include)
//    {
//        console($include);
//        $result = validatePHPFile($include);
//        if ($result[0] == false)
//            return $result;
//    }

    return array(true, array());
}

function flagsToInteger($flagsString = '')
{
    # We don't have anything to parse.
    if ($flagsString == '')
        return FALSE;

    $flagsBitwise = 0;
    for ($chrPointer = 0, $strLen = strlen($flagsString); $chrPointer < $strLen; ++$chrPointer)
    {
        # Convert this charater to it's ASCII int value.
        $char = ord($flagsString{$chrPointer});

        # We only want a (ASCII = 97) through z (ASCII 122), nothing else.
        if ($char < 97 || $char > 122)
            continue;

        # Check we have already set that flag, if so skip it!
        if ($flagsBitwise & (1 << ($char - 97)))
            continue;

        # Add the value to our $flagBitwise intager.
        $flagsBitwise += (1 << ($char - 97));
    }
    return $flagsBitwise;
}

function flagsToString($flagsBitwise = 0)
{
    $flagsString = '';
    if ($flagsBitwise == 0)
        return $flagsString;

    # This makes sure we only handle the flags we know by unsetting any unknown bits.
    $flagsBitwise = $flagsBitwise & ADMIN_ALL;

    # Converts bits to the char forms.
    for ($i = 0; $i < 26; ++$i)
        $flagsString .= ($flagsBitwise & (1 << $i)) ? chr($i + 97) : NULL;

    return $flagsString;
}

define('RAND_ASCII', 1);
define('RAND_ALPHA', 2);
define('RAND_NUMERIC', 4);
define('RAND_HEX', 8);
define('RAND_BINARY', 16);
function createRandomString($len, $type = RAND_ASCII)
{
    $out = '';
    for ($a=0; $a<$len; $a++)
    {
        if ($type & RAND_ALPHA)
        {
            $out .= rand(0,1) ? chr(rand(65, 90)) : chr(rand(97, 122));
        }
        else if ($type & RAND_NUMERIC)
        {
            $out .= chr(rand(48, 57));
        }
        else if ($type & RAND_HEX)
        {
            $out .= sprintf('%02x', rand(0, 255));
        }
        else if ($type & RAND_BINARY)
        {
            $out .= chr(rand(0, 255));
        }
        else
        {
            $out .= chr(rand(32, 127));
        }
    }
    return $out;
}

function ucwordsByChar($string, $delimiter)
{
    $out = '';
    foreach (explode($delimiter, $string) as $k => $v)
    {
        if ($k > 0)
            $out .= $delimiter;
        $out .= ucfirst($v);
    }
    return $out;
}

function getIP(&$ip)
{
    if (verifyIP($ip))
        return $ip;
    else
    {
        $tmp_ip = @gethostbyname($ip);
        if (verifyIP($tmp_ip))
            return $tmp_ip;
    }

    return FALSE;
}

function verifyIP(&$ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

function timeToString($int, $fraction=1000)
{
    $seconds = floor($int / $fraction);
    $fractions = $int - floor($seconds * $fraction);
    $seconds -= ($hours = floor($seconds / 3600)) * 3600;
    $seconds -= ($minutes = floor($seconds / 60)) * 60;

    if ($hours > 0)
    {
        return sprintf('%d:%02d:%02d.%0'.(strlen($fraction) - 1).'d', $hours, $minutes, $seconds, $fractions);
    }
    else
    {
        return sprintf('%d:%02d.%0'.(strlen($fraction) - 1).'d', $minutes, $seconds, $fractions);
    }
}

function timeToStr($time, $fraction=1000)
{
    return preg_replace('/^(0+:)+/', '', timeToString($time, $fraction));
}

function sortByKey($key)
{
    return function ($left, $right) use ($key)
    {
        if ($left[$key] == $right[$key])
            return 0;
        else
            return ($left[$key] < $right[$key]) ? -1 : 1;
    };
}

function sortByProperty($property)
{
    return function ($left, $right) use ($property)
    {
        if ($left->$property == $right->$property)
            return 0;
        else
            return ($left->$property < $right->$property) ? -1 : 1;
    };
}

class Msg2Lfs
{
    public $PLID = 0;
    public $UCID = 0;
    public $Text = '';
    public $Sound = SND_SILENT;

    public function __construct($text = '')
    {
        $this->Text = $text;
        return $this;
    }

    public function &__call($name, array $arguments)
    {
        if (property_exists(get_class($this), $name))
            $this->$name = array_shift($arguments);
        return $this;
    }

    public function send($hostId = NULL)
    {
        if ($this->Text == '') { return; }

        global $PRISM;

        // Decide what IS packet to use to send this message
        if (($PRISM->hosts->getStateById($hostId)->State & ISS_MULTI) === 0)
        {
            // Single player
            IS_MSL()->Msg($this->Text)->Sound($this->Sound)->send();
        }
        else
        {
            // Multi player
            if ($this->PLID > 0)
                IS_MTC()->PLID($this->PLID)->Text($this->Text)->Sound($this->Sound)->send();
            else if ($this->UCID > 0)
                IS_MTC()->UCID($this->UCID)->Text($this->Text)->Sound($this->Sound)->send();
            else
                IS_MSX()->Msg($this->Text)->send();
        }

        return $this;
    }
}; function Msg2Lfs() { return new Msg2Lfs; }
?>
