<?php

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

class CriticParserUrls {

    private $cm;
    private $cp;

    public function __construct() {
        $this->cm = new CriticMatic();
        $this->cp = new CriticParser($this->cm);
    }

    public function get_nationalreview() {
        $urls = array();
        $urls[] = 'https://www.nationalreview.com/author/kyle-smith/';
        for ($i = 2; $i <= 134; $i++) {
            $urls[] = 'https://www.nationalreview.com/author/kyle-smith/page/' . $i . '/';
        }

        $cid = 17;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            if (preg_match_all('/category-film-tv.*<h4 class="post-list-article__title"><a href="([^"]+)"/Us', $code, $match)) {

                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_crosswalk() {
        $urls = array();
        for ($i = 1; $i <= 136; $i++) {
            $urls[] = 'https://www.crosswalk.com/culture/movies/archives.html?p=' . $i;
        }

        $cid = 3;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            if (preg_match_all('/<div class="row item">[^<]+<a class="title" href="([^"]+)"/s', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            sleep(1);
            break;
        }
    }

    public function get_letterboxd() {
        $urls = array();
        $urls[] = 'https://letterboxd.com/sonnybunch/films/reviews/';
        for ($i = 2; $i <= 47; $i++) {
            $urls[] = 'https://letterboxd.com/sonnybunch/films/reviews/page/' . $i . '/';
        }

        $cid = 8;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            if (preg_match_all('/<h2 class="headline-2 prettify">[^<]*<a href="([^"]+)"/s', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, 'https://letterboxd.com' . $u);
                    print $u . "<br />";
                }
            }
            sleep(1);
        }
    }

    public function get_cityweekly() {
        $urls = array();

        for ($i = 1; $i <= 52; $i++) {
            $urls[] = 'https://www.cityweekly.net/BuzzBlog/archives/movies/?page=' . $i . '&topic=2125742';
        }

        $cid = 9;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            if (preg_match_all('/<h3 class="headline"><a href="([^"]+)"/', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }

            sleep(1);
        }
    }

    public function get_jaysanalysis() {
        $urls = array();
        $urls[] = 'https://jaysanalysis.com/category/film-reviewanalysis/';

        for ($i = 2; $i <= 23; $i++) {
            $urls[] = 'https://jaysanalysis.com/category/film-reviewanalysis/page/' . $i . '/';
        }

        $cid = 10;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<div class="mag-super-title">[^<]*<h3><a href=\'([^\']+)\'/s', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            sleep(1);
        }
    }

    public function get_therightstuff() {
        $urls = array();
        $urls[] = 'https://therightstuff.biz/category/all-shows/poz-button/';

        for ($i = 2; $i <= 11; $i++) {
            $urls[] = 'https://therightstuff.biz/category/all-shows/poz-button/page/' . $i . '/';
        }

        $cid = 11;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<p><a class="more-link" href="([^"]+)" rel="nofollow">/', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_icareviews() {
        $urls = array();
        $urls[] = 'https://icareviews.wordpress.com/';

        for ($i = 2; $i <= 117; $i++) {
            $urls[] = 'https://icareviews.wordpress.com/page/' . $i . '/';
        }

        $cid = 12;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<h1><a href="([^"]+)" rel="bookmark">/', $code, $match)) {
                foreach ($match[1] as $u) {
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_unz() {
        $urls = array();
        $urls[] = 'https://www.unz.com/author/linh-dinh/';

        for ($i = 2; $i <= 9; $i++) {
            $urls[] = 'https://www.unz.com/author/linh-dinh/page/' . $i . '/';
        }

        $cid = 13;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>Read More<\/a>/', $code, $match)) {
                foreach ($match[1] as $u) {
                    $u = 'https://www.unz.com' . $u;
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_unz_search() {
        $urls = array();

        for ($i = 1; $i <= 120; $i++) {
            $urls[] = 'https://www.unz.com/?s=review&Action=Search&authors=steve-sailer&ptype=all&paged=' . $i;
        }

        $cid = 15;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<div[^>]+><a[^>]+href="(\/isteve\/[^"]+)"[^>]+>[^<]+<\/a><\/div>/', $code, $match)) {
                foreach ($match[1] as $u) {
                    $u = 'https://www.unz.com' . $u;
                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_nwioqeqkdf() {
        $urls = array();

        for ($i = 1; $i <= 4; $i++) {
            $urls[] = 'http://nwioqeqkdf.blogspot.com/sitemap.xml?page=' . $i;
        }

        $cid = 16;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<loc>([^<]+)<\/loc>/', $code, $match)) {
                foreach ($match[1] as $u) {

                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_nypost() {
        $urls = array();
        $urls[] = 'https://nypost.com/tag/movie-reviews/';

        for ($i = 2; $i <= 138; $i++) {
            $urls[] = 'https://nypost.com/tag/movie-reviews/page/' . $i . '/';
        }

        $cid = 18;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<h3 class="story__headline[^"]+">[^<]*<a href="([^"]+)"/s', $code, $match)) {
                foreach ($match[1] as $u) {

                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

    public function get_hollywoodintoto() {
        $urls = array();
        $urls[] = 'https://www.hollywoodintoto.com/category/reviews/movies/';

        for ($i = 2; $i <= 133; $i++) {
            $urls[] = 'https://www.hollywoodintoto.com/category/reviews/movies/page/' . $i . '/';
        }

        $cid = 19;

        foreach ($urls as $url) {
            $code = $this->cp->get_proxy($url, '', $headers);
            //print_r(htmlspecialchars($code));
            if (preg_match_all('/<a class="more-link button" href="([^"]+)">Read More/', $code, $match)) {
                foreach ($match[1] as $u) {

                    $this->cp->add_url($cid, $u);
                    print $u . "<br />";
                }
            }
            //break;
            sleep(1);
        }
    }

}

$cpu = new CriticParserUrls();


//$cpu->get_hollywoodintoto();


/*
 * Include jQuery
var jq = document.createElement('script');
jq.src = "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js";
document.getElementsByTagName('head')[0].appendChild(jq);

jQuery.noConflict();



 */
//jQuery(jQuery.find('h3.entry-heading > a')).each(function(){console.log(jQuery(this).attr('href'))})
//jQuery(jQuery.find('.podcast__episodes-item-title a')).each(function(){console.log(jQuery(this).attr('href'))})
