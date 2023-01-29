<?php

namespace zqtop999\think\tp5tools;
//DT_CACHE的文件夹名称
define('DT_CACHEDIR', 'zqCacheData');
//_FILE_在二级目录
define('DT_ROOT', str_replace("\\", '/', dirname(__FILE__)) . "/..");
define('IN_CACHE', 1);
//其他常量
define('DT_WIN', strpos(strtoupper(PHP_OS), 'WIN') !== false ? true : false);
define('DT_CHMOD', (0777 && !DT_WIN) ? 0777 : 0);
define('DT_PATH', "http://" . $_SERVER['HTTP_HOST']);
//DT_CACHE在
define('DT_CACHE', DT_ROOT . "/" . DT_CACHEDIR);
//region 方法
if (!function_exists('file_put_contents')) {
    define('FILE_APPEND', 8);
    function file_put_contents($file, $string, $append = '')
    {
        $mode = $append == '' ? 'wb' : 'ab';
        $fp = @fopen($file, $mode) or exit("Can not open $file");
        flock($fp, LOCK_EX);
        $stringlen = @fwrite($fp, $string);
        flock($fp, LOCK_UN);
        @fclose($fp);
        return $stringlen;
    }
}

use think\App;

class fileCache extends App
{
    public static function file_ext($filename)
    {
        if (strpos($filename, '.') === false) return '';
        $ext = strtolower(trim(substr(strrchr($filename, '.'), 1)));
        return preg_match("/^[a-z0-9]{1,10}$/", $ext) ? $ext : '';
    }

    public static function file_vname($name)
    {
        if (strpos($name, '/') === false) return str_replace(array(' ', '\\', ':', '*', '?', '"', '<', '>', '|', "'", '$', '&', '%', '#', '@'), array('-', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''), $name);
        $tmp = explode('/', $name);
        $str = '';
        foreach ($tmp as $k => $v) {
            $str .= ($k ? '/' : '') . self::file_vname($v);
        }
        return $str;
    }

    public static function file_down($file, $filename = '', $data = '')
    {
        if (!$data && !is_file($file)) exit;
        $filename = $filename ? $filename : basename($file);
        $filetype = self::file_ext($filename);
        $filesize = $data ? strlen($data) : filesize($file);
        ob_end_clean();
        @set_time_limit(0);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
        } else {
            header('Pragma: no-cache');
        }
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Encoding: none');
        header('Content-Length: ' . $filesize);
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Type: ' . $filetype);
        if ($data) {
            echo $data;
        } else {
            readfile($file);
        }
        exit;
    }

    public static function file_list($dir, $fs = array())
    {
        $files = glob($dir . '/*');
        if (!is_array($files)) return $fs;
        foreach ($files as $file) {
            if (is_dir($file)) {
                $fs = self::file_list($file, $fs);
            } else {
                $fs[] = $file;
            }
        }
        return $fs;
    }

    public static function file_copy($from, $to)
    {
        self::dir_create(dirname($to));
        if (is_file($to) && DT_CHMOD) @chmod($to, DT_CHMOD);
        if (strpos($from, DT_PATH) !== false) $from = str_replace(DT_PATH, DT_ROOT . '/', $from);
        if (@copy($from, $to)) {
            if (DT_CHMOD) @chmod($to, DT_CHMOD);
            return true;
        } else {
            return false;
        }
    }

    public static function file_put($filename, $data)
    {
        self::dir_create(dirname($filename));
        if (@$fp = fopen($filename, 'wb')) {
            flock($fp, LOCK_EX);
            $len = fwrite($fp, $data);
            flock($fp, LOCK_UN);
            fclose($fp);
            if (DT_CHMOD) @chmod($filename, DT_CHMOD);
            return $len;
        } else {
            return false;
        }
    }

    public static function file_get($filename)
    {
        return @file_get_contents($filename);
    }

    public static function file_del($filename)
    {
        if (DT_CHMOD) @chmod($filename, DT_CHMOD);
        return is_file($filename) ? @unlink($filename) : false;
    }

    public static function dir_path($dirpath)
    {
        $dirpath = str_replace('\\', '/', $dirpath);
        if (substr($dirpath, -1) != '/') $dirpath = $dirpath . '/';
        return $dirpath;
    }

    public static function dir_create($path)
    {
        if (is_dir($path)) return true;
        if (DT_CACHE != DT_ROOT . "/" . DT_CACHEDIR && strpos($path, DT_CACHE) !== false) {
            $dir = str_replace(DT_CACHE . '/', '', $path);
            $dir = self::dir_path($dir);
            $temp = explode('/', $dir);
            $cur_dir = DT_CACHE . '/';
            $max = count($temp) - 1;
            for ($i = 0; $i < $max; $i++) {
                $cur_dir .= $temp[$i] . '/';
                if (is_dir($cur_dir)) continue;
                @mkdir($cur_dir);
                if (DT_CHMOD) @chmod($cur_dir, DT_CHMOD);
            }
        } else {
            $dir = str_replace(DT_ROOT . '/', '', $path);
            $dir = self::dir_path($dir);
            $temp = explode('/', $dir);
            $cur_dir = DT_ROOT . '/';
            $max = count($temp) - 1;
            for ($i = 0; $i < $max; $i++) {
                $cur_dir .= $temp[$i] . '/';
                if (is_dir($cur_dir)) continue;
                @mkdir($cur_dir);
                if (DT_CHMOD) @chmod($cur_dir, DT_CHMOD);
            }
        }
        return is_dir($path);
    }

    public static function dir_chmod($dir, $mode = '', $require = 0)
    {
        if (!$require) $require = substr($dir, -1) == '*' ? 2 : 0;
        if ($require) {
            if ($require == 2) $dir = substr($dir, 0, -1);
            $dir = self::dir_path($dir);
            $list = glob($dir . '*');
            foreach ($list as $v) {
                if (is_dir($v)) {
                    self::dir_chmod($v, $mode, 1);
                } else {
                    @chmod(basename($v), $mode);
                }
            }
        }
        if (is_dir($dir)) {
            @chmod($dir, $mode);
        } else {
            @chmod(basename($dir), $mode);
        }
    }

    public static function dir_copy($fromdir, $todir)
    {
        $fromdir = self::dir_path($fromdir);
        $todir = self::dir_path($todir);
        if (!is_dir($fromdir)) return false;
        if (!is_dir($todir)) self::dir_create($todir);
        $list = glob($fromdir . '*');
        foreach ($list as $v) {
            $path = $todir . basename($v);
            if (is_file($path) && !is_writable($path)) {
                if (DT_CHMOD) @chmod($path, DT_CHMOD);
            }
            if (is_dir($v)) {
                self::dir_copy($v, $path);
            } else {
                @copy($v, $path);
                if (DT_CHMOD) @chmod($path, DT_CHMOD);
            }
        }
        return true;
    }

    public static function dir_delete($dir)
    {
        $dir = self::dir_path($dir);
        if (!is_dir($dir)) return false;
        $dirs = array(DT_ROOT . '/admin/', DT_ROOT . '/api/', DT_CACHE . '/', DT_ROOT . '/file/', DT_ROOT . '/include/', DT_ROOT . '/lang/', DT_ROOT . '/member/', DT_ROOT . '/module/', DT_ROOT . '/skin/', DT_ROOT . '/template/', DT_ROOT . '/mobile/');
        if (substr($dir, 0, 1) == '.' || in_array($dir, $dirs)) die("Cannot Remove System DIR $dir ");
        $list = glob($dir . '*');
        if ($list) {
            foreach ($list as $v) {
                is_dir($v) ? self::dir_delete($v) : @unlink($v);
            }
        }
        return @rmdir($dir);
    }

    public static function get_file($dir, $ext = '', $fs = array())
    {
        $files = glob($dir . '/*');
        if (!is_array($files)) return $fs;
        foreach ($files as $file) {
            if (is_dir($file)) {
                if (is_file($file . '/index.php') && is_file($file . '/config.inc.php')) continue;
                $fs = self::get_file($file, $ext, $fs);
            } else {
                if ($ext) {
                    if (preg_match("/\.($ext)$/i", $file)) $fs[] = $file;
                } else {
                    $fs[] = $file;
                }
            }
        }
        return $fs;
    }

    public static function is_write($file)
    {
        if (DT_WIN) {
            if (substr($file, -1) == '/') {
                if (is_dir($file)) {
                    $file = $file . 'writeable-test.tmp';
                    if (@$fp = fopen($file, 'a')) {
                        flock($fp, LOCK_EX);
                        fwrite($fp, 'OK');
                        flock($fp, LOCK_UN);
                        fclose($fp);
                        $tmp = file_get_contents($file);
                        unlink($file);
                        if ($tmp == 'OK') return true;
                    }
                    return false;
                } else {
                    self::dir_create($file);
                    if (is_dir($file)) return self::is_write($file);
                    return false;
                }
            } else {
                if (@$fp = fopen($file, 'a')) {
                    fclose($fp);
                    return true;
                }
                return false;
            }
        } else {
            return is_writeable($file);
        }
    }

    public static function strip_nr($string, $js = false)
    {
        $string = str_replace(array(chr(13), chr(10), "\n", "\r", "\t", '  '), array('', '', '', '', '', ''), $string);
        if ($js) $string = str_replace("'", "\'", $string);
        return $string;
    }

    public static function cache_read($file, $dir = '', $mode = '')
    {
        $file = $dir ? DT_CACHE . '/' . $dir . '/' . $file : DT_CACHE . '/' . $file;
        if (!is_file($file)) return $mode ? '' : array();
        return $mode ? self::file_get($file) : include $file;
    }

    public static function cache_write($file, $string, $dir = '')
    {
        if (is_array($string)) $string = "<?php defined('IN_CACHE') or exit('Access Denied'); return " . self::strip_nr(var_export($string, true)) . "; ?>";
        $file = $dir ? DT_CACHE . '/' . $dir . '/' . $file : DT_CACHE . '/' . $file;
        $strlen = self::file_put($file, $string);
        return $strlen;
    }

    public static function cache_delete($file, $dir = '')
    {
        $file = $dir ? DT_CACHE . '/' . $dir . '/' . $file : DT_CACHE . '/' . $file;
        return self::file_del($file);
    }

}
