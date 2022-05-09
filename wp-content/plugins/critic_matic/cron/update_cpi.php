<?php

/*
 * Update CPI from site: https://www.rateinflation.com/consumer-price-index/usa-historical-cpi/
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}

if (!class_exists('CriticParser')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

class CpiParser {

    private $cm;
    private $cp;
    private $sf;

    public function __construct() {
        $this->cm = new CriticMatic();
        $this->cp = new CriticParser($this->cm);
    }

    public function get_ma() {
        // Get criti
        if (!$this->ma) {
            // init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this->cm);
        }
        return $this->ma;
    }

    public function get_rateinflation() {
        $url = 'https://www.rateinflation.com/consumer-price-index/usa-historical-cpi/';
        $code = $this->cp->get_proxy($url, '', $headers);

        if (preg_match_all('|<tr><td>([0-9]{4})</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>[0-9\.]+</td><td>([0-9\.]+)</td></tr>|', $code, $match)) {
            $ma = $this->get_ma();

            if (sizeof($match[1])) {

                foreach ($match[1] as $key => $year) {
                    $cpi = floatval($match[2][$key]);
                    // Annual type
                    $type = 0;
                    $ma->add_cpi($cpi, $year, $type);
                    if ($_GET['debug']) {
                        print_r(array($cpi, $year, $type));
                    }
                }
            }
        }
    }

}

$cpip = new CpiParser();
$cpip->get_rateinflation();
