<?php
!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

class Actor_Data
{

    private static $array_verdict = ["gender" => "Gender", "ethnic" => "Ethnicelebs", "jew" => "JewOrNotJew", "kairos" => "Facial Recognition by Kairos", "bettaface" => "Facial Recognition by Betaface",
        "surname" => "Ethnicolr", "familysearch" => "FamilySearch", "forebears_rank" => "ForeBears", "crowdsource" => "Crowdsource", "verdict" => "Verdict"];

    private static $array_link = [
        "ethnic" => '<a target="_blank" href="https://ethnicelebs.com">Ethnicelebs</a>',
        "jew" => '<a target="_blank" href="">JewOrNotJew</a>',
        "kairos" => '<a target="_blank" href="https://kairos.com">Kairos</a>',
        "bettaface" => '<a target="_blank" href="https://www.betafaceapi.com/demo_old.html">Betaface</a>',
        "surname" => '<a target="_blank" href="https://pypi.org/project/ethnicolr/">Ethnicolr</a>',
        "familysearch" =>'<a target="_blank" href="https://www.familysearch.org/en/surname">FamilySearch</a>',
        "forebears_rank" => '<a target="_blank" href="https://forebears.io/surnames">ForeBears</a>',
        "crowdsource" => "Crowdsource",
        "verdict" => "Verdict"
    ];



    public static function actor_verdict($id, $verdict)
    {


        $content = '';
        foreach ($verdict as $i => $v) {


            $class = '';
            if ($i == 'verdict') {
                $class = ' yellow ';
            }

            $content .= '<div class="single_flex' . $class . '"><div class="block"><span>' . self::$array_verdict[$i] . '</span></div><div class="block">' . strtoupper($v) . '</div></div>';


        }
        return $content;
    }

    private static function get_rand($used_template = [], $count)
    {
        $available_numbers = range(0, $count);
        $available_numbers = array_diff($available_numbers, $used_template);


        if (empty($available_numbers)) {
            return [0, $used_template];
        }


        $random_index = array_rand($available_numbers);
        $random_number = $available_numbers[$random_index];


        $used_template[] = $random_number;

        return [$random_number, $used_template];
    }

    private static function update_template($id, $used_template, $tmplate_order_last = '')
    {
        $tmplate_order = json_encode($used_template);


        if ($tmplate_order_last && $tmplate_order_last == $tmplate_order) {
            //echo 'already updated';
            return;
        }


        $q = "SELECT `val` FROM `cache_actor_template` WHERE `aid` = " . $id;
        $r = Pdo_an::db_get_data($q, 'val');
        if ($r) {

            $q = "UPDATE `cache_actor_template` SET `val`=? WHERE `aid`=?";
            Pdo_an::db_results_array($q, [$tmplate_order, $id]);
        } else {

            $q = "INSERT INTO `cache_actor_template`(`aid`, `val`) VALUES (?,?)";
            Pdo_an::db_results_array($q, [$id, $tmplate_order]);
        }

    }


    private static function set_template($array_data, $Templates, $i, $id, $tmplate_order, $used_template, $e, $next_string = '. ')
    {

        // $array_data=['_data'=>$ethnic_data,'_he_she'=> $personGender,'_personName'=> $personName, '_verdict', $verdict,'_source'=>$link_data ];
        $etc = count($Templates) - 1;


        if ($tmplate_order[$i] && $tmplate_order[$i] <= $etc && $tmplate_order[$i] >=0 ) {
            $a = $tmplate_order[$i];
            $used_template[$e][] = $a;
        } else {
            [$a, $used_template[$e]] = self::get_rand($used_template[$e], $etc);
            $tmplate_order[$i] = $a;
            self::update_template($id, $tmplate_order);
        }

        $randomTemplate = $Templates[$a];

        foreach ($array_data as $i => $v) {
            $randomTemplate = str_replace($i, $v, $randomTemplate);
        }

        $randomTemplate = str_replace('she\'s', 'her', $randomTemplate);
        $randomTemplate = str_replace('he\'s', 'his', $randomTemplate);

        $randomTemplate = $next_string . ucfirst($randomTemplate);

        return [$randomTemplate, $tmplate_order, $used_template];

    }

    public static function formatVerdicts($id, $verdicts, $personName, $short = '')
    {

        $tmplate_order = [];
        $used_template = ['e' => [], 'n' => [], 'c' => [], 'r' => [], 'v' => [], 'm' => [], 'bk' => [], 'f' => [], 'fs' => [], 's' => []];


        $ethnicTemplates = array(
            "According to _source, _he_she's ethnic roots are \"_data\" and ethnicity is _verdict.",
            "As per _source, _he_she's ethnic background is categorized as \"_data\" and ethnicity is _verdict.",
            "According to _source, _he_she's ancestry can be traced back to the \"_data\" ethnicity, and ethnicity is _verdict.",
            "_source suggests that _he_she's ethnic heritage is identified as \"_data\", making ethnicity _verdict.",
            "Based on information from _source, _he_she's ethnic lineage includes \"_data\" and is classified as _verdict.",
            "_source indicates that _he_she's ethnic origins align with \"_data\", resulting in ethnicity being _verdict.",
            "According to _source, _he_she's ethnicity is classified as \"_data\" and is also described as _verdict.",
            "_source states that _he_she's ethnic background is associated with \"_data\" and is categorized as _verdict.",
            "_source's data suggests that _he_she's ethnic roots are attributed to \"_data\", making ethnicity _verdict.",
            "According to _source, _he_she's ethnic descent includes \"_data\" and ethnicity is _verdict."
        );

        $templatesFace = array(
            "betaface" => array(
                "After running his photo through facial recognition, BetaFace labeled _he_she's _verdict.",
                "BetaFace's facial recognition technology identified _he_she as _verdict."
            ),
            "kairos" => array(
                "After running his photo through facial recognition, _Kairos labeled _he_she's _verdict.",
                "_Kairos identified _he_she's ethnicity as _verdict using facial recognition."
            ),
            "betaface_and_kairos" => array(
                "After running his photo through facial recognition, _BetaFace land _Kairos labeled _he_she's _verdict.",
                "Both _BetaFace and _Kairos labeled _he_she's ethnicity as _verdict after facial recognition analysis."
            ),
            "betaface_kairos_conflict" => array(

                "_BetaFace and _Kairos provided conflicting results: BetaFace labeled _he_she as _verdictFace, while Kairos identified _he_she's ethnicity as _verdictKairos."
            )
        );


        $templatesSurname = array(
            "Familysearch" => array(
                "As for his surname, it is the most common in _country, according to _source, so his verdict is _verdict.",
                "His surname is predominantly found in _country, as reported by _source, leading to the verdict of _verdict.",
            ),
            "Forebears" => array(
                "According to _source, _country has the highest prevalence of his surname, which aligns with the verdict of _verdict.",
                "Based on data from _source, _country is where his surname is most frequently found, resulting in the verdict of _verdict.",
                "The data from _source indicates that _country is the primary location for his surname, thus leading to the verdict of _verdict."
            ),
            "Familysearch_and_Forebears" => array(
                "Both _Forebears and _Familysearch data highlight _country as the primary location for his surname, leading to the verdict of _verdict.",
                "According to data from _Forebears and _Familysearch, _country is where his surname is predominantly found, resulting in the verdict of _verdict.",
                "The primary location for his surname, as indicated by both _Forebears and _Familysearch, is _country, thus leading to the verdict of _verdict.",
                "Data from both _Forebears and _Familysearch shows that _country is the primary location for his surname, resulting in the verdict of _verdict.",
                "According to both _Forebears and _Familysearch, _country is where his surname is most frequently found, leading to the verdict of _verdict."
            ),
            "Familysearch_and_Forebears_conflict_agree_verdict" => array(
                "The surname is primarily found in _ForebearsCountry according to _Forebears, with the verdict of _ForebearsVerdict. However, the data from _Familysearch differs, indicating that the surname is predominantly found in _FamilysearchCountry with the same verdict of _FamilysearchVerdict.",
                "According to _Forebears, the surname is predominantly found in _ForebearsCountry, leading to the verdict of _ForebearsVerdict. However, the data from _Familysearch contradicts this, suggesting that the surname is primarily found in _FamilysearchCountry with the same verdict of _FamilysearchVerdict.",
                "The data from _Forebears shows that the surname is most commonly found in _ForebearsCountry with the verdict of _ForebearsVerdict. In contrast, _Familysearch data indicates that the surname is primarily found in _FamilysearchCountry with the same verdict of _FamilysearchVerdict.",
                "_Forebears reports that the surname is predominantly found in _ForebearsCountry with the verdict of _ForebearsVerdict. However, _Familysearch data presents conflicting information, suggesting that the surname is primarily found in _FamilysearchCountry with the same verdict of _FamilysearchVerdict."
            ),
            "Familysearch_and_Forebears_conflict" =>array(
                "The surname is primarily found in _ForebearsCountry according to _Forebears, with the verdict of _ForebearsVerdict. However, the data from _Familysearch differs, indicating that the surname is predominantly found in _FamilysearchCountry with the verdict of _FamilysearchVerdict.",
                "According to _Forebears, the surname is predominantly found in _ForebearsCountry, leading to the verdict of _ForebearsVerdict. However, the data from _Familysearch contradicts this, suggesting that the surname is primarily found in _FamilysearchCountry with the verdict of _FamilysearchVerdict.",
                "The data from _Forebears shows that the surname is most commonly found in _ForebearsCountry with the verdict of _ForebearsVerdict. In contrast, _Familysearch data indicates that the surname is primarily found in _FamilysearchCountry with the verdict of _FamilysearchVerdict.",
                "_Forebears reports that the surname is predominantly found in _ForebearsCountry with the verdict of _ForebearsVerdict. However, _Familysearch data presents conflicting information, suggesting that the surname is primarily found in _FamilysearchCountry with the verdict of _FamilysearchVerdict."
            )
        );


        $Ethnicolr = array(
            "As for his surname, it is the most common in _country, according to _source, so his verdict is _verdict.",
            "His surname is predominantly found in _country, as reported by _source, leading to the verdict of _verdict.",
        );


        $neutralTemplates = array(
            "According to _source, _personName is most likely from _verdict.",
            "Based on _source's data, _personName probably comes from _verdict.",
            "_personName's probable ethnicity, according to _source, is _verdict.",
            "Analyzing _source's findings, _personName's ethnicity appears to be _verdict.",
            "_source suggests that _personName likely belongs to the _verdict ethnicity.",
            "_personName is typically associated with the _verdict ethnicity, according to _source.",
            "It seems that _personName hails from the _verdict ethnicity, as per _source.",
            "_source's analysis indicates that _personName is most likely from _verdict.",

        );


        $confirmingTemplates = array(
            "moreover, according to _source, _personName is also most likely from _verdict.",
            "furthermore, _personName is likely associated with _verdict, as indicated by _source.",
            "in addition, it seems that _personName's ethnic background is _verdict, according to _source.",
            "additionally, _personName's likely ethnic affiliation is _verdict, according to _source.",
            "furthermore, _personName appears to belong to the _verdict ethnicity based on _source's analysis.",
            "moreover, it is suggested that _personName's heritage is primarily _verdict, according to _source.",
            "in addition, _personName's origin is likely _verdict, based on _source's findings.",
            "additionally, _personName's data points to a strong association with the _verdict ethnicity, as per _source.",
            "moreover, it appears _personName is often associated with the _verdict ethnicity, according to _source.",
            "furthermore, _personName's ancestry is predominantly _verdict, according to _source."
        );


        $refutingTemplates = array(
            "However, according to _source, _personName is most likely from _verdict.",
            "Nevertheless, there is an assumption that _personName belongs to the _verdict ethnic group, according to _source.",
            "On the contrary, _personName's ethnicity is most likely _verdict, according to _source.",
            "However, _personName is possibly related to the ethnicity of _verdict, according to _source's findings.",
            "However, _personName is probably related to the ethnicity of _verdict, according to _source.",
            "However, it seems that the origin of _personName is predominantly _verdict, as indicated by _source."
        );

        $templateVariations_verdict = array(
            "_personName is _verdict."
        );

        $meta_templates = array(
            "Based on our analysis of the available data, we have determined that _personName's ethnic heritage is most likely _verdict.",
            "Utilizing our methodology and examining all the evidence, our conclusion is that _personName's ethnicity is likely _verdict.",
            "After thorough evaluation of our calculations, it is evident that _personName's ethnic background aligns with _verdict.",
            "According to our research findings, _personName's ethnicity is likely _verdict, based on our comprehensive analysis.",
            "Through our data-driven approach and meticulous examination, it is evident that _personName is most likely of _verdict ethnicity.",
            "Using our methodology, we have arrived at the conclusion that _personName's ethnic origin is likely _verdict.",
            "Our analysis of all available data indicates that _personName's ethnicity is most likely _verdict, according to our findings.",
            "Considering our rigorous assessment, it is probable that _personName belongs to _verdict ethnicity, based on our research.",
            "Based on our comprehensive examination, we conclude that _personName's ethnic identity is most likely _verdict.",
            "Through our extensive review of the data presented, it is likely that _personName's ethnic background aligns with _verdict."
        );


        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
         $template_arrays_string = OptionData::get_options('', 'actor_verdict_templates');
         $template_arrays = json_decode($template_arrays_string, 1);

//        $template_arrays =array('templatesSurname'=>$templatesSurname,'ethnicTemplates'=>$ethnicTemplates,'templatesFace'=>$templatesFace,'CountryTemplate'=>$Ethnicolr,   'meta_templates'=>$meta_templates, 'neutralTemplates'=>$neutralTemplates,'confirmingTemplates'=>$confirmingTemplates,'refutingTemplates'=>$refutingTemplates,'templateVariations_verdict'=>$templateVariations_verdict);
//        echo json_encode($template_arrays, JSON_PRETTY_PRINT);

        $neutralTemplates = $template_arrays['neutralTemplates'];
        $confirmingTemplates = $template_arrays['confirmingTemplates'];
        $refutingTemplates = $template_arrays['refutingTemplates'];
        $templateVariations_verdict = $template_arrays['templateVariations_verdict'];
        $meta_templates = $template_arrays['meta_templates'];

        $ethnicTemplates = $template_arrays['ethnicTemplates'];
        $templatesFace  =  $template_arrays['templatesFace'];
        $Ethnicolr = $template_arrays['EthnicolrTemplate'];
        $templatesSurname = $template_arrays['templatesSurname'];



        $ntc = count($neutralTemplates) - 1;
        $ctc = count($confirmingTemplates) - 1;
        $rtc = count($refutingTemplates) - 1;


        $q = "SELECT `val` FROM `cache_actor_template` WHERE `aid` = " . $id;
        $r = Pdo_an::db_get_data($q, 'val');
        if ($r) {
            $tmplate_order = json_decode($r, 1);
            $tmplate_order_last = $r;
        }

        $sentences = array();
        $sentences[0] = '';

        if ($verdicts['gender'] && $verdicts['gender'] != 'N/A') {
            $personGender = $verdicts['gender'] == 'Male' ? 'he' : 'she';
        } else {
            $personGender = $personName;
        }


        $person_verdict = $verdicts['verdict'];

        unset($verdicts['verdict']);
        unset($verdicts['gender']);

        //var_dump($verdicts);

        $i = 1;


        if ($verdicts['ethnic'] && $verdicts['ethnic'] != 'N/A') {
            $i++;

            $sql = "SELECT Ethnicity as ethnicity, Tags, actor_id, Link   FROM `data_actors_ethnic` WHERE  actor_id= " . $id . " limit 1";
            $q = Pdo_an::db_results_array($sql);
            foreach ($q as $r) {

                if ($r['ethnicity']) {
                    $ethnic_data = $r['ethnicity'];
                    $Link = $r['Link'];
                }
            }
            if (!$Link) {
                $Link = 'Ethnicelebs.com';
            }
            $link_data = '<a target="_blank" href="' . $Link . '">Ethnicelebs</a>';

            $verdict = $verdicts['ethnic'];


            $array_data = ['_data' => $ethnic_data, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdict, '_source' => $link_data];

            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $ethnicTemplates, $i, $id, $tmplate_order, $used_template, 'e'); ///$currentConnectingWord;
            $sentences[$i] = $randomTemplate;

            unset($verdicts['ethnic']);
        }

        if ($verdicts['kairos'] != 'N/A' || $verdicts['bettaface'] != 'N/A') {
            $i++;

            $link_data_k = '<a target="_blank" href="https://face.kairos.com/">Kairos</a>';
            $link_data_f = '<a target="_blank" href="https://www.betafaceapi.com/demo_old.html">Betaface</a>';

            if ($verdicts['kairos'] != 'N/A' && $verdicts['bettaface'] != 'N/A') {

                $array_data = ['_data' => $ethnic_data, '_he_she' => $personGender, '_personName' => $personName, '_verdictFace' => $verdicts['bettaface'], '_verdictKairos' => $verdicts['kairos'], '_verdict' => $verdicts['kairos'], '_Kairos' => $link_data_k, '_BetaFace' => $link_data_f];

                if ($verdicts['kairos'] == $verdicts['bettaface']) {
                    $temp_template = 'betaface_and_kairos';

                } else {
                    $temp_template = 'betaface_kairos_conflict';


                }

            } else if ($verdicts['kairos'] != 'N/A') {

                $array_data = ['_data' => $ethnic_data, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdicts['kairos'], '_Kairos' => $link_data_k];
                $temp_template = 'kairos';
            } else if ($verdicts['bettaface'] != 'N/A') {
                $temp_template = 'betaface';

                $array_data = ['_data' => $ethnic_data, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdicts['bettaface'], '_BetaFace' => $link_data_f];
            }


            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $templatesFace[$temp_template], $i, $id, $tmplate_order, $used_template, 'bk'); ///$currentConnectingWord;
            $sentences[$i] = $randomTemplate;


            unset($verdicts['kairos']);
            unset($verdicts['bettaface']);

        }


        $country_for='';
        $country_fs='';
        if ($verdicts['forebears_rank'] && $verdicts['forebears_rank']!= 'N/A') {

            $i++;

            $q = "SELECT a.lastname, v.`description_rank` FROM `data_actors_normalize` as a LEFT JOIN `data_forebears_verdict` as v  ON v.`lastname` = a.`lastname` 
                                        where  a.`aid` = " . $id;
            $q = Pdo_an::db_results_array($q);
            foreach ($q as $r) {
                $f_name = $r['lastname'];
                if ($r['description_rank']) {
                    $f_data = json_decode($r['description_rank'],1);
                    $keys = array_keys($f_data["total"]);
                    $firstKey = $keys[0];

                    $q = "SELECT `country_name` FROM `data_population_country` WHERE `cca2` = '" . $firstKey . "' limit 1";


                    $country_for = Pdo_an::db_get_data($q, 'country_name');
                }
            }

            $link_data =  self::$array_link['forebears_rank'];

            $verdict = $verdicts['forebears_rank'];


        }

        if ($verdicts['familysearch'] && $verdicts['familysearch'] != 'N/A') {

            $i++;

            $q = "SELECT a.lastname, v.`description` FROM `data_actors_normalize` as a LEFT JOIN `data_familysearch_verdict` as v  ON v.`lastname` = a.`lastname` 
                                        where  a.`aid` = " . $id;

            $q = Pdo_an::db_results_array($q);
            foreach ($q as $r) {
                $f_name = $r['lastname'];
                if ($r['description']) {
                    $f_data = json_decode($r['description'],1);

                    $keys = array_keys($f_data["total"]);
                    $firstKey = $keys[0];

                    if ($firstKey)
                    {
                        $q = "SELECT `country_name` FROM `data_population_country` WHERE `cca2` = '" . $firstKey . "' limit 1";
                        $country_fs = Pdo_an::db_get_data($q, 'country_name');
                    }

                }
            }

        }






        if ($country_for &&  $country_fs)
        {
            $ftemplate =   $templatesSurname["Familysearch_and_Forebears_conflict"];

            $array_data = [ '_he_she' => $personGender, '_personName' => $personName,
                '_ForebearsCountry' => $country_for,
                '_FamilysearchCountry' => $country_for,
                '_ForebearsVerdict' => $verdicts['forebears_rank'],
                '_FamilysearchVerdict' => $verdicts['familysearch'],
                '_Forebears' => self::$array_link['forebears_rank'],
                '_Familysearch' => self::$array_link['familysearch'],
                '_verdict' => $verdicts['forebears_rank']
            ];

        if ($country_for && $country_for == $country_fs && $verdicts['forebears_rank'] ==$verdicts['familysearch'])
        {

           $ftemplate =  $templatesSurname["Familysearch_and_Forebears"];

        }
        else if ( $country_for != $country_fs && $verdicts['forebears_rank'] == $verdicts['familysearch'])
        {
            $ftemplate =   $templatesSurname["Familysearch_and_Forebears_conflict_agree_verdict"];

        }

            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $ftemplate, $i, $id, $tmplate_order, $used_template, 'f');
            $sentences[$i] = $randomTemplate;

            unset($verdicts['forebears_rank']);
            unset($verdicts['familysearch']);
        }
        else {
            if ($country_for) {
                $array_data = ['_country' => $country_for, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdicts['forebears_rank'], '_source' => self::$array_link['forebears_rank']];
                [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $templatesSurname["Forebears"], $i, $id, $tmplate_order, $used_template, 'f');
                $sentences[$i] = $randomTemplate;
                unset($verdicts['forebears_rank']);
            }
            if ($country_fs) {
                $array_data = ['_country' => $country_fs, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdicts['familysearch'], '_source' => self::$array_link['familysearch']];
                [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $templatesSurname["Familysearch"], $i, $id, $tmplate_order, $used_template, 'fs');
                $sentences[$i] = $randomTemplate;
                unset($verdicts['familysearch']);
            }
        }


        if ($verdicts['surname']) {

            $i++;

            $q = "SELECT `wiki` FROM `data_actors_ethnicolr` WHERE `aid` =  " . $id;
            $data = Pdo_an::db_get_data($q,'wiki');

            if ($data)
            {
                $s_array = json_decode($data,1);
                $lastElement = end($s_array);
                $country = $lastElement;

                $country = preg_replace('/(?=[A-Z])/', ' ', $country);
            }

            $link_data = self::$array_link['surname'];
            $verdict = $verdicts['surname'];

            $array_data = ['_country' => $country, '_he_she' => $personGender, '_personName' => $personName, '_verdict' => $verdict, '_source' => $link_data];

            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $Ethnicolr, $i, $id, $tmplate_order, $used_template, 's');

            $sentences[$i] = $randomTemplate;

            unset($verdicts['surname']);
        }



        $valueCounts = array();
        foreach ($verdicts as $value) {
            if (!isset($valueCounts[$value])) {
                $valueCounts[$value] = 0;
            }
            $valueCounts[$value]++;
        }
        arsort($valueCounts);

        $sortedVerdicts = array();
        foreach ($valueCounts as $value => $count) {
            foreach ($verdicts as $key => $verdict) {
                if ($verdict === $value) {
                    $sortedVerdicts[$key] = $verdict;
                }
            }
        }


        if ($short) {


            $array_data = ['_personName' => $personName, '_verdict' => $person_verdict];

            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $meta_templates, 0, $id, $tmplate_order, $used_template, 'm', '');

            return "What ethnicity is " . $personName . "? " . $randomTemplate;
        }
       // $i++;

        $prev_prev_prev_iousVerdict = '';
        $prev_prev_iousVerdict = '';
        $previousVerdict = '';


        $w = 0;


        foreach ($sortedVerdicts as $source => $verdict) {
            if ($source != 'gender' && $verdict != 'N/A' && $source != 'verdict') {

                $personName_result = $personName;
                if (!$previousVerdict) {

                    if ($tmplate_order[$i] && $tmplate_order[$i] <= $ntc) {
                        $a = $tmplate_order[$i];
                    } else {
                        [$a, $used_template['n']] = self::get_rand($used_template['n'], $ntc);
                        $tmplate_order[$i] = $a;
                    }
                    $i++;


                    $randomTemplate = ucfirst($neutralTemplates[$a]);


                } else if ($verdict == $previousVerdict) {
                    if ($prev_prev_iousVerdict && $prev_prev_iousVerdict == $previousVerdict && $prev_prev_iousVerdict == $prev_prev_prev_iousVerdict && !$w) {
                        $personName_result = $personGender;


                        if ($tmplate_order[$i] && $tmplate_order[$i] <= $ctc) {
                            $a = $tmplate_order[$i];
                        } else {
                            [$a, $used_template['c']] = self::get_rand($used_template['c'], $ctc);
                            $tmplate_order[$i] = $a;
                        }
                        $i++;

                        $randomTemplate = ', ' . $confirmingTemplates[$a];
                        $w = 1;


                    } else if ($prev_prev_iousVerdict && $prev_prev_iousVerdict == $previousVerdict) {

                        if ($tmplate_order[$i] && $tmplate_order[$i] <= $ntc) {
                            $a = $tmplate_order[$i];
                        } else {
                            [$a, $used_template['n']] = self::get_rand($used_template['n'], $ntc);
                            $tmplate_order[$i] = $a;
                        }
                        $i++;

                        $randomTemplate = '. ' . ucfirst($neutralTemplates[$a]);

                        $w = 0;
                    } else {

                        if ($tmplate_order[$i] && $tmplate_order[$i] <= $ctc) {
                            $a = $tmplate_order[$i];
                        } else {
                            [$a, $used_template['c']] = self::get_rand($used_template['c'], $ctc);
                            $tmplate_order[$i] = $a;
                        }
                        $i++;

                        $personName_result = $personGender;
                        $randomTemplate = ', ' . $confirmingTemplates[$a];

                    }

                } else if ($verdict != $previousVerdict) {

                    if ($prev_prev_iousVerdict && $prev_prev_iousVerdict != $previousVerdict) {

                        if ($tmplate_order[$i] && $tmplate_order[$i] <= $ntc) {
                            $a = $tmplate_order[$i];
                        } else {
                            [$a, $used_template['n']] = self::get_rand($used_template['n'], $ntc);
                            $tmplate_order[$i] = $a;
                        }
                        $i++;

                        $randomTemplate = '<br>' . ucfirst($neutralTemplates[$a]);

                    } else {


                        if ($tmplate_order[$i] && $tmplate_order[$i] <= $rtc) {
                            $a = $tmplate_order[$i];
                        } else {
                            [$a, $used_template['r']] = self::get_rand($used_template['r'], $rtc);
                            $tmplate_order[$i] = $a;
                        }
                        $i++;

                        $randomTemplate = '. ' . ucfirst($refutingTemplates[$a]);


                    }

                }


                $randomTemplate = str_replace('_personName', $personName_result, $randomTemplate);
                $randomTemplate = str_replace('_verdict', $verdict, $randomTemplate);
                $randomTemplate = str_replace('_source', self::$array_link[$source], $randomTemplate);
                $randomTemplate = str_replace('she\'s', 'her', $randomTemplate);
                $randomTemplate = str_replace('he\'s', 'his', $randomTemplate);

                $currentConnectingWord = $randomTemplate;

                $sentences[] = $currentConnectingWord;


                $prev_prev_prev_iousVerdict = $prev_prev_iousVerdict;
                $prev_prev_iousVerdict = $previousVerdict;
                $previousVerdict = $verdict;
            }
        }


        if ($person_verdict) {

            $array_data = ['_personName' => $personName, '_verdict' => '<strong>' . $person_verdict . '</strong>'];
            [$randomTemplate, $tmplate_order, $used_template] = self::set_template($array_data, $templateVariations_verdict, 1, $id, $tmplate_order, $used_template, 'v', '');

            self::update_template($id, $tmplate_order, $tmplate_order_last);

            $sentences[0] = '<br>' . $randomTemplate;


            $content_data = implode('. ', $sentences);
            $content_data = str_replace('. ,', ',', $content_data);
            $content_data = str_replace('. .', '.', $content_data);
            $content_data = str_replace('..', '.', $content_data);

            if (isset($_GET['debug']))
            {

                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                TMDB::var_dump_table(['tmplate_order', $tmplate_order]);
                TMDB::var_dump_table(['used_template', $used_template]);
               // TMDB::var_dump_table(['template_arrays', $template_arrays]);



            }



            return "<h3>What ethnicity is " . $personName . "?</h3>" . $content_data;
        }


    }

    public static function get_actor_verdict($aid)
    {
//        $vd_data = unserialize(unserialize(OptionData::get_options('', 'critic_matic_settings')));
//        $verdict_method = 0;
//        if ($vd_data["an_verdict_type"] == 'w') {
//            $verdict_method = 1;
//        }

        $verdict_method = 1;

        $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');
        $array_int_convert = array(1 => 'W', 2 => 'EA', 3 => 'H', 4 => 'B', 5 => 'I', 6 => 'M', 7 => 'MIX', 8 => 'JW', 9 => 'NJW', 10 => 'IND');
        $array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');


        $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $aid . " ";
        $row = Pdo_an::db_results_array($sql);

        $result = [];

        $r = $row[0];

        if ($r['gender']) {
            $result['gender'] = $array_convert[$r['gender']];
        }

        foreach ($r as $rw => $v) {
            if (strstr($rw, 'n_') && $rw != 'n_verdict_weight' && $rw != 'n_verdict' && $rw != 'n_forebears') {

                $rws = substr($rw, 2);

                if ($v > 0) {
                    $result[$rws] = $array_compare_cache[$array_int_convert[$v]];
                } else {
                    $result[$rws] = 'N/A';
                }

            }

        }


        if ($verdict_method == 1) {
            $verdict = $r['n_verdict_weight'];
        } else if ($verdict_method == 0) {

            $verdict = $r['n_verdict'];
        }


        if ($verdict) {
            $result['verdict'] = $array_compare_cache[$array_int_convert[$verdict]];
        }

        return $result;
    }

    public static function get_actor_meta($aid)
    {
        if (is_numeric($aid)) {
            $aid = intval($aid);
            $q = "SELECT `id`,  `name` FROM `data_actors_imdb` WHERE `id` = ? ";
        } else {
            $q = "SELECT `id`,`name` FROM `data_actors_imdb` WHERE `slug` =? ";
        }

        $r = Pdo_an::db_results_array($q, [$aid]);
        $name = $r[0]['name'];
        $id = $r[0]['id'];

        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        $image_link = RWTimages::get_image_link($id, '270x338', '', '', '', 1);
        $image_link_big = RWTimages::get_image_link($id, '540x676', '', '', '', 1);
        $verdict = self::get_actor_verdict($id);

        return ['id' => $id, 'name' => $name, 'image' => $image_link, 'image_big' => $image_link_big, 'verdict' => $verdict];
    }

    private static function normalise_array($array)
    {
        $totalsumm = 0;


        foreach ($array as $index => $val) {
            $totalsumm += $val;
        }
        if ($totalsumm) {
            foreach ($array as $index => $val) {

                $array_result[$index] = round($val * 100 / $totalsumm, 2);


            }

            return $array_result;


        }
        return $array;
    }

    public static function actor_data_template($id)
    {

        $actor_meta = self::get_actor_meta($id);

        echo '<div id="' . $id . '" class="actor_main_container">';
        echo '<div class="actor_title_container">' . $actor_meta['name'] . '</div><span data-value="actor_popup" class="nte_info nte_right nte_open_down"></span>';

        ?>
        <div class="actor_card_min">
            <div class="actor_card_min_image">
                <div class="wrapper" style="min-width: 270px;min-height: 338px;">

                    <img loading="lazy" class="actor_poster" src="<?php echo $actor_meta['image'] ?>"
                        <?php if ($actor_meta['image_big']) { ?> srcset="<?php echo $actor_meta['image']; ?> 1x, <?php echo $actor_meta['image_big']; ?> 2x"<?php } ?> >
                </div>
            </div>
            <div class="actor_card_min_data">

                <?php echo self::actor_verdict($id, $actor_meta['verdict']); ?>

            </div>

        </div>

        <?php


        $array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');
        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        global $debug;
        if ($debug) {
            $array_timer[] = 'before functions  ' . timer_stop_data();
        }


        if (strstr($id, ',')) {
            $array_id = explode(',', $id);

        } else {

            $array_id[0] = $id;
        }

        $data = ($_POST['data']);

        if (!$data) {

            $data = ('{"movie_type":[],"movie_genre":[],"inflation":null,"actor_type":["star","main"],"diversity_select":"default","display_select":"date_range_international","country_movie_select":[],"ethnycity":{"1":{"crowd":1},"2":{"ethnic":1},"3":{"jew":1},"4":{"face":1},"5":{"face2":1},"6":{"forebears":1},"7":{"familysearch":1},"8":{"surname":1}}} ');

        }

        $data_object = json_decode($data);
        $ethnycity = $data_object->ethnycity;


        $w = '';
        $cat = array('star', 'main', 'extra');
        $ethnycity_string = urlencode(json_encode($ethnycity));
        foreach ($array_id as $id) {

            $id = intval($id);

            $sql = "SELECT * FROM `data_actors_imdb` where id =" . $id . " ";
            $r = Pdo_an::db_results_array($sql);

            $name = $r[0]['name'];

            $actor_updated = $r['lastupdate'];


////////get actor data
            !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
            $array_ints = INTCONVERT::get_array_ints();

            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $vd_data = unserialize(unserialize(OptionData::get_options('', 'critic_matic_settings')));
            $verdict_method = 0;
            if ($vd_data["an_verdict_type"] == 'w') {
                $verdict_method = 1;
            }

            $a_sql = "actor_id ='" . $id . "' ";

            ///$array_movie_result = get_movie_data_from_db('', $a_sql, '');
            ///$array_movie_result  = MOVIE_DATA::get_movie_data_from_db($id, $a_sql,'');

            $av = MOVIE_DATA::get_actor_meta($id);

///	tmdb_id	gender	ethnic	jew	kairos	bettaface	placebirth	surname	familysearch	forebears	crowdsource	verdict	verdict_weight	n_ethnic	n_jew	n_kairos	n_bettaface	n_surname	n_familysearch	n_forebears	n_crowdsource	n_verdict	n_verdict_weight	img	tmdb_img	last_update

            $face2 = $array_ints[$av['n_bettaface']];
            $face = $array_ints[$av['n_kairos']];
            $surname = $array_ints[$av['n_surname']];
            $etn = $array_ints[$av['n_ethnic']];
            $jew = $array_ints[$av['n_jew']];
            $crowd = $array_ints[$av['n_crowdsource']];
            $forebears = $array_ints[$av['n_forebears_rank']];
            $familysearch = $array_ints[$av['n_familysearch']];

            $verdict = $array_ints[$av['n_verdict']];

            if ($verdict_method == 1) {
                $verdict = $array_ints[$av['n_verdict_weight']];
                if (!$verdict) {
                    $verdict = $array_ints[$av['n_verdict']];
                }
            }


///var_dump($ethnycity);
///
///
///
            if ($debug) {
                $array_timer[] = 'get_movie_data_from_db  ' . timer_stop_data();
            }
            foreach ($ethnycity as $order => $data) {
                foreach ($data as $type => $enable) {

                    if ($type == 'forebears') {
                        echo '<p class="in_hdr">Forebears Surname Analysis:</p>';
                        if ($forebears) {


                            echo '<p class="verdict">Verdict: ' . $array_compare_cache[$forebears] . '</p>';
                            echo '<a class="source_link"  target="_blank" href="https://forebears.io/surnames">Source: https://forebears.io/surnames</a>';
                        } else echo '<p class="verdict">N/A</p>';
                    }
                    if ($type == 'familysearch') {
                        echo '<p class="in_hdr">FamilySearch Surname Analysis:</p>';
                        if ($familysearch) {


                            echo '<p class="verdict">Verdict: ' . $array_compare_cache[$familysearch] . '</p>';
                            echo '<a class="source_link"  target="_blank" href="https://www.familysearch.org/en/surname">Source: https://www.familysearch.org/en/surname</a>';
                        } else echo '<p class="verdict">N/A</p>';
                    }

                    if ($type == 'surname') {
                        echo '<p class="in_hdr">Surname Analysis:</p>';
                        if ($surname) {

                            $sql = "SELECT *  FROM data_actors_ethnicolr where aid =" . $id;

                            $q = Pdo_an::db_results_array($sql);
                            $r = $q[0];

                            $actor_data = [];


                            $data = $r['wiki'];
                            if ($data) {
                                $data = json_decode($data, 1);

                                $actor_data['EA'] += (float)$data[5] * 100;


                                $actor_data['EA'] += (float)$data[9] * 100;


                                $actor_data['I'] += (float)$data[13] * 100;


                                $actor_data['B'] += (float)$data[17] * 100;


                                $actor_data['M'] += (float)$data[21] * 100;


                                $actor_data['W'] += (float)$data[25] * 100;


                                $actor_data['W'] += (float)$data[29] * 100;


                                $actor_data['JW'] += (float)$data[33] * 100;
                                $actor_data['W'] += (float)$data[37] * 100;
                                $actor_data['W'] += (float)$data[41] * 100;
                                $actor_data['H'] += (float)$data[45] * 100;
                                $actor_data['W'] += (float)$data[49] * 100;

                                $actor_data['W'] += (float)$data[53] * 100;


                                arsort($actor_data);
                                $key = array_keys($actor_data);

                                $surname = $array_compare_cache[$r['verdict']];


                                if ($surname) {
                                    $surname = ucfirst($surname);
                                } else {
                                    $surname = 'N/A';
                                }
                                $actor_data = self::normalise_array($actor_data);

                                echo '<div class="small_desc">';
                                foreach ($actor_data as $i => $v) {
                                    echo $array_compare_cache[$i] . ': ' . $v . '%<br>';
                                }
                                echo '</div>';

                                $key = array_keys($actor_data);


                                echo '<p class="verdict">Verdict: ' . $surname . '</p>';
                                echo '<a class="source_link"  target="_blank" href="https://pypi.org/project/ethnicolr/">Source: https://pypi.org/project/ethnicolr/</a>';
                            } else echo '<p class="verdict">N/A</p>';
                        } else echo '<p class="verdict">N/A</p>';

                        if ($debug) {
                            $array_timer[] = 'after surname  ' . timer_stop_data();
                        }
                    }
                    if ($type == 'jew') {

                        echo '<p class="in_hdr">JewOrNotJew:</p>';
                        if ($jew) {
                            //////gett_jew_data

                            $jverdict = 'N/A';
                            $sql = "SELECT Verdict, actor_id FROM `data_actors_jew` WHERE actor_id=" . $id;
                            $q = Pdo_an::db_results_array($sql);


                            foreach ($q as $r) {


                                if ($r['Verdict']) {
                                    $jverdict = $r['Verdict'];
                                }

                            }

                            echo '<p class="verdict">Verdict: ' . $jverdict . '</p>';


                            echo '<a class="source_link" target="_blank" href="http://jewornotjew.com/">Source: http://jewornotjew.com/</a>';

                        } else {
                            echo '<p class="verdict">N/A</p>';
                        }
                        if ($debug) {
                            $array_timer[] = 'after jew  ' . timer_stop_data();
                        }
                    }
                    if ($type == 'face') {

                        echo '<p class="in_hdr">Facial Recognition by Kairos:</p>';
                        if ($face) {


                            $sql = "SELECT  *  FROM data_actors_race where actor_id =" . $id . " LIMIT 1";

                            $q = Pdo_an::db_results_array($sql);
                            $row = $q[0];
                            $imgid = $row['actor_id'];

                            $array_race['EA'] = $row['Asian'];
                            $array_race['B'] = $row['Black'];
                            $array_race['H'] = $row['Hispanic'];
                            $array_race['W'] = $row['White'];


                            $array_race = self::normalise_array($array_race);
                            arsort($array_race);

                            echo '<div class="small_desc">';
                            foreach ($array_race as $i => $v) {
                                echo $array_compare_cache[$i] . ': ' . $v . '%<br>';
                            }
                            echo '</div>';


                            echo '<p class="verdict">Verdict: ' . $array_compare_cache[$face] . '</p>';

                            echo '<a class="source_link" target="_blank" href="https://kairos.com/">Source: https://kairos.com/</a>';

                        } else   echo '<p class="verdict">N/A</p>';
                        if ($debug) {
                            $array_timer[] = 'after face  ' . timer_stop_data();
                        }
                    }
                    if ($type == 'face2') {

                        echo '<p class="in_hdr">Facial Recognition by Betaface:</p>';
                        if ($face2) {


                            $sql = "SELECT  *  FROM data_actors_face where actor_id =" . $id . " LIMIT 1";
                            $fr = Pdo_an::db_results_array($sql);
                            $brace = $fr[0]['race'];
                            $prcnt = $fr[0]['percent'];

                            if ($brace && $prcnt) {
                                echo '<div class="small_desc">';

                                echo ucfirst($brace) . ': ' . $prcnt * 100 . '%<br>';

                                echo '</div>';
                            }


                            echo '<p class="verdict">Verdict: ' . $array_compare_cache[$face2] . '</p>';


                            echo '<a class="source_link" target="_blank" href="https://www.betafaceapi.com/demo_old.html">Source: https://www.betafaceapi.com/</a>';

                        } else     echo '<p class="verdict">N/A</p>';

                        if ($debug) {
                            $array_timer[] = 'after face2  ' . timer_stop_data();
                        }
                    }
                    if ($type == 'ethnic') {


                        echo '<p class="in_hdr">Ethnicelebs:</p>';

                        if ($etn) {
                            $sql = "SELECT Ethnicity as ethnicity, Tags, actor_id, Link   FROM `data_actors_ethnic` WHERE  actor_id= " . $id . " limit 1";

                            $q = Pdo_an::db_results_array($sql);
                            foreach ($q as $r) {

                                if ($r['ethnicity']) {

                                    echo '<div class="small_desc">';
                                    echo $r['ethnicity'] . '<br>';
                                    echo '</div>';

                                }
                                if ($r['Link']) {
                                    $link = $r['Link'];
                                }

                            }

                            $ethnic_result = $etn;
                            if ($array_compare_cache[$ethnic_result]) {
                                echo '<p class="verdict">Verdict: ' . $array_compare_cache[$ethnic_result] . '</p>';
                            } else {
                                echo '<p class="verdict">Verdict: ' . $ethnic_result . '</p>';
                            }

                            if ($link) {

                                echo '<a class="source_link"  target="_blank" href="' . $link . '">Source: ' . $link . '</a>';
                            } else {
                                echo '<a class="source_link"  target="_blank" href="http://ethnicelebs.com/">Source: http://ethnicelebs.com/</a>';
                            }

                        } else {
                            echo '<p class="verdict">N/A</p>';

                        }

                        if ($debug) {
                            $array_timer[] = 'after ethnic  ' . timer_stop_data();
                        }
                    }
                    if ($type == 'crowd') {


                        echo '<p class="in_hdr">Crowdsource:</p>';

                        if ($crowd) {
                            $sql = "SELECT *   FROM `data_actors_crowd` WHERE  actor_id= " . $id . " and  	`status` =  1";

                            $rows = Pdo_an::db_results_array($sql);

                            foreach ($rows as $r) {

                                if ($r['verdict']) {

                                    echo '<div class="small_desc">';
                                    echo $array_compare_cache[$r['verdict']] . '<br>';
                                    echo '</div>';

                                }


                                if ($r['comment']) {
                                    echo '<p>User comment: ' . $r['comment'] . '</p>';

                                }
                                if ($r['link']) {
                                    echo '<a class="source_link"  target="_blank" href="' . $r['link'] . '">Source: ' . $r['link'] . '</a>';
                                }
                            }
                        } else {
                            echo '<p class="verdict">N/A</p>';


                        }

                        echo '<p>Please help improve ZR by correcting & adding data. <span data-value="custom_actor_crowdsource_' . $id . '" class="actor_crowdsource_link nte_info nte_open_down"></span></p>';


                        if ($debug) {
                            $array_timer[] = 'after ethnic  ' . timer_stop_data();
                        }
                    }
                }
            }

            if (!$verdict) $verdict = 1;
            if ($verdict) {
                echo '<p style="font-size: 20px; margin: 20px 0px; text-transform: uppercase" class="verdict">Final Verdict:  ' . $array_compare_cache[$verdict] . '</p>';

                echo '<p><a href="#" data-actor="' . $id . '" class="calculate_actor_data">Methodology</a></p>';

            }

            ///update data info


            $update_array = ['actror_data_update' => ['time' => $actor_updated, 'comment' => 'Actor IMDB data:']];

            foreach ($update_array as $i => $v) {

                $asctor_u .= MOVIE_DATA::last_update_container($id, $i, $v['time'], $v['comment'], 86400);

            }
            $update_container = '<div class="actor_update_data"><p>Last updated: </p>' . $asctor_u . '</div>';
            echo $update_container;


            echo '<a  target="_blank" class="admin_link" href="https://info.filmdemographics.com/analysis/include/scrap_imdb.php?actor_logs=' . $id . '">Actor info</a>';


            if ($debug) {
                $array_timer[] = 'end  ' . timer_stop_data();
                /// print_timer($array_timer);
            }

            echo '</div>';
        }
    }
}