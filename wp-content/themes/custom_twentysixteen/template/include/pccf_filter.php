<?php


if (!function_exists('get_option_a')) {
    function get_option_a($id)
    {
        global $table_prefix;

        $sql ="SELECT option_value FROM ".$table_prefix."options WHERE option_name = ? LIMIT 1";

        $r = Pdo_wp::db_fetch_row($sql,array($id));
       return $r->option_value;
    }
}

if (!function_exists('pccf_filter')) {
    function pccf_filter($text)
    {

        $valprev='';

        if (function_exists('get_option'))
        {
            $tmp = get_option( 'pccf_options');
        }
        else if (function_exists('get_option_a')) {

            $tmps = get_option_a('pccf_options');


            $tmps = preg_replace('/s:\d+:/', '', $tmps);
            $tmps = preg_replace('/a:\d+:/', '', $tmps);
            $tmps = str_replace(array('{', '}', '"'), '', $tmps);

            ///   echo $tmps;

            $tmps = explode(';', $tmps);
            $i = 0;
            foreach ($tmps as $val) {
                if ($valprev) {
                    $result[$valprev] = $val;
                    $valprev = '';
                } else {
                    $valprev = $val;

                }
            }
            $tmp = $result;

        }


        if ($tmp) {

            /// var_dump($tmp);

            ///echo  $tmp['txtar_keywords'];

            $exclude_id_list = $tmp['txt_exclude'];
            $exclude_id_array = explode(', ', $exclude_id_list);

            $wildcard_filter_type = $tmp['rdo_word'];
            $wildcard_char = $tmp['drp_filter_char'];

            if ($wildcard_char == 'star') {
                $wildcard = '*';
            } else {
                if ($wildcard_char == 'dollar') {
                    $wildcard = '$';
                } else {
                    if ($wildcard_char == 'question') {
                        $wildcard = '?';
                    } else {
                        if ($wildcard_char == 'exclamation') {
                            $wildcard = '!';
                        } else {
                            if ($wildcard_char == 'hyphen') {
                                $wildcard = '-';
                            } else {
                                if ($wildcard_char == 'hash') {
                                    $wildcard = '#';
                                } else {
                                    if ($wildcard_char == 'tilde') {
                                        $wildcard = '~';
                                    } else {
                                        $wildcard = '';
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $filter_type = $tmp['rdo_case'];
            $db_search_string = $tmp['txtar_keywords'];

            $keywords = array_map('trim', explode(',', $db_search_string)); // explode and trim whitespace
            $keywords = array_unique($keywords); // get rid of duplicates in the keywords textbox
            $whole_word = $tmp['rdo_strict_filtering'] == 'strict_off' ? false : true;

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword); // remove whitespace chars from start/end of string
                if (strlen($keyword) > 2) {
                    $replacement = censor_word($wildcard_filter_type, $keyword, $wildcard);
                    if ($filter_type == "insen") {
                        $text = str_replace_word_i($keyword, $replacement, $text, $wildcard_filter_type, $keyword, $wildcard, $whole_word);
                    } else {
                        $text = str_replace_word($keyword, $replacement, $text, $whole_word);
                    }
                }
            }

            return $text;
        }
    }
}
if (!function_exists('censor_word')) {
    function censor_word($wildcard_filter_type, $keyword, $wildcard)
    {

        if ($wildcard_filter_type == 'first') {
            $keyword = substr($keyword, 0, 1) . str_repeat($wildcard, strlen(substr($keyword, 1)));
        } else {
            if ($wildcard_filter_type == 'all') {
                $keyword = str_repeat($wildcard, strlen(substr($keyword, 0)));
            } else {
                $keyword = substr($keyword, 0, 1) . str_repeat($wildcard, strlen(substr($keyword, 2))) . substr($keyword, -1, 1);
            }
        }

        return $keyword;
    }
}

if (!function_exists('str_replace_word_i')) {
// case insensitive
    function str_replace_word_i($needle, $replacement, $haystack, $wildcard_filter_type, $keyword, $wildcard, $whole_word = true)
    {

        $needle = str_replace('/', '\\/', preg_quote($needle)); // allow '/' in keywords
        $pattern = $whole_word ? "/\b$needle\b/i" : "/$needle/i";
        $haystack = preg_replace_callback(
            $pattern,
            function ($m) use ($wildcard_filter_type, $keyword, $wildcard) {
                return censor_word($wildcard_filter_type, $m[0], $wildcard);
            },
            $haystack);

        return $haystack;
    }
}

if (!function_exists('str_replace_word')) {
// case sensitive
    function str_replace_word($needle, $replacement, $haystack, $whole_word = true)
    {
        $needle = str_replace('/', '\\/', preg_quote($needle)); // allow '/' in keywords
        $pattern = $whole_word ? "/\b$needle\b/" : "/$needle/";
        $haystack = preg_replace($pattern, $replacement, $haystack);

        return $haystack;
    }
}