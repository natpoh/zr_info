var disqus_config = function () {
};
var lastload = '';
var template_path =  "/wp-content/themes/custom_twentysixteen/template/ajax/";
var site_url = window.location.protocol +"//"+window.location.host;
var crowdsource_url = site_url+"/service/ajax/crowdsource.php"
var debug_mode = 0;
// if (window.location.host == 'zeitgeistreviews.com' || window.location.host == 'zgreviews.com') {
//     crowdsource_url = 'https://' + window.location.host + "/service/ajax/crowdsource.php";
// }

//crowdsource_url=  'https://service.zeitgeistreviews.com/ajax/crowdsource.php';


function attachScroller(distance, scroller, hasScrolled, scrollLeft) {
    if (jQuery(scroller).hasClass('should_fade')) {
        if ((scrollLeft < distance)) {
            jQuery(scroller).removeClass('is_hidden').addClass('is_fading');
        }

        if ((scrollLeft > distance) && (jQuery(scroller).hasClass('is_fading'))) {
            jQuery(scroller).removeClass('is_fading').addClass('is_hidden');
        }
    }
}

function recomendations_resset() {
    /// console.log(window.DISQUS_RECOMMENDATIONS);
    //window.DISQUS_RECOMMENDATIONS.reset();
}


function scroll_block(block_id) {
    if (window.innerWidth <= 450) {
        let scrollWrapper = document.querySelector('.single div#' + block_id + '  div.scroller');
        ///console.log(scrollWrapper);
        if (scrollWrapper) {
            scrollWrapper.scrollLeft = 40;
        } else {

            scrollWrapper = document.querySelector('.home div#' + block_id + '  div.scroller');
            ///console.log(scrollWrapper);
            if (scrollWrapper) {
                scrollWrapper.scrollLeft = 10;
            }
        }


    }
}

function discuss_config(data_object) {
    var page_url = data_object['page_url'];
    var page_identifier = data_object['page_identifier'];
    var title = data_object['title'];

    disqus_config = function () {
        this.page.url = page_url;
        this.page.identifier = page_identifier;
        this.page.title = title;
    };

    /// popup-container

    /// console.log(typeof DISQUS);

    if (typeof DISQUS == 'object') {
        DISQUS.reset({
            reload: true,
            config: function () {
                this.page.url = page_url;
                this.page.identifier = page_identifier;
                this.page.title = title;
            }
        });
        //  console.log('next');
    } else {
        //   console.log('first');
        (function () { // DON'T EDIT BELOW THIS LINE
            var d = document, s = d.createElement('script');
            s.src = 'https://hollywoodstfu.disqus.com/embed.js';
            s.setAttribute('data-timestamp', +new Date());
            (d.head || d.body).appendChild(s);
        })();

    }

    if (data_object['data_comments']) {
        if (jQuery('div[id="disqus_recommendations"]').attr('id')) {
            var third_scripts = {omendations: 'https://' + data_object['data_comments'] + '.disqus.com/recommendations.js'};
            use_ext_js(recomendations_resset, third_scripts);


        }

    }

}


function initializeScroller(mobile, scroller) {

    var hasScrolled = false;
    var scrollLeft = 0;
    var itemWidth = 0;
    var parentWidth = jQuery(scroller).parent().outerWidth();
    var childScroller = jQuery(scroller).find('.scroller');

    childScroller.children().each(function () {
        itemWidth += jQuery(this).outerWidth();
    });
    //console.log(parentWidth,itemWidth,scroller);
    var distance;
    if (mobile) {
        distance = 30;
    } else {
        distance = 50;
    }

    if (itemWidth < (parentWidth + distance)) {
        jQuery(scroller).removeClass('should_fade');
    }
    //console.log(itemWidth,(parentWidth + distance));

    var targetScroll;
    childScroller.scroll(function (e) {
        hasScrolled = true;
        if (targetScroll == null) {
            targetScroll = jQuery(e.target);
        }
        scrollLeft = targetScroll.scrollLeft();
    });

    setInterval(function () {
        if (hasScrolled) {
            attachScroller(distance, scroller, hasScrolled, scrollLeft);
            hasScrolled = false;
        }
    }, 250);
}

function add_rating_duoble_block(type, value_female, data, desc, usescroll, new_api = false) {

    $api = `<div class="note nte ${type}"><div class="btn"><span  class="num_1">${value_female}</span><span  class="num_2">${data}</span></div>
                 <div class="nte_show"><div class="nte_in"><div class="nte_cnt"><div class="note_show_content_${usescroll}" >${desc}</div></div></div></div>
                 </div>`;

    return $api;

}

function add_rating_block(type, data, desc, usescroll, new_api = false, dwn = '') {

    $api = `<div class="note nte ${type}"><div class="btn">${data}</div>
                 <div class="nte_show ${dwn}"><div class="nte_in"><div class="nte_cnt"><div class="note_show_content_${usescroll}" >${desc}</div></div></div></div>
                 </div>`;

    return $api;

}

function popup_cusomize(type, a, b) {

    if (type == 'row_main' || type == 'row_inner') {
        return '<span class="pp_row ' + type + '"><span class="pp_rl">' + a + '</span><span class="pp_rr">' + b + '</span></span>';
    }


    return '<p class="' + type + '">' + a + '</p>';


}

function family_rating(family_data) {

    var family_data_result = '';

    if (family_data) {
        jQuery.each(family_data, function (a, b) {
            if (a && b) {
                /// console.log(b,typeof b );
                if (a == "mpaa") {
                    family_data_result += popup_cusomize('row_main', a.toUpperCase(), b);
                } else if (a == "mpaa_rus") {
                    a = "mpaa rus";
                    family_data_result += popup_cusomize('row_main', a.toUpperCase(), b);
                } else if (a == "imdb" || a == "cms_rating" || a == "dove_rating") {
                    if (a == "imdb") {
                        a = 'IMDB';
                    }
                    if (a == "cms_rating") {
                        a = 'Commonsensemedia';
                        b = JSON.parse(b);
                    } else if (a == "dove_rating") {
                        a = 'Dove';
                        b = JSON.parse(b);
                    }


                    var d_content = '';
                    var imdb_rating_colors = {"Mild": 'yelow', "Moderate": 'orange', "Severe": 'red'};
                    var cms_rating_colors = {"1": 'green', "2": 'yelow', "3": 'orange', "4": 'red', "5": 'red'};
                    var cms_rating_plus = {
                        "educational": '1',
                        "message": '1',
                        "role_model": '1',
                        "Faith": '1',
                        "Integrity": '1',
                        "diverse": '2'
                    };

                    jQuery.each(b, function (c, d) {
                        if (a == 'IMDB' || a == 'IMDb') {
                            a = 'IMDb';

                            if (d != 'None') {


                                c = c[0].toUpperCase() + c.slice(1);
                                var data_class = imdb_rating_colors[d];
                                d = '<span  class="color_rating ' + data_class + '">' + d + '</span>';
                                d_content += popup_cusomize('row_inner', c.toUpperCase(), d);

                            } else {

                            }
                        } else if (Number(d) > 0) {
                            var data_class = cms_rating_colors[d];
                            if (cms_rating_plus[c] == 1) {
                                data_class = 'green';
                            } else if (cms_rating_plus[c] == 2) {
                                data_class = 'gray';
                            }
                            if (c == 'role_model') {
                                c = 'role model';
                            }


                            c = c[0].toUpperCase() + c.slice(1);

                            d = '<span  class="color_rating ' + data_class + '">' + d + '/5</span><br>';
                            d_content += popup_cusomize('row_inner', c, d);
                        }
                    });
                    ;

                    if (d_content) {
                        family_data_result += popup_cusomize('row_head', a, '') + d_content;
                    }

                }

            }

        });
    }

    return family_data_result;
}

function create_total_rating(obj, only_tomatoes, rt_gap) {
    let content_rating = '';

    let rt_class = '';

    if (Number(obj.rt_rating) > 0 || Number(obj.rt_aurating) > 0) {


         if (rt_gap != 0) {
             var ttcomment = '';
             if (Number(obj.rt_rating) > 0  && !obj.rt_aurating)
             {
                 ttcomment = "The average Rotten Tomatoes critic score is " + Number(obj.rt_rating)  + "%."
             }
             else if (!obj.rt_rating  && Number(obj.rt_aurating)>0)
             {
                 ttcomment = "The average Rotten Tomatoes audience score is " +  Number(obj.rt_aurating)  + "%."
             }
            else if (rt_gap > 10) {
                ttcomment = "The Rotten Tomatoes audience rated this " + rt_gap + "% higher than the critics."
            } else if (rt_gap < -10) {
                ttcomment = "The Rotten Tomatoes audience rated this " + rt_gap + "% lower than the critics."
            } else if (rt_gap > 0) {
                ttcomment = "The Rotten Tomatoes audience rated this " + rt_gap + "% higher than the critics."
            } else if (rt_gap < 0) {
                ttcomment = "The Rotten Tomatoes audience rated this " + rt_gap + "% lower than the critics."
            } else if (obj.rt_rating == obj.rt_aurating) {
                ttcomment = "Rotten Tomatoes audience rated it equally with the critics."
            }
            content_rating += popup_cusomize('popup_header', ttcomment);

            content_rating += '<div><div class="rotten_tomatoes_score">';
        } else {
            content_rating += '<div class="exlink" id="rt"><span>RottenTomatoes:</span><div class="rotten_tomatoes_score">';

        }


        if (Number(obj.rt_rating) > 0) {
            if (Number(obj.rt_rating) >= 60) {
                rt_class = '_max_tomatoes';
            }

            content_rating += create_rating_star(obj.rt_rating, 'rotten_tomatoes' + rt_class);

        }


        if (Number(obj.rt_aurating) > 0) {
            if (Number(obj.rt_aurating) >= 60) {
                rt_class = '_max_tomatoes';
            }
            content_rating += create_rating_star(obj.rt_aurating, 'rotten_tomatoes_audience' + rt_class);

        }
        content_rating += '</div></div>';
    }

    if (!only_tomatoes) {

        function formatWordsList(word) {
            let formattedWord = word.replace('_rating', '');
            formattedWord = formattedWord.replace('_', ' ');
            formattedWord = formattedWord.charAt(0).toUpperCase() + formattedWord.slice(1);
            return formattedWord;
        }


        Object.keys(obj).forEach(function (key) {

            var value = obj[key];

            let name = key;

            if (key == 'metacritic_userscore') {
                name = 'Metacritic User';
            } else if (key == 'audience_rating') {
                name = 'ZR Audience';
            } else if (key == 'imdb_rating') {
                name = 'IMDb';
            }
            else if (key == 'ofdb_rating') {
                name = 'OFDb';
            }
            else if (key == 'kinop_rating') {
                name = 'Kinopoisk';
            } else if (key == 'Animelist') {
                name = 'MyAnimeList';
            } else {
                name = formatWordsList(key);
            }

            let nolink ='';
           if (key =='audience_rating')
           {

               nolink =' no_link '
           }

            if (Number(value) > 0) {
                if (key.indexOf('rt_') == -1 && key != 'total_rating') {

                    content_rating += '<div class="exlink'+nolink+'" id="' + key + '"><span>' + name + ':</span>' + create_rating_star(value, key) + '</div>';
                }


            }

        });


        // if (obj.kinop_rating > 0)
        //     content_rating += '<div class="exlink" id="kinop"><span>Kinopoisk:</span>' + create_rating_star(obj.kinop_rating, 'kinopoisk') + '</div>';
        //
        // if (obj.douban_rating > 0)
        //     content_rating += '<div class="exlink" id="douban"><span>Douban:</span>' + create_rating_star(obj.douban_rating, 'douban') + '</div>';


    }


    return content_rating;
}


function create_context_rating(obj, hollywood) {

    let content_rating = '';

    //if (hollywood)
    //  content_rating += '<div><span>ZR BS Score:</span>' + create_rating_star(hollywood, 'hollywood') + '</div>';
    if (obj.vote)
        content_rating += '<div><span>Support:</span>' + create_rating_star([obj.vote, obj.vote_type], 'vote') + '</div>';
    if (obj.patriotism)
        content_rating += '<div><span>Neo-Marxism:</span>' + create_rating_star(obj.patriotism, 'patriotism') + '</div>';
    if (obj.misandry)
        content_rating += '<div><span>Feminism:</span>' + create_rating_star(obj.misandry, 'misandry') + '</div>';
    if (obj.affirmative)
        content_rating += '<div><span>Affirmative Action:</span>' + create_rating_star(obj.affirmative, 'affirmative') + '</div>';
    if (obj.lgbtq)
        content_rating += '<div><span>Gay Stuff:</span>' + create_rating_star(obj.lgbtq, 'lgbtq') + '</div>';
    if (obj.god)
        content_rating += '<div><span>Fedora Tipping:</span>' + create_rating_star(obj.god, 'god') + '</div>';

    return content_rating;
}


function create_gender_desc(value) {

    let fcontent = '<div class="gray_comment">We love women at ZR!<br>' + 'We just grow tired of feminist quotas. The \"<a href="https://en.wikipedia.org/wiki/F-rating" target="_blank"><u>F rated</u></a>\" label is a good proxy for that, and the <a href="https://en.wikipedia.org/wiki/Bechdel_test" target="_blank"><u>Bechdel Test</u></a> sometimes can be. But both are scarcely applied. Thus, a scan of the cast\'s gender is more ubiquitous.</div>';
    let hcontent = popup_cusomize('popup_header', value + '% of the Stars & Main Cast are Female.');

    return hcontent;
}

function create_diversity(diversity_data, value) {

    diversity_data_content = popup_cusomize('popup_header', `${value}% of the Stars & Main Cast are <a href="https://www.facebook.com/868164246578472/posts/the-cast-of-black-panther-is-hella-diverse/1236999296361630/" target="_blank:"><u>"diverse."</u></a>`);

    if (diversity_data) {

        const ordered = [];
        Object.keys(diversity_data).forEach(function (key) {
            ordered.push({'r': key, 'c': diversity_data[key]});
        });
        ordered.sort((a, b) => a.c < b.c ? 1 : -1);


        jQuery.each(ordered, function (a, b) {

            diversity_data_content += popup_cusomize('row_inner', b.r, b.c + '%');

        });
        //diversity_data_content += '<p class="gray_comment">We love all demographics at ZR!<br>We just grow tired of forced "<a href="https://www.facebook.com/868164246578472/posts/the-cast-of-black-panther-is-hella-diverse/1236999296361630/" target="_blank:"><u>diversity.</u></a>" Like Blackwashing superheroes and writing Europeans out of their own history. Unfortunately, we can\'t automatically scan for forced diversity, only analyze the entire cast. On the bright side, you can use these percentages to help support <a href="https://zeitgeistreviews.com/analytics/tab_ethnicity/budget_12000-500000/minus-genre_animation/release_1984-2022/type_movies/showcast_2_1/stacking_percent/vis_regression/xaxis_release/yaxis_simpson/setup_noclasters_inflation" target="_blank"><u>truly diverse</u></a> media as well!</p>';

    }

    return diversity_data_content;
}

function abbreviateNumber(number) {
    const abbreviations = [
        {value: 1e18, symbol: "E"},
        {value: 1e15, symbol: "P"},
        {value: 1e12, symbol: "T"},
        {value: 1e9, symbol: "B"},
        {value: 1e6, symbol: "M"},
        {value: 1e3, symbol: "K"},
    ];

    for (let i = 0; i < abbreviations.length; i++) {
        if (number >= abbreviations[i].value) {
            return (
                (number / abbreviations[i].value).toFixed(1).replace(/\.0$/, "") +
                abbreviations[i].symbol
            );
        }
    }

    return number.toString();
}

function create_rating_content(object, m_id, search_block = 0) {
    let content = '';


    if (object['type']) {
        var movie_type = object['type'];
    }
    //console.log(movie_type);

    if (object['indie']) {
        //console.log(object['indie']);
        let data = '';
        if (object['indie']['productionBudget'] > 0 || object['indie']['box_usa'] > 0 || object['indie']['box_intern'] || object['indie']['box_world'] > 0) {


            if (object['indie']['box_usa'] > 0 || object['indie']['box_world'] > 0) {
                data = popup_cusomize('row_text_head', 'Box office:');
            }


            if (object['indie']['productionBudget'] > 0)
                data += popup_cusomize('row_inner', 'Budget:', '$' + abbreviateNumber(object['indie']['productionBudget']));
            if (object['indie']['box_usa'] > 0)
                data += popup_cusomize('row_inner', 'Domestic:', '$' + abbreviateNumber(object['indie']['box_usa']));
            if (object['indie']['box_intern'] > 0)
                data += popup_cusomize('row_inner', 'International:', '$' + abbreviateNumber(object['indie']['box_intern']));


            if (object['indie']['box_world'] > 0)
                data += popup_cusomize('row_inner', 'Worldwide:', '$' + abbreviateNumber(object['indie']['box_world']));
        }


        let recycle = '';
        if (object['indie']['recycle']) {


            if (object['indie']['recycle']['enabled']) {
                recycle = ' recycle ';
            }

            if (object['indie']['recycle']['enabled'] || (object['indie']['recycle']['keywords'] && object['indie']['recycle']['keywords']['lasy_grab'])) {

                data += popup_cusomize('row_text_head row_margin l_c_g', 'Possible lazy cash grab:') + '<span data-value="lazy_cash_grab_popup" class="nte_info nte_right"></span>';
            }


            if (object['indie']['recycle']['keywords']) {

                if (object['indie']['recycle']['keywords']['lasy_grab']) {

                    data += popup_cusomize('row_link', object['indie']['recycle']['keywords']['lasy_grab']);
                }
            }

            if (object['indie']['recycle']['franchise']) {

                data += popup_cusomize('row_text_head', 'Franchise:');
                data += popup_cusomize('row_link', object['indie']['recycle']['franchise']);
            }


            if (object['indie']['recycle']['keywords']) {
                if (object['indie']['recycle']['keywords']['remake_words']) {

                    data += popup_cusomize('row_text_head row_margin', 'Adaptation/remake:');
                    data += object['indie']['recycle']['keywords']['remake_words'];
                }

            }


        }
        let big_b = '';


        if (object['indie']['production']) {

            if (object['indie']['production'][1]) {
                big_b = ' big_business ';
            }
            if (object['indie']['production'][1] || object['indie']['production'][2]) {
                data += popup_cusomize('row_text_head row_margin big_b_i', 'Possible Big Business:') + '<span data-value="big_business_popup" class="nte_info nte_right"></span>';
            }
            data += popup_cusomize('row_text_head ', 'Production:');

            jQuery.each(object['indie']['production'], function (a, b) {

                jQuery.each(b, function (c, d) {

                    data += popup_cusomize('row_link ', d);

                });

            });

        }


        let dwn = 'dwn';
        if (search_block) {
            dwn = '';
        }

        content += add_rating_block('indie' + recycle + big_b, ' ', data, 2, true, dwn);
    }
    //console.log(object);
    if (object['type'] != 'videogame') {
        if (object.total_rating && (object.total_rating.rt_gap > 0 || object.total_rating.rt_rating > 0 || object.total_rating.rt_aurating > 0)) {

            let total_gap_str = 'N/A';

            if (object.total_rating.rt_rating == object.total_rating.rt_aurating )
            {

                total_gap_str = '0%';
            }


            var total_gap = object.total_rating.rt_gap;
            let rating_color = '';
            if (total_gap) {
                total_gap = Number(total_gap);

                if (total_gap > 0 || total_gap < 0) {


                    let rating_color = '';

                    if ((total_gap) > 10) {
                        rating_color = 'green_rt';
                    }
                    if ((total_gap) < -10) {
                        rating_color = 'red_rt';
                    }
                }
                total_gap_str = total_gap + '%'
            }

            let total_tomatoes_content = create_total_rating(object.total_rating, 1, total_gap);

            content += add_rating_block('rt_gap ' + rating_color, total_gap_str, total_tomatoes_content, 4, true);

        } else {
            let rating_color = 'gray_rt';
            let total_gap_str = 'N/A';
            let total_tomatoes_content = 'No <b class="exlink" id="rt">Rotten Tomatoes</b> ratings imported yet.';

            content += add_rating_block('rt_gap ' + rating_color, total_gap_str, total_tomatoes_content, 4, true);
        }
    }


    if ((object['diversity'] || object['diversity_data'] || object['female'] || object['male']) && object['type'] != 'videogame') {

        let fem_desk = '';
        let diversity_data_content = '';
        let value_female = 'N/A';
        let value = 'N/A';
        if (object['female'] || object['male']) {
            // block_class = 'gender';

            // content += add_rating_block(block_class, value + '%', hcontent, 2, true);
            value_female = Number(object['female']);
            value_female = value_female.toFixed(0);
            fem_desk = create_gender_desc(value_female);

            value_female = value_female + '%';
        }


        if (object['diversity_data']) {

            value = Number(object['diversity']);
            value = value.toFixed(0);
            if (!value)
                value = 0;

            var diversity_data = object['diversity_data'];
            diversity_data_content = create_diversity(diversity_data, value);

            value = value + '%';
        }
        content += add_rating_duoble_block('diversity', value_female, value, fem_desk + diversity_data_content, 3, true);

    }

    ///family rating

    block_class = 'family_friendly';
    let value = '';

    ///LGBTQ content included  console.log(object);

    let lgbt_class = '';
    let woke_class = '';
    let lgbt_warning_text = '';
    let woke_warning_text = '';


    value = Number(object['family']);

    value = value.toFixed(2);

    let rating_color = 'noffrating';

    if (value > 0) {
        rating_color = 'green';
        if (value < 3) {
            rating_color = 'orange';
        }
        if (value < 2) {
            rating_color = 'red';
        }
        let family_data_result = '';
    }


    let family_data_result = '';

    if (object['family_data']) {

        var family_data = object['family_data'];

        family_data = JSON.parse(family_data);
        family_data_result = family_rating(family_data);
    }
    lgbt_warning_text = '';
    // console.log(object);
    if (object['lgbt_warning'] == 1 || object['qtia_warning'] == 1) {
        let ltext = '';
        let qtext ='';

        if (object['lgbt_text'] && object['qtia_text'] ) {
            ltext+= '<span class="bg_rainbow">' + object['lgbt_text']+ '</span>';
            qtext+= '<span class="bg_rainbow">' +object['qtia_text']+ '</span>';

            lgbt_warning_text = popup_cusomize('row_text_head', 'LGBTQ content included:<span data-value="lgbt_popup" class="nte_info"></span>') ;

            lgbt_warning_text+= popup_cusomize('row_text_head', 'LGB:') + popup_cusomize('row_text', ltext);
            lgbt_warning_text+= popup_cusomize('row_text_head', 'QTIA+:') + popup_cusomize('row_text', qtext);
        }

        else if (object['lgbt_text']) {
            ltext+= '<span class="bg_rainbow">' + object['lgbt_text'] + '</span>';
            lgbt_warning_text= popup_cusomize('row_text_head', 'LGBTQ content included:<span data-value="lgbt_popup" class="nte_info"></span>') + popup_cusomize('row_text', ltext);
        }
        else if (object['qtia_text']) {
            qtext+= '<span class="bg_rainbow">' + object['qtia_text'] + '</span>';
            lgbt_warning_text= popup_cusomize('row_text_head', 'LGBTQ content included:<span data-value="lgbt_popup" class="nte_info"></span>') + popup_cusomize('row_text', qtext);
        }

        lgbt_class = ' lgbt ';





    }
    woke_warning_text = '';

    if (object['woke'] == 1) {
        let woketext = '';
        if (object['woke_text']) {
            woketext = '<span class="bg_woke">' + object['woke_text'] + '</span>';
        }
        woke_class = ' woke ';
        woke_warning_text = popup_cusomize('row_text_head', 'Possibly woke elements:<span data-value="woke_popup" class="nte_info"></span>') + popup_cusomize('row_text', woketext);
    }


    /// console.log(movie_type);

    let array_title = {'movie': 'film', 'tvseries': 'show', 'videogame': 'game'};
    let name = 'film';
    if (array_title[movie_type]) {
        name = array_title[movie_type];
    }
    let scorecontent = '';
    if (value > 0) {
        scorecontent = popup_cusomize('popup_header', `This ${name} gets a ${value}/5 family friendly score `);
    } else if (value == 0) {
        value = 'N/A';
        scorecontent = 'No MPAA or IMDb parental guidance data has been imported yet.';

    }

    scorecontent+= '<br>Please help improve our site and <div class="add_pg_rating_button"><a href="#" class="empty_ff_rating">add a Family Friendly Rating.</a></div>';

    let rating_link = popup_cusomize('row_link', '<a href="#" class="read_more_rating">CONTENT BREAKDOWN</a>');
    rating_link += popup_cusomize('row_link', '<a href="#" class="how_calculate_rating">Methodology</a>')

    content += add_rating_block(block_class + ' ' + lgbt_class + woke_class + rating_color, value,  lgbt_warning_text + woke_warning_text + family_data_result +scorecontent + rating_link, 1, true);


    if (object.total_rating && object.total_rating.total_rating > 0) {
        let total_rating_star = create_rating_star(object.total_rating.total_rating, '');


        let rating_link_t = popup_cusomize('row_link', '<a href="#" class="how_calculate_rwt_rating">Methodology</a>');

        var content_rating_adata = create_total_rating(object.total_rating, '', '') + rating_link_t;

        var rdata = '<div class="rwt_stars" title="ZR Aggregate Rating">' + total_rating_star + '</div>';
        content += add_rating_block('rwt_stars', rdata, content_rating_adata, 'rwt_rt', true);

    } else {
        var rdata = '<div class="rwt_stars not_rated" title="ZR Aggregate Rating"><span class="rating_result btn raitng_empty"></span></div>';
        let rcontent = 'No ratings imported yet,<br> <b class="edit_review">Write a review?</b>';
        content += add_rating_block('rwt_stars', rdata, rcontent, 'rwt_rt', true);
    }


    content += '<div id="' + m_id + '"  class="note edit"><div class="note_togle">' +
        '<div  class="edit_area  note_show">' +
        '<div class="edit_comment"><div class="desc">Add Comment</div></div>' +
        '<div   class="edit_review"><div class="desc">Write a Review</div></div>' +
        '<div   class="edit_critic"><div class="desc">Submit Review Link</div></div>' +
        '<div class="edit_family_rating"><span class="f_name"></span><div class="desc">Edit Family Friendly Rating</div></div>' +
        '</div></div>';


    if (content) {
        content = `<div id="${m_id}"  class="rating_block">${content}</div>`;
    }

    return content;
}

function set_video_scroll(data, block_id, append = '') {


    if (data) {

        data = JSON.parse(data);

        if  (data['count'] > 0 && data['tmpl'])
        {
            if (!append) {
                jQuery('div[id="' + block_id + '"]').parents('section').addClass('loaded');
            }
            var content = '';
            var tmpl = data['tmpl'];
            var tmpl_type = data['type'];
            if (tmpl_type == 'actors_data') {

                if (data['result']) {


                    jQuery.each(data['result'], function (a, b) {

                        if (b) {
                            if (b['content_data']) {
                                content += b['content_data'];
                            }

                        }

                    });
                    let ths = jQuery('div[id="' + block_id + '"]');

                    ths.html('<div class="column_content flex scroller">' + content + '</div>');
                    // let prnt =ths.parents('div.column');
                    // let head = prnt.find('div.column_header');
                    // let title = head.html();
                    // head.html('<div class="i_head">'+title+data['html']+'</div>')
                }
            } else {
                for (var i = 1; i <= Number(data['count']); i++) {
                    content += tmpl.replace('{id}', i);
                }

                ///console.log(block_id);
                if (append) {
                    jQuery('div[id="' + block_id + '"] .column_content' + append).html(content);
                } else {
                    jQuery('div[id="' + block_id + '"]').html('<div class="column_content flex scroller">' + content + '</div>');
                }

                if (data['rating'] && typeof (data['rating']) == 'string') {
                    data['rating'] = JSON.parse(data['rating']);
                }

                if (data['result']) {

                    var array_movie = [];
                    jQuery.each(data['result'], function (a, b) {

                        b.mid = a;
                        array_movie.push(b);

                    });
                    if (block_id == 'video_scroll') {
                        array_movie.sort((a, b) => a.release < b.release ? 1 : -1);
                    }


                    var i = 1;
                    jQuery.each(array_movie, function (mi, b) {
                        var a = b.mid;
                        var block = jQuery('div[id="' + block_id + '"] .column_content' + append + ' .loading[id="' + i + '"]');

                        if (b.genre == 'load_more') {
                            block.html('<a class="load_more" href="' + b.link + '/">' + b.title + '</a>');
                            block.removeClass('loading');
                        } else {
                            let array_title = {'movies': 'Movie', 'tvseries': 'TV Show', 'videogame': 'Game'};
                            let mtitle = 'Movie';
                            if (array_title[b.type]) {
                                mtitle = array_title[b.type];
                            }
                            var image = '<a class="image" href="' + b.link + '/" title="' + b.title + '">' +
                                '<span class="card_movie_type ctype_' + b.type + '" title="' + mtitle + '"></span>\n' +
                                '<img loading="lazy" class="poster"  srcset="' + b.poster_link_small + ' 1x, ' + b.poster_link_big + ' 2x" alt="">\n' +
                                '</a>';
                            block.find('.wrapper').html(image);
                            block.find('.content h2').html('<a href="' + b.link + '/" title="' + b.title + '">' + b.title + '</a>');
                            block.find('.content p').html(b.genre);
                            block.removeClass('loading');
                            if (b.content_pro) {
                                block.find('.mbp_f').html(b.content_pro);
                                let ecount = 0;
                                let user_class = '';

                                let pid = b.pid;
                                let ccount = '';

                                if (data['reaction']) {
                                    if (data['reaction']['total']) {
                                        if (data['reaction']['total'][pid]) {
                                            ecount = data['reaction']['total'][pid];
                                        }
                                    }
                                    if (data['reaction']['user']) {
                                        if (data['reaction']['user'][pid]) {
                                            user_class = ' emotions_custom ' + data['reaction']['user'][pid];
                                        }
                                    }
                                    if (data['reaction']['comments']) {


                                        if (data['reaction']['comments'][pid]) {

                                            ccount = data['reaction']['comments'][pid];
                                        }
                                    }
                                }

                                var disquss_class = '';
                                var ccount_data = ' ';
                                if (ccount) {
                                    disquss_class = ' comment_count';
                                    ccount_data = '<span  class="disquss_coment_count">' + ccount + '</span>';
                                } 

                                let ptitle = b.pid_title;
                                if (!ptitle)
                                    ptitle = '';
                                if (!ecount)
                                    ecount = '';

                                block.find('.review_comment_data').html('<a  href="#" data_title="' + ptitle + '" class="disquss_coment' + disquss_class + '">' + ccount_data + '</a><a href="#"   class="emotions  ' + user_class + '  "><span class="emotions_count">' + ecount + '</span></a>').attr('data-id', b.pid);


                            }
                            if (data['rating']) {
                                if (b.content_pro) {
                                    if (b['m_id']) {
                                        a = b['m_id'];
                                    }
                                }

                                if (data['rating'][a]) {

                                    let block_img = block.find('div.image');

                                    if (block_img) {
                                        if (block_img.html()) {
                                            let rating_content = create_rating_content(data['rating'][a], a);
                                            if (rating_content) {
                                                block_img.append(rating_content);
                                            }
                                        }
                                    }
                                }

                            }
                        }

                        i++;
                    });

                    init_short_codes();

                }
            }


            if (block_id == 'review_scroll') {

                if (data['mid'] && data['mid'] > 0) {

                    let custom_ctntn = `<div class="review_details"><a href="#" id-data="${data['mid']}" class="add_critic">Submit a review link</a></div>


<section class="dmg_content inner_content" id="actor_data_dop">
   <div class="column_header"><h2>Blogosphere / Vlogosphere:</h2></div>
        <div id="google_search_review" data-name="reviews_search" data-value="${data['mid']}" class="page_custom_block not_load"></div>
</section>`;

                    jQuery('div[id="' + block_id + '"]').append(custom_ctntn);
                    load_next_block('google_search_review');
                }


            }

        } else {


            if (block_id == 'review_scroll') {

                if (data['mid'] && data['mid'] > 0) {
                    let prnt = jQuery('div[id="' + block_id + '"]').parents('section.inner_content');
                    var block = jQuery('div[id="' + block_id + '"]');

                    let custom_ctntn = `<a href="#" id-data="${data['mid']}" class="add_critic">Submit a review link</a><div class="page_custom_block reviews_gs_block" id="reviews_gs_block"></div>`;

                    block.html(custom_ctntn).show().addClass('loaded');

                    if (data['gdata']) {

                        prepare_search_data('reviews_gs_block', data['gdata']);
                    }
                    return;
                }
            }


            if (!append) {

                let prnt = jQuery('div[id="' + block_id + '"]').parents('section.inner_content');
                prnt.remove();
                var tmpl_type = data['type'];

                if (tmpl_type == 'actors_data') {
                    //  jQuery('.actor_details>div').html('  Sorry. No actor data available yet. Stay tuned.').addClass('dmg_content');
                }

            }
        }


    } else {


        if (!append) {
            let prnt = jQuery('div[id="' + block_id + '"]').parents('section.inner_content');
            prnt.remove();
        }


    }
}

function add_rating_row(title, content, id, content_text) {
    let id_ins = '';
    let open_rating = '';
    let open_rating_container = '';

    if (id) {
        id_ins = `id="${id}"`;
        open_rating = `<a id="op"  class="open_rating open_ul" href="#"></a>`;

        if (content_text) {
            open_rating_container = `<div style="display: none" class="open_rating_container note_show"><div class="note_show_content_1">${content_text}</div></div>`;
        }
    }


    let row = `<div class="rating_row" ${id_ins}><span class="rating_row_title">${title}</span><span class="rating_row_content">${content}${open_rating}</span>${open_rating_container}</div>`;
    return row;
}

function create_rating_star(rating, type, num = 10) {


    if (type == 'big_rating') {


        if (rating) {
            return '<span class="big_rating ' + type + '"><strong>' + rating + '</strong>/' + num + '</span>';
        }

    } else if (type.indexOf('rotten_tomatoes') != -1) {
        rating = Number(rating);
        if (rating > 0) {
            return '<span class="' + type + '_rating"><strong>' + rating + '%</strong></span>';
        } else
            return '';

    } else if (type == 'vote') {


        //  console.log(rating[1]);
        let array = {};
        if (rating[1] == 'tvseries' || rating[1] == 'movie') {
            array = {
                1: ['pay_to_watch', 'Pay To Watch!'],
                2: ['skip_it', 'Skip It'],
                3: ['watch_if_free', 'Watch If Free.']
            };
        } else if (rating[1] == 'videogame') {
            array = {
                1: ['pay_to_watch', 'Pay to play it!'],
                2: ['skip_it', 'Skip It'],
                3: ['watch_if_free', 'Play if Free.']
            };
        } else {
            array = {
                1: ['pay_to_watch', 'Pay To Consume'],
                2: ['skip_it', 'Skip It'],
                3: ['watch_if_free', 'Consume if Free']
            };
        }


        return '<span title="' + array[rating[0]][1] + '" style="background-size: 30%" class="rating_result ' + array[rating[0]][0] + '"><span class="verdict_text">' + array[rating[0]][1] + '</span></span>';
    } else {
        rating = Number(rating);
        let bg = 20;
        let str_widt = rating * 20;
        if (rating) {
            bg = 100 / rating;
        }

        str_widt = str_widt.toFixed(0);
        return '<span class="rating_result btn"><span style="width: ' + str_widt + '%;background-size: ' + bg + '%;" class="rating_result_total btn" title="' + rating + '/5"></span></span>';

    }


}

function add_movie_rating(block_id, data) {
    let parent_id = jQuery('div[id="' + block_id + '"]').attr('data-value');
    let rating_content = create_rating_content(data, parent_id, 1);

    jQuery('div.movie_total_rating[id="' + block_id + '"]').html(rating_content);
    return;

}

function load_actor_representation(movie_id) {
    var data = new Object();
    var ethnycity = new Object();
    var i = 1;
    jQuery('div[id="Ethnycity_container"] .ethnycity_select').each(function () {

        if (!ethnycity[i])
            ethnycity[i] = new Object();
        if (jQuery(this).hasClass('select_disabled')) {
            ethnycity[i][jQuery(this).attr('id')] = 0;
        } else {
            ethnycity[i][jQuery(this).attr('id')] = 1;
        }
        i++;
    });
    var a_type = new Array();
    jQuery('.r_row input').each(function () {
        if (jQuery(this).is(":checked")) {
            a_type.push(jQuery(this).attr('id'));
        }

    });


    data['ethnycity'] = ethnycity;
    data['actor_type'] = a_type;

    var url =  site_url+template_path + "actor_representation.php";

    jQuery.ajax({
        type: "post",
        url: url,
        data: {
            ethnic: JSON.stringify(data),
            id: movie_id
        },

        success: function (data) {

            jQuery('.r_content').html(data);
            jQuery('.main_ethnic_graph').click();

        }
    });


}

function word_cloud(id) {
    var factor = '0.5';
    if (jQuery('.wordcloud[id="' + id + '"]').width() > 600) {
        factor = 1;
    }


    jQuery('.wordcloud[id="' + id + '"]').awesomeCloud({
        "size": {
            "grid": 5,
            "factor": 1,

        },
        "options": {
            "color": 'random-dark',
            "rotationRatio": 0.35
        },
        "font": "'Times New Roman', Times, serif",
        "shape": "square"
    });

    // // $("#tv").awesomeCloud({
    //     "size" : {
    //         "grid" : 9,
    //         "normalize" : true
    //     },
    //     "options" : {
    //         "color" : "random-dark",
    //         "rotationRatio" : 0.35,
    //         "printMultiplier" : 3,
    //         "sort" : "random"
    //     },
    //     "font" : "'Times New Roman', Times, serif",
    //     "shape" : "square"
    // });
}


function ff_content(data, id) {
    let content = '<div class="column_header"><h2>Parental Guide: <a href="#" data-value="' + id + '" class="empty_ff_rating empty_ff_popup_rating">+add</a></h2></div>';

    if (data) {
        let data_ob = JSON.parse(data);

//     console.log(data_ob);

        if (data_ob.rating) {


            let movie_type = data_ob.type;

            let array_title = {'movie': 'film', 'tvseries': 'show', 'videogame': 'game'};
            let name = 'film';
            if (array_title[movie_type]) {
                name = array_title[movie_type];
            }
            let scorecontent = '';


            if (data_ob.rating > 0) {
                scorecontent = `This ${name} gets a ${data_ob.rating}/5 family friendly score `;
                content += '<div class="ff_rating_contaner">' + create_rating_star(data_ob.rating, 'ff_total') + '<p class="ff_rating_desc">' + scorecontent + '</p></div>';
            }

        }

        if (data_ob.content) {
            content += '<div class="ff_other_content">' + data_ob.content + '</div><a href="#" class="ff_other_content_calculate how_calculate_rating">Methodology</a>';


            if (data_ob.other) {
                content += '<details style="margin-top: 15px" class="trsprnt"><summary>Other sources</summary><div>' + data_ob.other + '</div></details>';
            }
        } else {

            if (data_ob.other) {
                content += data_ob.other;
            }
        }


    }

    return content;
}

function transformURL(url) {
    const parts = url.split('/');
    const domain = parts[2].replace(/\./g, '-');
    const path = '/' + parts.slice(3).join('/');

    const transformedURL = `https://${domain}.translate.goog${path}?_x_tr_sl=auto&_x_tr_tl=en&_x_tr_hl=en`;
    return transformedURL;
}


function global_zeitgeist_content(data) {

    let result = '';

    if (data.result) {
        let gzobj = data.result;

        for (var key in gzobj) {

            if (gzobj.hasOwnProperty(key)) {
                let item = gzobj[key];

                if (item.link) {
                    if (!item.multipler) {
                        item.multipler = 10;
                    }


                    let rating = item.rating;

                    if (rating) {
                        rating = rating / item.multipler;

                        // rating = rating + '/' + item.ratmax;
                    } else {
                        rating = '';
                    }
                    let img_container = '';


                    if (item.img) {
                        img_container = '<div class="gl_image"><img src="https://info.antiwoketomatoes.com/wp-content/uploads/sites_img/' + item.img + '.png" alt="' + item.name + '"></div>';
                    }
                    let flag = '';
                    if (item.flag) {

                        if (item.flag == 'mtcr') {

                            flag = `<img src="https://zeitgeistreviews.com/wp-content/themes/custom_twentysixteen/images/metacritic-logo.svg">`;
                        } else {
                            flag = `<img src="https://zeitgeistreviews.com/wp-content/themes/custom_twentysixteen/images/flags/4x3/${item.flag}.svg">`;
                        }

                    }

                    let converted_rating = item.rating / (item.rateconvert);
                    let star_rating = create_rating_star(converted_rating, 'gl_' + item.ekey);

                    let rating_converted = create_rating_star(rating, 'big_rating', item.multipler);

                    let rdata = `<div class="gl_small_block rating_block" id="${item.ekey}"><div class="gl_rating_img">${flag}</div><div class="gl_rating_title">${item.name}</div><div class="gl_star_rating rwt_stars">${star_rating}</div></div>`;


                    let trans_link = '';// `<a class="outr_link"  target="_blank" href="${transformURL(item.link)}" >${item.name} (en)</a>` ;


                    let incntnt = `<a class="outr_link"  target="_blank" href="${item.link}" >${item.name}</a>${trans_link}<div class="gl_rating">rating: ${rating_converted}</div>${img_container}</div>`

                    let rblock = add_rating_block('glob_zr', rdata, incntnt, '', true);

                    result += rblock;

                }

            }
        }

    }
    // if (data.other) {
    //     result += data.other;
    // }


    return result;
}



var gs_ob = {};


function loadGScript(src) {

    // var scripts = document.head.getElementsByTagName("script");
    // for (let i = scripts.length - 1; i >= 0; i--) {
    //     let script = scripts[i];
    //     if (script.src.includes("cse.google.com")) {
    //         script.parentNode.removeChild(script);
    //     }
    // }

    var script = document.createElement("script");
    script.async = true;
    script.src = src;
    script.addEventListener("load", function () {
    });

    document.head.appendChild(script);

}

function check_new_data(title, block_id,tabName) {

    let gsContent = document.querySelector("#" + block_id + " #gs_cotntent div.tab-content-inner#i_"+tabName);

   /// var gsContent = document.querySelector('#' + block_id + ' #gs_cotntent'); ///document.getElementById("gs_cotntent");

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes) {

                mutation.addedNodes.forEach(function (addedNode) {

                    if (addedNode.classList && addedNode.classList.contains('gsc-input')  && addedNode.nodeName === 'INPUT') {
                        addedNode.value = title;

                        document.querySelector("#" + block_id + " #gs_cotntent div.tab-content-inner#i_"+tabName+' button.gsc-search-button').click();

                        observer.disconnect();
                    }
                });
            }
        });
    });

    var config = {childList: true, subtree: true};
    observer.observe(gsContent, config);
}

function get_subcontent_gs(parent, data, block_id) {

    //  const sortedArray = Object.values(data).sort((a, b) => Number(a.data.weight) - Number(b.data.weight));
    const sortedArray = Object.values(data).sort((a, b) => {
        const titleA = a.data.name.toLowerCase();
        const titleB = b.data.name.toLowerCase();
        if (titleA < titleB) {
            return -1;
        }
        if (titleA > titleB) {
            return 1;
        }
        return 0;
    });
    let buttons = '';
    let sub_content = '';
    //console.log(sortedArray);
    sortedArray.forEach(item => {
        //console.log(item);
        buttons += `<span class="tab_button_child button_child_${item.data.id}"><button class="tab_button_child_btn" data-parent="${parent}" data-block="${block_id}"   data-tab="${item.data.id}" onclick="showTab(this)">${item.data.name}</button><a target="_blank" class="button_child_extlink" href="${item.data.link}"></a><span class="tab_child_title">${item.data.title}</span></span>`;
    });

    let content = `<div class="tab-list-main-child"><div class="tab-list tab-list-child">${buttons}</div></div>`;

    return content;


}


function showTab_inner(button) {
    if (gs_ob) {

        let gs_ob_current = null;
        let tabName = button.dataset.tab;

        let sub_content = '';

        let sub_procces = null;
        let prnt = null;


        let block_id = button.dataset.block;


        let visibleBlocks = document.querySelectorAll("#" + block_id + " #gs_cotntent div.tab-content-inner.visible");
        for (var j = 0; j < visibleBlocks.length; j++) {
            visibleBlocks[j].classList.remove("visible");
        }


        let parentBlock = document.querySelector("#" + block_id + " #gs_cotntent");
        let contentDiv = document.querySelector("#" + block_id + " #gs_cotntent div.tab-content-inner#i_"+tabName);
        if (!contentDiv){

            contentDiv = document.createElement("div");
            contentDiv.id = "i_" + tabName;
            contentDiv.classList.add('tab-content-inner');
            contentDiv.classList.add(block_id);
            parentBlock.appendChild(contentDiv);
        }


        if (button.classList.contains('tab_button_child_btn')) {
            sub_procces = 1;
            prnt = button.dataset.parent;
            gs_ob_current = gs_ob[block_id].data[prnt].sub;


            sub_content = document.querySelector("#" + block_id + " .tab-list-child").outerHTML;
        } else {
            gs_ob_current = gs_ob[block_id].data;
        }
        ///console.log(prnt,gs_ob_current);


        if (!sub_procces && gs_ob_current[tabName].sub) {
            sub_content = get_subcontent_gs(tabName, gs_ob_current[tabName].sub, block_id);
        }
        else if (sub_procces && prnt)
        {

            sub_content = get_subcontent_gs(prnt, gs_ob_current, block_id);
        }



        let isContentDivEmpty = !contentDiv.innerHTML.trim();
        let content_type = gs_ob_current[tabName].data.content_type;
        let search_block = '<div class=\"gcse-search\"></div>';
        if (content_type == 0) {
            let script_src='';
            if (gs_ob_current[tabName].data.script) {
                script_src = (gs_ob_current[tabName].data.script);
            }

            let sublass ='';
            if (document.querySelector('body').classList.contains('theme_dark'))
            {
                sublass = ' theme_dark';
            }
            let title = gs_ob[block_id].title;
            title =title.replace(/'/g, '&#39;');

            search_block = `<iframe class="gcse-search-main" srcdoc='<meta name="viewport" content="width=device-width, initial-scale=1"><body class="${sublass}"><div class="page_custom_block"><div class="gcse-search"></div></div></div><script type="text/javascript" src="${script_src}"></script>
<script type="text/javascript" >
    let gsContent = document.querySelector("div.page_custom_block");
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes) {
                mutation.addedNodes.forEach(function (addedNode) {
                    if (addedNode.classList && addedNode.classList.contains("gsc-input")  && addedNode.nodeName === "INPUT") {
                        addedNode.value = "${title}";
                        document.querySelector("button.gsc-search-button").click();
                        observer.disconnect();
                    }
                });
            }
        });
    });

    var config = {childList: true, subtree: true};
    observer.observe(gsContent, config);

</script>
<link rel="stylesheet"  href="/wp-content/themes/custom_twentysixteen/css/google_cse.css" type="text/css" />' ></iframe>`;
        }

        if (isContentDivEmpty)
        {
            contentDiv.innerHTML = "<h3 class = \"gs_head gs_head_" + tabName + "\">" + gs_ob_current[tabName].data.name + ": <a target='_blank' href=\"" + gs_ob_current[tabName].data.link + "\" class=\"button_child_extlink\"></a></h3><div class=\"tab_content_gs_block\">"+search_block+"</div><p>" + sub_content + gs_ob_current[tabName].data.dop_content + "</p>";

        }


        contentDiv.classList.add("visible");


        if (!sub_procces) {
            var tabButtons = document.querySelector("#" + block_id + " .tab-button.selected");

            if (tabButtons) {
                tabButtons.classList.remove("selected");
            }


            button.classList.add("selected");
        }

        ///contentDiv.style.display = "block";


        if (!isContentDivEmpty) {
            return false;
        }




        if (content_type == 0) {



           //  check_new_data(gs_ob[block_id].title, block_id,tabName);
            // if (gs_ob_current[tabName].data.script) {
            //  loadGScript(gs_ob_current[tabName].data.script);
            // }

        } else if (content_type == 1) {
            let srcdata = gs_ob_current[tabName].data.script

            let tabContentBlock = document.querySelector("#" + block_id + " #gs_cotntent div.tab-content-inner#i_"+tabName + ' .tab_content_gs_block');


            let htmlCode = '<div class="spin_bg"><i class="icon icon-loader"></i></div><iframe src="' + srcdata + '"></iframe>';


            tabContentBlock.innerHTML = htmlCode;


        } else if (content_type == 2) {
            let srcdata = gs_ob_current[tabName].data.script

            let tabContentBlock = document.querySelector("#" + block_id + " #gs_cotntent div.tab-content-inner#i_"+tabName + ' .tab_content_gs_block');


            let htmlCode = '<div class="spin_bg"><i class="icon icon-loader"></i></div><iframe style="max-width: 1020px;" src="' + srcdata + '"></iframe>';


            tabContentBlock.innerHTML = htmlCode;


        }


    }

    return false;
}

function  showTab(button) {


    const initialBlock = document.querySelector(".gcse-search");
    if (initialBlock) {

        const observer = new MutationObserver((mutationsList) => {
            for (const mutation of mutationsList) {
                if (mutation.type === 'childList') {
                    for (const removedNode of mutation.removedNodes) {
                        if (removedNode.classList && removedNode.classList.contains("gcse-search")) {

                            showTab_inner(button);
                            observer.disconnect();
                            return;
                        }
                    }
                }
            }
        });


        const targetNode = document.body;
        const config = { childList: true, subtree: true };
        observer.observe(targetNode, config);
    } else {

        showTab_inner(button);
    }
}

function prepare_search_data(block_id, data_str) {
    let content = '';
    //console.log(typeof(data_str));

    if (data_str) {
        if (typeof (data_str) == 'object') {
            gs_ob[block_id] = data_str;
        } else {
            gs_ob[block_id] = JSON.parse(data_str);
        }

        //console.log(gs_ob[block_id].data);


        let sortedArray = Object.values(gs_ob[block_id].data).sort((a, b) => Number(b.data.weight) - Number(a.data.weight));

       /// console.log('sortedArray',sortedArray);

        let buttons = '';
        let sub_content = '';
        let gs_div ='';

        sortedArray.forEach(item => {
            // console.log(item);
            buttons += `<button class="tab-button" data-tab="${item.data.id}" data-block="${block_id}" onclick="showTab(this)">${item.data.title}</button>`;

        });

    content = `<div class="tab-container">
    <div class="tab-list_main">
    <div class="tab-list">
        ${buttons}
    </div>
    </div><div id="gs_cotntent" class="tab-content"></div></div>`;


    }


    jQuery('#' + block_id).html(content);

    jQuery('.tab-list button.tab-button:first-of-type').click();


}

function load_ajax_block(block_id) {

    lastload = block_id;
    var parent_id = '';
    if (jQuery('div[id="' + block_id + '"]').attr('data-value')) {
        var request_block = '';
        parent_id = jQuery('div[id="' + block_id + '"]').attr('data-value');
        var request = "?id=" + parent_id;

    } else {
        request = "";
    }

    if (request == '?id=none') {
        return;
    }

    if (block_id.indexOf('?') > 0) {
        let pos = block_id.indexOf('?');
        request_block = block_id.substr(pos + 1);
        var block_id_sub = block_id.substr(0, pos);
        //  console.log(pos,request_block,block_id);
    }

    // Get local data
    var local_sroll = false;
    var scroll_data = '';
    if (block_id == 'video_scroll') {
        if (typeof video_scroll_data !== 'undefined') {
            scroll_data = video_scroll_data;
            local_sroll = true;
        }
    } else if (block_id == 'tv_scroll') {
        if (typeof tv_scroll_data !== 'undefined') {
            scroll_data = tv_scroll_data;
            local_sroll = true;
        }
    } else if (block_id == 'games_scroll') {
        if (typeof tv_scroll_data !== 'undefined') {
            scroll_data = games_scroll_data;
            local_sroll = true;
        }
    } else if (block_id == 'review_scroll') {
        if (typeof review_scroll_data !== 'undefined') {
            scroll_data = review_scroll_data;
            local_sroll = true;
        }
    } else if (block_id == 'stuff_scroll') {
        if (typeof stuff_scroll_data !== 'undefined') {
            scroll_data = stuff_scroll_data;
            local_sroll = true;

        }
    } else if (block_id == 'audience_scroll') {
        if (typeof audience_scroll_data !== 'undefined') {
            scroll_data = audience_scroll_data;
            local_sroll = true;
        }
        // Init tabs
        init_audience_tabs(block_id, parent_id);
    }
    if (local_sroll) {
        set_video_scroll(scroll_data, block_id);
        initializeScroller(0, 'div[id="' + block_id + '"]');        
        init_tags();        
        scroll_block(block_id);
        init_nte();
        return true;
    }


    // Ajax load
    var url =  site_url+template_path + block_id + ".php" + request;

    if (request_block) {
        url =  site_url+template_path + block_id_sub + ".php" + request + '&' + request_block;
    }

    if (block_id == 'ns_related_scroll') {
        url = 'https://newsfilter.biz/service/ns_related.php?pid=' + parent_id;
    }
    if (block_id == 'global_zeitgeist') {
        url = 'https://info.antiwoketomatoes.com/service/global_consensus.php?mid=' + parent_id;
    }

    jQuery.ajax({
        type: "GET",
        url: url,
        success: function (data) {
            if (block_id == 'similar_movies') {

                let title = jQuery('div[id="' + block_id + '"]').attr('data-name');
                if (data) {
                    let obj = JSON.parse(data);
                    if (obj) {
                        if (obj.content) {
                            let dto = '';
                            if (obj.data) {
                                dto = '<details style="margin-top: 15px" class="trsprnt"><summary>Other sources</summary><div>' + obj.data + '</div></details>';
                            }

                            jQuery('div[id="' + block_id + '"]').html('<div class="column_header">\n' +
                                '                    <h2>Similar ' + title + ':</h2>\n' +
                                '                </div><div class="movie_scroller scroller_wrap"><div class="column_content flex scroller flex_movies_block">' + obj.content + '</div>' +
                                '</div>' + dto);

                            if (obj['rating']) {
                                jQuery.each(obj['rating'], function (a, b) {
                                    ///console.log(a,b);
                                    let rating_content = create_rating_content(b, a);
                                    if (rating_content) {
                                        jQuery('div.movie_container[id="' + a + '"]').append(rating_content);
                                    }
                                });

                            }

                        }
                    }
                }
            } else if (block_id == 'twitter_scroll') {
                if (data) {
                    //  jQuery('div.column_header_main').prepend(data);
                    jQuery('div[id="' + block_id + '"]').html('<div class="column_content flex scroller">' + data + '</div>');
                    if (typeof ctf_init != 'undefined') {
                        ctf_init();
                    }


                    let af = jQuery('.s_container a:first-of-type').attr('id');
                    if (af) {
                        word_cloud(af);
                    }

                    initializeScroller(1, 'div[id="' + block_id + '"]');

                }
            } else if (block_id == 'chan_scroll') {
                if (data) {
                    try {
                        var Object_data = JSON.parse(data);
                    } catch (err) {
                        console.log(err);
                        Object_data = null;
                    }

                    let chandata = Object_data['chandata'];
                    if (chandata) {
                        ///console.log(link);

                        jQuery('div[id="' + block_id + '"]').html(chandata);
                        initializeScroller(1, 'div[id="internet_zeitgest"]');
                    }

                }

            } else if (block_id == 'google_search' || block_id == 'google_search_review' || block_id == 'google_global_zeitgeist'|| block_id == 'google_global_games'|| block_id == 'google_characters') {
                if (data) {
                    prepare_search_data(block_id, data);
                }
            } else if (block_id == 'ns_related_scroll') {
                if (data) {
                    jQuery('#' + block_id).html(data);
                }
            } else if (block_id == 'tags_keywords') {
                if (data) {


                    jQuery('#' + block_id).html(data);

                }
            } else if (block_id == 'family_friendly') {
                if (data) {

                    let ffcontent = ff_content(data, block_id);

                    jQuery('#' + block_id).html(ffcontent);


                }
            } else if (block_id == 'global_zeitgeist') {
                if (data) {
                    let gzobj = JSON.parse(data);


                    if (gzobj.result.length != 0) {

                        let gz_content = global_zeitgeist_content(gzobj);

        gz_content += `
        <div style="margin-top: 15px; width: 100%"  class="accordion-item">
            <div class="accordion-header">Search the globe</div>
            <div class="accordion-content"><div  id="google_global_zeitgeist" data-value="${parent_id}" class="not_load page_custom_block"></div></div>
        </div>
        `;
                        jQuery('#' + block_id).html(gz_content);

                    }
                    else {


                        jQuery('.global_zr').removeClass('active');
                        jQuery('.global_zr .accordion-content').html(`<section class="dmg_content inner_content" id="actor_data_dop" ><div  id="google_global_zeitgeist" data-value="${parent_id}" class="not_load page_custom_block"></div></section>`);

                       // jQuery('#' + block_id).html(gz_content);
                    }


                }
            } else if (block_id == 'movie_rating') {
                add_movie_rating(block_id, data);
            } else if (block_id == 'last_donations') {
                jQuery('div[id="' + block_id + '"]').html(data);
            } else if (block_id == 'mailpoet_form') {
                jQuery('div[id="' + block_id + '"]').html(data);
            } else if (block_id == 'disqus_last_comments') {
                jQuery('div[id="' + block_id + '"]').html(data);
            } else if (block_id == 'actor_representation') {
                jQuery('div[id="' + block_id + '"]').html(data);
                load_actor_representation(parent_id);

                var srcs = window.location.protocol +"//"+window.location.host+ '/wp-content/themes/custom_twentysixteen/js/jquery-ui-sortable.min.js';

                jQuery.getScript(srcs).done(function (script, textStatus) {
                    jQuery('div[id="Ethnycity_container"]').sortable({
                        placeholder: 'emptySpace',
                        update: function (event, ui) {
                            load_actor_representation(parent_id);
                        }

                    });

                });


                jQuery('body').on('change', '.r_row_item  input', function () {
                    load_actor_representation(parent_id);
                });
                jQuery('body').on('click', '.r_row  .ethnycity_select', function () {
                    load_actor_representation(parent_id);
                });
            } else if (block_id == 'audience_form') {
                jQuery('div[id="' + block_id + '"]').html(data);
                //check load script

                /*jQuery('body.jstiny').removeClass('jstiny');
                 if (typeof tinymce !== 'undefined') {
                 tinymce = undefined;
                 }*/
                if (typeof wpcr3a == 'object') {
                    wpcr3a.init();
                } else {

                    ///console.log('first');
                    var head = document.getElementsByTagName('head')[0];
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.type = 'text/css';
                    link.href = window.location.protocol + '//' + window.location.host + '/wp-content/plugins/critic_matic/css/reviews.css';
                    link.media = 'all';
                    head.appendChild(link);


                    var d = document, s = d.createElement('script');
                    s.src = window.location.protocol + '//' + window.location.host + '/wp-content/plugins/critic_matic/js/reviews.js?v=1.8';
                    (d.head || d.body).appendChild(s);
                    s.onload = function () {
                        wpcr3a.init();

                    };
                }


            } else if (block_id == 'search_ajax') {
                if (data) {
                    try {
                        var Object_data = JSON.parse(data);
                    } catch (err) {
                        console.log(err);
                        Object_data = null;
                    }

                    jQuery('div[id="' + block_id + '"]').html(Object_data['content']);

                    if (Object_data['rating'] && typeof (Object_data['rating']) == 'string') {

                        Object_data['rating'] = JSON.parse(Object_data['rating']);

                        jQuery.each(Object_data['rating'], function (a, b) {
                            ///console.log(a,b);
                            let rating_content = create_rating_content(b, a);
                            if (rating_content) {
                                jQuery('div.movie_container[id="' + a + '"]').append(rating_content);
                            }
                        });

                    }

                }
            } else {


                set_video_scroll(data, block_id);
                initializeScroller(0, 'div[id="' + block_id + '"]');
            }
            init_nte();
        }
    });
}

function init_audience_tabs(block_id, parent_id) {
    jQuery('.audience_review .tab-wrapper:not(.init)').each(function (i, v) {
        var tabs = jQuery(v);
        tabs.addClass('init');

        tabs.find('a').click(function () {
            var first_tab_id = tabs.find('li.active a').first().attr('data-id');
            var tab = jQuery(this);
            var tab_id = tab.attr('data-id');
            var parrent = tab.closest('li');
            var is_active = parrent.hasClass('active');
            if (is_active) {
                return false;
            }

            // 1. Mark last tab            
            jQuery('.audience_review .column_content:not(.init)').each(function () {
                jQuery(this).addClass(first_tab_id);
                jQuery(this).addClass('init');
            });


            // 2. Hide active tab
            jQuery('.audience_review .column_content.' + first_tab_id).addClass('hide');

            // 3. Try to select tab
            var select_tab = jQuery('.audience_review .column_content.' + tab_id);
            if (select_tab.length) {
                select_tab.removeClass('hide');
            } else {
                // 4. If no content, get content and select
                jQuery('#audience_scroll').append('<div class="column_content flex scroller init ' + tab_id + '"></div>');
                // 5. Get ajax content
                // Ajax load
                var vote = 0;
                if (tab_id == 'tab-p') {
                    vote = 1;
                } else if (tab_id == 'tab-n') {
                    vote = 2;
                }
                var and_parent = '';
                if (parent_id) {
                    and_parent = "&id=" + parent_id;
                }
                var url =  site_url+template_path + block_id + ".php" + "?vote=" + vote + and_parent;

                jQuery.ajax({
                    type: "GET",
                    url: url,
                    success: function (data) {
                        var tab_class = '.' + tab_id;
                        set_video_scroll(data, block_id, tab_class);
                        init_nte();
                    }
                });
            }
            tabs.find('li.active').removeClass('active');
            parrent.addClass('active');

            return false;
        });


    });
}

function update_scrool_Title(event) {
    var selectedOption = event.target;
    var selectedValue = selectedOption.getAttribute("data-value");
    var selectedText = selectedOption.innerText;

    let title = jQuery('.rand_scroll .column_header>h2 span.block_title')
    title.html(selectedText);
    jQuery('#compilation_scroll').attr('data-value', selectedValue);

    ///jQuery('#compilation_scroll').removeClass('loaded').addClass('not_load');
    load_ajax_block('compilation_scroll');
}

var update_scrool = document.getElementById("myList");
if (update_scrool) {
    update_scrool.addEventListener("click", update_scrool_Title);
}


function get_Top(block) {
    if (jQuery(block).attr('id')) {
        return jQuery(block).offset().top;
    }
}

function load_next_block(block_id) {


    if (!block_id) {
        var block_id = jQuery('.not_load:visible').attr('id');
    }


    if (lastload != block_id) {

        if (block_id == 'rwt_footer') { ///footer block

            let content = '<div class="support_us" style="height: 60px;"><a href="https://oc.rightwingtomatoes.com/help/" target="_blank" rel="noopener"><img loading="lazy" style="height: 60px" src="https://oc.rightwingtomatoes.com/wp-content/uploads/2018/11/support_us_fair_use-300x89.png" alt="" width="100%" height="60px"></a></div><iframe style="width: 100%;height: calc(100vh - 60px);margin: 0;" src="https://cointr.ee/rightwingtomato"></iframe>';
            jQuery('div[id="' + block_id + '"]').removeClass('not_load').addClass('loaded').html(content);
            return;
        }

        if (block_id == 'disquss_container') {


            var open_popup = jQuery('.popup-container input[id="action-popup"]').attr('checked');
///console.log(open_popup);
            var block = jQuery('div[id="disquss_container"]');
            if (open_popup == 'checked') {

                block.removeClass('loaded').addClass('not_load');
                return;
            }
            jQuery('.popup-content').html('');

            var data_object = new Object();


            block.html('<div id="disqus_thread"></div>');

            data_object['page_url'] = block.attr('data_link');
            data_object['page_identifier'] = block.attr('data_idn');
            data_object['title'] = block.attr('data_title');
            data_object['data_comments'] = block.attr('data_comments');


            ///console.log(data_object);
            discuss_config(data_object);
            jQuery('div[id="' + block_id + '"]').removeClass('not_load').addClass('loaded');
            return;
        }
        // else if (block_id == 'disqus_last_comments') {
        //
        //     var block = jQuery('div[id="disqus_last_comments"]');
        //
        //     block.removeClass('not_load').addClass('loaded');
        //
        //     block.html('<div id="RecentComments" class="dsq-widget"></div>');
        //
        //     var script = document.createElement('script');
        //     script.src = 'https:///hollywoodstfu.disqus.com/recent_comments_widget.js?num_items=5&hide_mods=0&hide_avatars=0&avatar_size=32&excerpt_length=50';
        //     document.querySelector('div[id="RecentComments"]').appendChild(script);
        //     script.onload = function () {
        //       console.log('ok');
        //         DISQUSWIDGETS.getCount({reset: true});
        //     };
        //
        //     return;
        // }

        jQuery('div[id="' + block_id + '"]').removeClass('not_load').addClass('loaded');
        load_ajax_block(block_id);
        //check_load_block();
    }

}

////load content
function check_load_block() {
    var topcur = jQuery(window).scrollTop() + jQuery(window).height() + 800;
    var last_bloc = get_Top('.not_load:visible');
    //  console.log('last_bloc '+last_bloc+' topcur '+topcur);
    if (last_bloc) {
        if (topcur >= last_bloc) {
            load_next_block('');
        }
    }
}

var run = 1;
// jQuery(window).scroll(function () {
//
//     if (!run) {
//         return false;
//     }
//     run = 0;
//
//     check_load_block();
//
//
//     setTimeout(function () {
//         run = 1;
//     }, 500);
// });

function add_popup() {
    if (!jQuery('.popup-container').html()) {
        var popup = '<div class="popup-container">\n' +
            '\t<input type="checkbox" id="action-popup">\n' +
            '\t<div class="popup">\n' +
            '\t\t<label for="action-popup" class="transparent-label"></label>\n' +
            '\t\t<label for="action-popup" class="popup-close"></label><div class="popup-inner">\n' +
            '\t\t\t<div class="popup-content">\n' +
            '\t\t\t</div>\n' +
            '\t\t</div>\n' +
            '\t</div>\n' +
            '</div>';
        jQuery('body').append(popup);
    }


}

function generate_watch_content(data, title, year, type) {

    let content = '<h2> Watch Now</h2>';
    let content_array = {};
    let array_type = [];
    let array_priority = {'4k': 1, 'hd': 2, 'sd': 3};
    let providers_priority = [];
    var Object_data = new Object();

    if (data) {
        try {
            Object_data = JSON.parse(data);
        } catch (err) {
            console.log(err);
            Object_data = null;
        }
        if (!Object_data) {
            // console.log(data);
            //return;
        }
    }

    if (!Object_data['data']) {
        Object_data['data'] = new Array();
    }
    if (!Object_data['providers']) {
        Object_data['providers'] = new Array();
    }
    ///console.log(Object_data);

    if (type == 'Movie') {

        Object_data['data'].push({
            'monetization_type': "irl",
            'provider_id': 'showtimes',
            'presentation_type': '',
            urls: {'standard_web': 'https://www.showtimes.com/Search?query=' + title}
        });
    }

    if (type == 'Movie') {
        Object_data['providers']['showtimes'] = {
            's': 'fullsize fullsizebig',
            'n': 'Showtimes',
            'i': window.location.protocol  +"//"+window.location.host+"/wp-content/themes/custom_twentysixteen/images/showtimes-logo.png"
        };
    }


    //console.log(Object_data['data']);

    Object.keys(Object_data['data']).forEach((key) => {

        let current_data = Object_data['data'][key];
        ///console.log(current_data);

        let summ = array_priority[current_data.presentation_type];
        if (!summ) {
            summ = 4;
        }
        let price = current_data.retail_price;
        if (!price) {
            price = 1;
        }
        let total = Number(summ) * Number(price);


        if (!providers_priority[current_data.monetization_type]) {
            providers_priority[current_data.monetization_type] = {};
        }
        if (!providers_priority[current_data.monetization_type][current_data.provider_id]) {
            providers_priority[current_data.monetization_type][current_data.provider_id] = {};
        }

        let current = providers_priority[current_data.monetization_type][current_data.provider_id]['summ'];
        if ((current && current > total) || !current) {
            providers_priority[current_data.monetization_type][current_data.provider_id]['summ'] = total;
            providers_priority[current_data.monetization_type][current_data.provider_id]['id'] = key;
        }


    });
    ////console.log(providers_priority);

    Object.keys(Object_data['data']).forEach((key) => {
        //console.log('key:', key)
        /// console.log('value:', Object_data['data'][key])


        let current_data = Object_data['data'][key];


        let priorityclass = '';

        if (providers_priority[current_data.monetization_type][current_data.provider_id]['id'] == key) {
            /// console.log('ok');

            priorityclass = ' type_priority ';
        }


        let provider = Object_data['providers'][current_data.provider_id];
        if (typeof provider === 'object' && provider != null) {

            // console.log(provider);
            let url = current_data.urls.standard_web;

            /// console.log(url);
            let provider_currency = '';
            if (current_data.currency == 'USD') {
                provider_currency = '$';
            }

            if (current_data.retail_price) {
                provider_currency = provider_currency + current_data.retail_price;
            } else {
                provider_currency = '';
            }

            array_type[current_data.presentation_type] = 1;

            if (current_data.presentation_type == 'canvas') {
                current_data.presentation_type = '';
            }
            let cls = '';
            if (provider.s) {
                cls = '  ' + provider.s;
            }
            let res_price = '';
            if (provider_currency || current_data.presentation_type) {
                res_price = `<div class="povider_price">${provider_currency}<span class="provider_type">${current_data.presentation_type}</span></div>`;
            }


            let result_data = `<a target="_blank" class="provider_container type_${current_data.presentation_type + priorityclass + cls}"  href="${url}" title="${provider.n}">
<img src="${provider.i}" alt="${provider.n}" />${res_price}</a>`;


            if (current_data.monetization_type == 'flatrate') {
                current_data.monetization_type = 'stream'
            }

            if (current_data.monetization_type == 'ads') {
                current_data.monetization_type = 'free';
            }
            if (!content_array[current_data.monetization_type]) {
                content_array[current_data.monetization_type] = result_data;
            } else {
                content_array[current_data.monetization_type] += result_data;
            }

            ///console.log(content_array);


//             currency: "USD"
// monetization_type: "rent"
// presentation_type: "sd"
// provider_id: 2
// retail_price: 19.99
        }
    });

    content += `<div class="filters_type"><span class="filter_heading"><svg style="width: 10px" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="filter" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-filter fa-w-16"><path data-v-0634e7f3="" fill="currentColor" d="M487.976 0H24.028C2.71 0-8.047 25.866 7.058 40.971L192 225.941V432c0 7.831 3.821 15.17 10.237 19.662l80 55.98C298.02 518.69 320 507.493 320 487.98V225.941l184.947-184.97C520.021 25.896 509.338 0 487.976 0z" class=""></path></svg><span class="hidden-xs"> Filters</span></span>`;

    content += `<a href="#" class="show_type active" id="priority">Best Price</a>`;


    Object.keys(array_type).forEach((key) => {

        if (key != 'canvas') {
            content += `<a href="#" class="show_type" id="${key}">${key}</a>`;
        }

    });
    content += `<span class="close_filters"></span></div>`;
    Object.keys(content_array).forEach((key) => {


        let dop = '';
        if (key == '"Free"') {
            dop = '<span data-value="piracy_disclaimer_popup" class="nte_info nte_providers"></span>';
        }

        content += `${dop}<div class="providers_colum"><div class='providers_desc providers_${key}'>${key}</div><div class="providers_colum_data">${content_array[key]}</div></div>`;

    });


    //console.log(content);
    return content;


}

function create_Highcharts_colomn_single(data, block) {


    if (typeof Highcharts !== 'undefined') {


        if (data) {
            data = JSON.parse(data);
            var data_series = data['series'];
            var prefix = data['prefix'];

        }

        if (data) {
            Highcharts.chart('container_' + block, {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'column'
                },
                title: {
                    text: data['name']
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    headerFormat: '<span style="font-size: 16px">{point.point.name}</span><br/>',
                    pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y} %</b><br/>'
                },
                xAxis: {
                    type: 'category',
                    // max: 4,
                    labels: {
                        animate: true,
                    }
                },
                yAxis: [{
                    title: {
                        text: 'Percentage'
                    },
                    showFirstLabel: false
                }],
                series: [{
                    name: data['name'],
                    colorByPoint: true,
                    dataSorting: {
                        enabled: true,
                        matchByName: true
                    },
                    dataLabels: [{
                        enabled: true,
                        inside: true,
                        style: {
                            fontSize: '14px'
                        }
                    }],
                    data: data_series
                }]
            });
        } else {
            console.log('data error');
            console.log(data);
        }
    } else {
        console.log('cant load Highcharts');
    }
}

function create_Highcharts_columns(data, block) {


    if (typeof Highcharts !== 'undefined') {


        if (data) {
            data = JSON.parse(data);
            var data_series = data['series'];
            var data_series_cast = data['cast'];
        }
        if (data) {
            Highcharts.chart('container_' + block, {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'column'
                },
                title: {
                    text: data['name'] + ' Representation'
                },

                plotOptions: {
                    series: {
                        grouping: false,
                        borderWidth: 0
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    headerFormat: '<span style="font-size: 16px">{point.point.name}</span><br/>',
                    pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y} %</b><br/>'
                },
                xAxis: {
                    type: 'category',
                    // max: 4,
                    labels: {
                        animate: true,
                    }
                },
                yAxis: [{
                    title: {
                        text: 'Percentage'
                    },
                    showFirstLabel: false
                }],
                series: [{
                    color: 'rgb(44,47,66)',
                    pointPlacement: -0.2,
                    linkedTo: 'main',
                    name: data['name'],
                    data: data_series,

                }, {
                    name: 'Cast Percentages',
                    id: 'main',
                    dataSorting: {
                        enabled: true,
                        matchByName: true
                    },
                    dataLabels: [{
                        enabled: true,
                        inside: true,
                        style: {
                            fontSize: '14px'
                        }
                    }],
                    data: data_series_cast
                }
                ]
            });
        }
    }
}

function set_point_format(series, point, block, prefix) {
    if (block == 'main_movie_graph') {
        var data = '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.percentage:.1f}%</b>'

    } else {
        if (!prefix) {
            prefix = '';
        }

        data = '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.percentage:.1f}%</b><br>  Total: ' + prefix + ' {point.y}'


    }

    return data;

}


function create_Highcharts(data, block) {


    if (typeof Highcharts !== 'undefined') {


        if (data) {
            data = JSON.parse(data);
            var data_series = data['series'];
            var prefix = data['prefix'];

        }

        if (data) {
            Highcharts.chart('container_' + block, {
                chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                    text: data['name']
                },
                // tooltip: {
                //     pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                // },
                tooltip: {
                    shared: true,
                    headerFormat: '<span style="font-size: 16px">{point.point.name}</span><br/>',

                    pointFormat: set_point_format('{series}', '{point}', block, prefix)


                },

                accessibility: {
                    point: {
                        valueSuffix: '%'
                    }
                },
                plotOptions: {

                    pie: {
                        size: 120,
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            distance: 20,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',

                            style: {
                                fontWeight: 'bold',
                                color: 'white',
                                fontSize: '12px'
                            }
                        },

                        format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                    }

                },
                series: [{
                    name: data['name'],
                    colorByPoint: true,
                    data: data_series
                }]
            });
        } else {
            console.log('data error');
            console.log(data);
        }
    } else {
        console.log('cant load Highcharts');
    }
}

jQuery(document).ready(function () {


    jQuery('.not_load:visible').each(function () {
        let prnts = jQuery(this).parents('details');
        let block_id = jQuery(this).attr('id');

        if (!prnts.length || (prnts.length && prnts.prop('open'))) {
            load_next_block(block_id);
        }
        /// check_load_block();
    });

    jQuery('body').upScrollButton();

    init_short_codes();


    jQuery("body").on('click', 'details.actor_details summary', function () {

        //  console.log('start');
        var prnt = jQuery(this).parents('details');

        let id = prnt.find('.not_load').attr('id');

        if (id) {
            load_next_block(id);
        }

    });

    jQuery("body").on('click', '.filters_type a', function () {
        let id = jQuery(this).attr('id');
        let prnt = jQuery(this).parents('.movie_watch');

        prnt.find('a.show_type').removeClass('active');
        jQuery(this).addClass('active')

        prnt.find('a.provider_container').hide();
        prnt.find('a.provider_container.type_' + id).show();

        return false;
    });

    jQuery("body").on('click', '.close_filters', function () {


        var prnt = jQuery(this).parents('.movie_container');
        prnt.find('.movie_watch').html('').hide();
        prnt.find('.watch_buttom').show();

    });

    function get_watch(id)
    {
        jQuery('.movie_watch').html('').hide();
        jQuery('.watch_buttom').show();
        let btn =  jQuery('.watch_buttom[id="'+id+'"]');

        let title = btn.attr('data-title');
        let year = btn.attr('data-year');
        let type = btn.attr('data-type');


        var prnt = btn.parents('.movie_container');
        jQuery(this).hide();

        // console.log('ajax');
        jQuery.ajax({
            type: 'POST',
            async: false,
            data: {id: id},
            url:  site_url+template_path + "get_wach.php",
            success: function (html) {

                var content = generate_watch_content(html, title, year, type);
                prnt.find('.movie_watch').html(content).show();


            }
        });
    }

    jQuery("body").on('click', '.watch_buttom', function (e) {
        e.preventDefault();
       // console.log('watch_buttom');
        let id = jQuery(this).attr('id');
        get_watch(id);
      //  console.log('after ajax');
      // return false;
    });

    jQuery('body').on('change', '.popup-container > input:not(:checked)', function () {

        if (jQuery('.popup-content').html()) {
            jQuery('.popup-content').html('');
        }


        //  console.log('ok');

    });

    jQuery("body").on("click", ".button_play_trailer", function () {

        var id = jQuery(this).attr('id');

        if (id.indexOf('search_query') == 0) {


            let url = 'https://www.youtube.com/results?' + id;

            window.open(url, '_blank');
            return false;
        }

        add_popup();
        let content = `<iframe style="  width: 100%;    height: 90vh;max-width: 800px; max-height: 440px" src="https://www.youtube.com/embed/${id}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        jQuery('.popup-content').html(content);
        jQuery('.popup-content').append('<label for="action-popup" class="popup-close-btn">Close</label>');
        jQuery('input[id="action-popup"]').click();
        return false;
    });


    jQuery('body').on('click', 'a.icntn:not(.direct_link)', function (e) {


        if (e.target.closest('.spoiler_default')) {
            return false;
        }
        var $this = jQuery(this)
        var link = $this.attr('href');
        var wp_core = '';
        if ($this.hasClass('wp_core')) {
            wp_core = '&wp_core=1';
        }

        jQuery.ajax({
            type: "GET",
            url:  site_url+template_path + "get_review.php?id=" + link + wp_core,
            success: function (data) {
                if (data) {
                    jQuery('div[id="disqus_thread"]').remove();


                    ////$result = array('page_url'=>$link,'page_identifier'=>$pg_idnt,'title'=>$title,'content'=>$content);

                    var data_object = JSON.parse(data);
                    var content = data_object['content'];
                    var emotions = data_object['emotions'];

                    add_popup();
                    jQuery('.popup-content').html(content);

                    jQuery('.full_review').append(emotions);

                    // Short codes
                    init_short_codes(data_object);

                    jQuery('.popup-content .full_review').append('<label for="action-popup" class="popup-close-btn">Close</label>');

                    jQuery('input[id="action-popup"]').click();

                    discuss_config(data_object);
                    init_nte();


                    jQuery('div[id="disquss_container"]').removeClass('loaded').addClass('not_load');
                }


            }
        });


        return false;
    });

    jQuery(document).on("click", function (e) {
        var div = jQuery(".user-reaction");

        if ((!div.is(e.target) && div.has(e.target).length === 0)) {
            jQuery('.card .user-reactions').remove();

        }
    });


    jQuery(document).on('click', '.user-reactions-box>span.user-reaction', function (e) {

        e.preventDefault();

        var t = jQuery(this), $class = t.attr('class'), main = t.parent().parent().parent();

        var vote_type = 'vote';
        let voted_before_container = main.find('.voted');
        var voted_before = voted_before_container.html();
        let voted_before_count = voted_before_container.find('.count').html();


        var voted = jQuery(this).hasClass('voted');
        if (voted) {
            vote_type = 'unvote';
            jQuery(this).removeClass('voted');

            let cnt = Number(jQuery(this).find('.count').html());
            cnt--;
            if (cnt < 0)
                cnt = 0;
            jQuery(this).find('.count').html(cnt);

        } else {
            if (voted_before) {
                voted_before_count = Number(voted_before_count);
                voted_before_count--;
                if (voted_before_count <= 0) {
                    voted_before_count = '';
                }
                voted_before_container.find('.count').html(voted_before_count);
            }

            let cnt = Number(jQuery(this).find('.count').html());
            cnt++;

            jQuery(this).find('.count').html(cnt);

            main.find('.user-reaction').removeClass('voted');
            jQuery(this).addClass('voted');
        }

        var res = $class.split(' ');
        var data_type = res[1].split('-');
        var ptype = main.attr('data-ptype');

        jQuery('.card  .user-reactions-button.reaction-show').addClass('linked');

        var pid = main.data('post');
        //  console.log(pid);

        $.ajax({

            url:  site_url+template_path + "get_emotions.php",
            type: 'POST',
            data: {

                nonce: main.data('nonce'),
                type: data_type[2],
                post: main.data('post'),
                vote_type: vote_type,
                ptype: ptype,
                request: 'set_emtns'
            },
            success: function ($data) {
                window.setTimeout(function () {

                    jQuery('.card .user-reactions').remove();
                    var emotions = jQuery('.review_comment_data[data-id="' + pid + '"]>a.emotions');
                    emotions.attr('class', 'emotions');

                    if (typeof $data !== "undefined") {
                        //Top results api
                        var data = JSON.parse($data);

                        var key = data.key;
                        var count = data.count;
                        if (count > 0) {
                            emotions.addClass('emotions_custom');
                            emotions.addClass('user-reaction-' + key);
                            emotions.find('.emotions_count').html(count);
                        } else {
                            emotions.find('.emotions_count').html('');
                        }

                    } else {

                        var count = Number(emotions.find('.emotions_count').html());

                        emotions.attr('class', 'emotions');
                        // console.log(vote_type,voted_before);
                        if (vote_type == 'vote') {
                            emotions.addClass('emotions_custom');
                            emotions.addClass('user-reaction-' + type[2]);

                            if (!voted_before) {
                                count++;
                                emotions.find('.emotions_count').html(count);
                            }

                        } else {
                            count--;
                            if (count == 0)
                                count = '';

                            emotions.find('.emotions_count').html(count);

                        }
                        ///  console.log(count);
                    }
                }, 700);

            }
        });
        return false;
    });

    jQuery('body').on('click', 'a.emotions', function (e) {


        e.preventDefault();
        var ts = jQuery(this);
        let prnt = ts.parents('.review_comment_data');
        let big_prnt = ts.closest('.a_msg').find('.em_hold');

        if (big_prnt.find('.user-reactions').html()) {
            big_prnt.find('.user-reactions').remove();
        } else {

            jQuery('.card .user-reactions').remove();

            var id = prnt.attr('data-id');
            var ptype = prnt.attr('data-ptype');
            jQuery.ajax({
                type: 'POST',

                data: {
                    id: id, 
                    request: 'get_emtns',
                    ptype: ptype,
                },
                url:  site_url+template_path + "get_emotions.php",

                success: function (data) {
                    if (data) {
                        big_prnt.prepend(data);
                    }
                }
            });

        }
        return false;
    });


    jQuery('body').on('click', 'a.disquss_coment', function (e) {
        e.preventDefault();


        let prnt = jQuery(this).parents('.review_comment_data');
        var id = prnt.attr('data-id');
        var pid_title = jQuery(this).attr('data_title');
        let big_prnt = jQuery(this).parents('.a_msg');
        let link = big_prnt.find('a.icntn').attr('href');

        add_popup();

        let data_object = new Object();
        data_object['page_url'] = link;
        data_object['page_identifier'] = id + ' ' + link;
        data_object['title'] = pid_title;

        let contenttext = big_prnt.find('.vote_content').html();
        if (!contenttext) {
            contenttext = big_prnt.find('a.icntn').html();
        }
        if (!contenttext) {
            contenttext = ' ';
        }


        // console.log(data_object);
        jQuery('div[id="disqus_thread"]').remove();

        let content = '<div class="sub_content" ><div class="sub_content_text" >' + contenttext + '</div><div id="disqus_thread" ></div></div><label style="margin-top: -45px;margin-right: 15px;" for="action-popup" class="popup-close-btn">Close</label>';
        jQuery('.popup-content').html(content);

        jQuery('input[id="action-popup"]').click();
        discuss_config(data_object);
        jQuery('div[id="disquss_container"]').removeClass('loaded').addClass('not_load');


        return false;

    });

    jQuery('body').on('click', '.actor_info, a.actors_link', function () {
        var actor_id = jQuery(this).attr('data-id');

        add_popup();
        var whait_html = '<div class="cssload-circle">\n' +
            '\t\t<div class="cssload-up">\n' +
            '\t\t\t\t<div class="cssload-innera"></div>\n' +
            '\t\t</div>\n' +
            '\t\t<div class="cssload-down">\n' +
            '\t\t\t\t<div class="cssload-innerb"></div>\n' +
            '\t\t</div>\n' +
            '</div>';
        jQuery('.popup-content').html(whait_html);
        jQuery('input[id="action-popup"]').click();

        var data = '';

        if (typeof get_data == 'function') {
            data = get_data();
            data = JSON.stringify(data);
        }

        $.ajax({
            type: "POST",
            url: window.location.protocol +"//"+window.location.host+ "/analysis/get_data.php",
            data: ({
                oper: 'get_actordata',
                id: actor_id,
                'data': data

            }),
            success: function (html) {
                jQuery('.popup-content').html('<div class="actor_popup default_popup">' + html + '</div>');
                jQuery('.actor_popup').append('<label for="action-popup" class="popup-close-btn">Close</label>');
            }
        });


        return false;
    });

    jQuery("body").on("keyup", '.crowd_movie_autoinput', function (e) {

        if (e.which == 13) {
            jQuery(".customsearch_button_advanced").click();
            return false;
        }

        jQuery(".crowd_movie_autoinput").addClass('loading');

        var keyword = jQuery(this).val();

        if (keyword.length >= 2) {
            jQuery.ajax({
                type: 'POST',
                ///context: this,
                url:  site_url+template_path + "ajax_data.php",
                data: {"action": "ajax_search", "keyword": keyword, "type": "movie", 'nolinks': 1},
                success: function (data) {
                    // console.log(data);

                    jQuery('.crowd_items .advanced_search_first').html(data).show();
                    jQuery('.advanced_search_menu.crowd_items').show().removeClass("advanced_search_hidden");

                }
            });
        }
    });

    jQuery('body').on('change', '.review_crowd .big_checkbox>input', function (e) {


            let min_prnt = jQuery(this).parents('.big_checkbox');
            let sconainer = min_prnt.find('.check_container');

            if (jQuery(this).is(":checked")) {
                sconainer.show();
            } else {
                sconainer.hide();
            }


            let th = jQuery(this).parents('.review_crowd');


            if (th.hasClass('active')) {
                return;
            } else {
                th.addClass('active');
                setTimeout(function () {
                    th.removeClass('active');
                }, 2980);

            }

        }
    );

    // jQuery('.ajaxlogin').click(function () {
    //
    //     jQuery.ajax({
    //         type: 'POST',
    //
    //         url: window.location.protocol +"//"+window.location.host+template_path + 'loginajax.php',
    //         success: function (html) {
    //             add_popup();
    //             jQuery('.popup-content').html('<div class="default_popup login_popup">' + html + '</div>');
    //             jQuery('input[id="action-popup"]').click();
    //
    //             let url = site_url+'/wp-content/plugins/login-with-ajax/templates/login-with-ajax.min.js';
    //
    //             var third_scripts = {login_ajax: url};
    //             use_ext_js('', third_scripts);
    //
    //
    //         }
    //     });
    //
    //     return false;
    //
    // });

    jQuery('.primary-menu a[href="#add_movie"],' +
        ' .primary-menu a[href="#add_review"], .primary-menu a[href="#add_audience_review"]').click(function () {

        let href = jQuery(this).attr('href');


        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'add_custom',
                'type': href
            },
            url: crowdsource_url,
            success: function (html) {
                if (debug_mode) {
                    console.log('add_custom', html);
                }
                add_popup();
                let hclass = href.substr(1);


                jQuery('.popup-content').html('<div class="default_popup custom_crowd ' + hclass + '">' + html + '</div>');

                jQuery('input[id="action-popup"]').click();

                jQuery('span.close_header_nav').click();

            }
        });

        return false;

    });


    jQuery('body').on('click', '.a_info', function () {

        var id = jQuery(this).attr('data-value');
        var movie = jQuery(this).attr('data-movie');
        if (jQuery(this).hasClass('a_state')) {
            var enable_robot = 1;
        }
        var popup_enable = '';
        var popup = jQuery(this).parents('.popup-content');
        if (popup.html()) {
            popup_enable = 1;
        }

        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'review_crowd',
                'id': id,
                'movie': movie,
                'robot': enable_robot
            },
            url: crowdsource_url,
            success: function (html) {
                if (debug_mode) {
                    console.log('review_crowd', html);
                }
                if (!popup_enable) {
                    add_popup();
                }

                jQuery('.popup-content').html('<div id="' + id + '" class="default_popup review_crowd">' + html + '</div>');
                if (!popup_enable) {
                    jQuery('input[id="action-popup"]').click();
                }

                if (enable_robot) {
                    jQuery.ajax({
                        type: 'get',
                        dataType: 'html',
                        url: window.location.protocol +"//"+window.location.host+ "/wp-content/themes/custom_twentysixteen/images/roboto.svg",
                        success: function (html) {
                            jQuery('.review_crowd').append(html);


                        }
                    });

                }

            }

        });

        return false;

    });


    jQuery('body').on('click', '.movie_touch', function () {
        var id = jQuery(this).attr('id');
        var r_id = jQuery('.review_crowd').attr('id');
        var only_movie = 0;
        let prnt = jQuery(this).parents('.crowd_items');
        if (prnt.hasClass('crowd_select_movie')) {
            var only_movie = 1;
        }
        let bigprnt = jQuery(this).parents('.custom_crowd');


        jQuery('.advanced_search_first').html('').hide();

        jQuery.ajax({
                type: 'POST',
                data: {
                    'oper': 'get_search_movie',
                    'id': id,
                    'r_id': r_id,
                    'only_movie': only_movie
                },
                url: crowdsource_url,
                success: function (html) {
                    if (debug_mode) {
                        console.log('get_search_movie', html);
                    }
                    if (only_movie) {
                        jQuery('.check_container_main').html(html);

                        if (bigprnt.hasClass('add_audience_review')) {
                            let mid = jQuery('div.custom_crowd_movie').attr('data-value');

                            jQuery('input[name="wpcr3_postid"]').val(mid);
                            jQuery('div.wpcr3_respond_1').attr('data-value', mid).attr('data-postid', mid);
                            load_ajax_block('audience_form');

                        }
                    } else {
                        if (!jQuery('.check_inner_container[id="' + id + '"]').html()) {
                            jQuery('.check_container_main').append(html);
                        }

                    }


                }
            }
        );


        return false;


    });

    jQuery('body').on('click', '.note_big .note, .rating_block .note,  .r_content .note', function () {

        if (jQuery(this).hasClass('.nte')) {
            return;
        }


        if (jQuery(this).hasClass('togle_show')) {
            jQuery('.note_big .note, .rating_block .note, .r_content .note').removeClass('togle_show');
            jQuery('.note_show').hide();

        } else {

            jQuery('.note_big .note, .rating_block .note').removeClass('togle_show');
            jQuery('.note_show').hide();

            jQuery(this).addClass('togle_show');
            jQuery(this).find('.note_show').show();
        }
    });

    jQuery('body').on('click', '.flex_movies_block .movie_container .movie_poster>a', function () {

        if (jQuery('body').width() <= 550) {
            var parent = jQuery(this).parents('.movie_container');
            parent.toggleClass('opened');


            return false;
        }
    });


    jQuery('body').on('click', '.add_movie_todb', function () {
        var button = jQuery(this);
        button.attr('disabled', true);

        var movie = button.attr('id');


        let pr = jQuery(this).parents('tr.container_for_add_movies');

        if (pr.html()) {

            movie = pr.find('input.addmoviesfrom_id');


            movie = movie.val();

            if (!movie) {
                button.attr('disabled', false);
                return false;
            }

        }


        jQuery.ajax({
            type: 'POST',
            data: ({'add_movie': movie}),
            url: window.location.protocol +"//"+window.location.host+ "/wp-content/themes/custom_twentysixteen/template/ajax/search_ajax.php",
            success: function (html) {
                if (html == 1) {
                    button.after('<div class="add_succ">Successfully added to the queue. Check back soon!</div>');
                }
                button.remove();
            }
        });

        return false;
    });


    jQuery('body').on('click', '.submit_data .submit_user_data', function (e) {

        var $this = jQuery(this);

        if ($this.hasClass('in_process')) {
            return false;
        }

        $this.addClass('in_process');

        var closep = 0;
        let prnt = jQuery(this).parents('.crowd_data');
        if (!prnt.hasClass('crowd_data')) {
            prnt = jQuery(this).parents('.default_popup');
            closep = 1;

        }
        var result = new Object();
        prnt.find('input, select, textarea').each(function () {


            let cls = jQuery(this).attr('data-id');
            let data = jQuery(this).val();
            //console.log(cls+' '+jQuery(this).attr('class'));
            if (jQuery(this).attr('type') == 'checkbox') {
                if (jQuery(this).is(":checked")) {
                    data = '1';
                } else {
                    data = '0';
                }
            }

            result[cls] = data;

        });
        prnt.find('div.input_content').each(function () {


            let cls = jQuery(this).attr('data-id');
            let data = jQuery(this).html();

            result[cls] = data;
        });

        if (closep) {
            var type = jQuery(this).attr('id');
            var id = prnt.attr('id');
        } else {
            var link = prnt.prev('a.actor_crowdsource');
            var id = link.attr('data-value');
            var type = link.attr('class');

        }

        if (type == 'critic_crowd_link') {
            var msg_text = '<div id="progress-bar-container"><div class="txt"><i class="icon icon-loader"></i><span>Loading <span id="dtime">00:00</span></span></div><div id="progress-bar"></div></div>';
            jQuery('.form_msg').html(msg_text);
            var timer = 0, minutes, seconds;
            var display = $('#dtime');
            jQuery.progress_interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;
                display.text(minutes + ":" + seconds);
                ++timer;
            }, 1000);
        } else {
            jQuery('.form_msg').html('');
        }


        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'crowd_submit',
                'id': id,
                'type': type,
                'data': JSON.stringify(result),
            },
            url: crowdsource_url,
            success: function (html) {
                if (debug_mode) {
                    console.log('crowd_submit', html);
                }
                $this.removeClass('in_process');

                if (jQuery.progress_interval != '') {
                    clearInterval(jQuery.progress_interval);
                    jQuery.progress_interval = '';
                }

                if (html) {
                    var data = JSON.parse(html);

                    if (data.critic_data) {

                        prnt.html(data.critic_data);
                        // Init author autocomplite
                        author_autocomplete('.default_popup');
                        // Load wp editor

                        /*jQuery('body.jstiny').removeClass('jstiny');
                         if (typeof tinymce !== 'undefined') {
                         tinymce = undefined;
                         }*/
                        if (typeof wp_custom_editor == 'object') {
                            wp_custom_editor.load('id_crowd_text');
                        } else {
                            success = function () {
                                wp_custom_editor.load('id_crowd_text');
                            };
                            var third_scripts = {
                                wpeditorjs: '/wp-content/themes/custom_twentysixteen/js/wp-editor.js?v=1.14'
                            };
                            use_ext_js(success, third_scripts);
                        }
                    }

                    if (data.error && data.error.link) {
                        var error_msg = '<div class="alert alert-danger">' + data.error.link + '</div>';
                        jQuery('.form_msg').html(error_msg);

                    }

                } else {
                    let msg = '<p class="user_message_info">Thank you for your help, we\'ll check it soon.</p>';
                    if (closep) {
                        prnt.html(msg + '<div class="submit_data"><button class="button close" >Close</button></div>');
                    } else {
                        prnt.html('<div class="open_rating_container note_show">' + msg + '<div class="submit_data"><button class="button close" >Close</button></div></div>');
                    }

                }


            }
        });


        return false;
    });


    jQuery('body').on('click', '.submit_data .close', function (e) {


        let prnt = jQuery(this).parents('.crowd_data');
        //  console.log(prnt);
        if (!prnt.hasClass('crowd_data')) {
            prnt = jQuery(this).parents('.popup-content');
            prnt.html('');
            jQuery('.popup-close').click();

            return false;
        }
        prnt.prev('a.actor_crowdsource').attr('id', 'op');
        prnt.html('');
        return false;
    });


    jQuery('body').on('click', '.edit_comment, .edit_review', function (e) {

        var prnt = jQuery(this).parents('.note.edit');
        var id = prnt.attr('id');

        e.preventDefault();


        let prntbig = jQuery(this).parents('div.card');

        if (prntbig.hasClass('card')) {
            let big_prnt = prntbig.find('.content a');
            var pid_title = big_prnt.html();
            var image = prntbig.find('a.image').html();
            var link = big_prnt.attr('href');
            var block_summary = '';
        } else {
            let prntbig = jQuery(this).parents('.movie_container');


            var link = prntbig.find('.movie_poster a').attr('href');
            var image = prntbig.find('.image div.wrapper').html();
            var pid_title = prntbig.find('.movie_description .header_title a').html();
            var block_summary = prntbig.find('.movie_description .block_summary').html();
        }


        if (link)
        {


            if (link.indexOf(window.location.host) == -1) {
                link = window.location.protocol + '//' + window.location.host + link;
            }

        }


        else if (!link)
        {
            if (jQuery(this).hasClass('edit_comment')) {
                let commentElement = document.getElementById('movie_commnets');
                commentElement.scrollIntoView();

            }
            else if (jQuery(this).hasClass('edit_review')) {

                let commentElement = document.getElementById('audience_form');
                commentElement.scrollIntoView();

            }

            return false;
        }

        add_popup();

        let contenttext = '<div class="full_review_movie">' + image + '<div class="movie_link_desc"><span class="itm_hdr">' + pid_title + '</span><span>' + block_summary + '</span></div></div>';


        if (jQuery(this).hasClass('edit_comment')) {
            let data_object = new Object();
            data_object['page_url'] = link;
            data_object['page_identifier'] = id + ' ' + link;
            data_object['title'] = pid_title;
            jQuery('div[id="disqus_thread"]').remove();
            let content = '<div class="sub_content" ><div class="sub_content_text" >' + contenttext + '</div><div id="disqus_thread" ></div></div><label style="margin-top: -45px;margin-right: 15px;" for="action-popup" class="popup-close-btn">Close</label>';
            jQuery('.popup-content').html(content);
            jQuery('input[id="action-popup"]').click();
            discuss_config(data_object);
            jQuery('div[id="disquss_container"]').removeClass('loaded').addClass('not_load');
        } else if (jQuery(this).hasClass('edit_review')) {

            let content = '<div class="sub_content" ><div class="sub_content_text" >' + contenttext + '</div>' +
                '<div id="audience_form" class="wpcr3_respond_1" data-value="' + id + '" data-postid="' + id + '"></div>' +
                '</div><label style="margin-top: -85px;margin-right: 15px;" for="action-popup" class="popup-close-btn">Close</label>';

            jQuery('.popup-content').html(content);
            jQuery('input[id="action-popup"]').click();
            load_ajax_block('audience_form');
        }

        return false;

    });

    jQuery('body').on('click', '.add_critic, .edit_critic', function (e) {

        if (jQuery(this).hasClass('edit_critic')) {
            var prnt = jQuery(this).parents('.note.edit');
            var id = prnt.attr('id');
        } else {
            var id = jQuery(this).attr('id-data');
        }


        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'add_critic',
                'id': id
            },
            url: crowdsource_url,
            success: function (html) {
                if (debug_mode) {
                    console.log('add_critic', html);
                }
                add_popup();
                jQuery('.popup-content').html('<div id="' + id + '" class="default_popup"><h2>Add Critic Review:</h2><p>Please help improve ZR, add a critic review link</p>' + html + '</div>');
                jQuery('input[id="action-popup"]').click();

            }
        });


        return false;
    });
    jQuery('body').on('click', '.update_data', function (e) {

        let ths = jQuery(this);

        let type = ths.attr('id');
        ths.addClass('rotate');
        var id = ths.attr('data-value');

        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'ckeck_update_cowd_data',
                'id': id,
                'data_type': type
            },
            url: crowdsource_url,
            success: function (html) {
                if (html) {
                    ths.removeClass('rotate');
                    if (html == 1) {

                        ths.after(' updated');
                        ths.remove();
                    }


                    if (html == 2) {
                        if (type == 'last_imdb_pg_update' || type == 'last_cms_pg_update') {
                            let prnt = jQuery(this).parents('#family_friendly');
                            if (prnt.hasClass('loaded')) {
                                prnt.removeClass('loaded').addClass('not_load');
                                load_next_block('family_friendly');
                                return;
                            }

                            jQuery('input[id="action-popup"]').click();
                            jQuery('.rating_block[id="' + id + '"] a.read_more_rating').click();
                        } else if (type == 'last_pg_update') {
                            jQuery('input[id="action-popup"]').click();
                            jQuery('.rating_block[id="' + id + '"] a.how_calculate_rating').click();
                        }


                    }

                }

            }
        });

        return false;

    });

    jQuery('body').on('click', '.add_pg_rating_button, .edit_family_rating, .empty_ff_rating', function (e) {

        if (jQuery(this).hasClass('edit_family_rating')) {
            var prnt = jQuery(this).parents('.note.edit');
            var id = prnt.attr('id');
        } else if (jQuery(this).hasClass('empty_ff_popup_rating')) {
            var id = jQuery(this).attr('data-value');
            jQuery('input[id="action-popup"]').click();
        } else if (jQuery(this).hasClass('empty_ff_rating')) {
            var prnt = jQuery(this).parents('.rating_block');
            var id = prnt.attr('id');
        } else {
            var prnt = jQuery(this).parents('.movie_total_rating');
            var id = prnt.attr('data-value');
        }


        jQuery.ajax({
            type: 'POST',
            data: {
                'oper': 'pg_rating',
                'id': id
            },
            url: crowdsource_url,
            success: function (html) {
                if (debug_mode) {
                    console.log('pg_rating', html);
                }
                jQuery('.popup-container').remove();
                add_popup();
                jQuery('.popup-content').html('<div id="' + id + '" class="default_popup pg_popup"><h2>Edit Family Friendly Rating</h2><p>Please help improve ZR, add a Family Friendly Rating and leave your comment(s).</p>' + html + '</div>');
                jQuery('input[id="action-popup"]').click();
                return false;
            }
        });


        return false;
    });
    jQuery('body').on('change', '.row.rating  select, .check_inner_container  select.movie_link', function (e) {

        let val = jQuery(this).val();
        jQuery(this).attr('id', val);

        if (jQuery(this).hasClass('movie_link')) {
            if (val == 'remove') {
                let prnt = jQuery(this).parents('.check_inner_container:not(.main)');
                if (prnt.html()) {
                    prnt.remove();
                }
            }
        }


    });

    jQuery('body').on('click', '.actor_crowdsource', function (e) {

        var prnt = jQuery(this).parents('.actor_crowdsource_container');

        var op = jQuery(this).attr('id');
        var id = jQuery(this).attr('data-value');
        var cont = '';
        if (jQuery(this).hasClass('button_edit')) {
            cont = 'popup';
            var prnt = jQuery(this).parents('.card');

        }

        if (op == 'cl') {

            if (!cont) {

                prnt.find('.crowd_data').hide();
                jQuery(this).attr('id', 'op');
            }


        } else {
            if (!cont) {
                jQuery('.open_rating').attr('id', 'op');
                jQuery(this).attr('id', 'cl');
                jQuery('.open_rating_container').hide();
            }


            jQuery.ajax({
                type: 'POST',
                data: {
                    'oper': 'actor_crowd',
                    'id': id
                },
                url: crowdsource_url,
                success: function (html) {
                    if (debug_mode) {
                        console.log('actor_crowd', html);
                    }
                    if (cont) {

                        var aname = prnt.find('.a_data_n').html();

                        add_popup();
                        if (html.indexOf('user_message_info') > 0) {
                            jQuery('.popup-content').html('<div id="' + id + '" class="default_popup">' + html + '</div>');
                        } else {
                            jQuery('.popup-content').html('<div id="' + id + '" class="default_popup"><h2>Edit Actor Data</h2><span data-value="actor_popup" class="nte_info nte_right nte_open_down"></span><p class="center">Please help improve ZR by correcting & adding data.</p><h1>' + aname + '</h1>' + html + '</div>');
                        }


                        jQuery('input[id="action-popup"]').click();
                    } else {

                        prnt.find('.crowd_data').show().html('<div class="open_rating_container note_show">' + html + '</div>');
                    }


                }
            });
        }

        return false;
    });


    jQuery('body').on('click', '.open_rating', function (e) {

        var prnt = jQuery(this).parents('div.rating_row');

        var op = jQuery(this).attr('id');


        if (op == 'cl') {
            prnt.find('.open_rating_container').hide();
            jQuery(this).attr('id', 'op');

        } else {
            jQuery('.open_rating_container').hide();
            jQuery('.open_rating').attr('id', 'op');

            jQuery(this).attr('id', 'cl');
            prnt.find('.open_rating_container').show();

            // var cntnr_big = prnt.next('.open_rating_container');
            // var id = prnt.attr('id');
            // var movie_id = jQuery(this).parents('.movie_total_rating' ).attr('data-value');
        }

        return false;
    });

    jQuery('body').on('click', '.exlink', function () {

        if (jQuery(this).hasClass('no_link'))
        {
            return false;;
        }

        let type = jQuery(this).attr('id');
        let id = jQuery(this).parents('.rating_block').attr('id');
        if (!id) {
            id = jQuery(this).parents('.movie_total_rating').attr('data-value');
        }
        if (!id) {
            id = jQuery(this).parents('#global_zeitgeist').attr('data-value');
        }
        jQuery.ajax({
            type: 'POST',
            ///context: this,
            url:  site_url+template_path + "movie_rating.php",
            data: {"action": "get_link", "type": type, "id": id},
            success: function (data) {
                if (data) {
                    let ob = JSON.parse(data);
                    if (ob['url']) {
                        window.open(ob['url']);

                    }

                }
            }
        });

        return false;
    });


    jQuery('body').on('click', '.how_calculate_rating, .read_more_rating, .how_calculate_rwt_rating, .calculate_actor_data', function () {


        var post = {};


        if (jQuery(this).hasClass('calculate_actor_data')) {
            var a_id = jQuery(this).attr('data-actor');


            post = {
                'calculate_actor_data': a_id
            }
        } else {

            let movie_id = jQuery(this).parents('.movie_total_rating').attr('data-value');
            var rwt_id = jQuery(this).parents('.rating_block').attr('id');

            if (jQuery(this).hasClass('how_calculate_rwt_rating')) {


                post = {
                    'refresh_rwt_rating': 1,
                    'rwt_id': rwt_id,
                    'movie_id': movie_id
                }
            }


            if (jQuery(this).hasClass('how_calculate_rating')) {


                if (!rwt_id && jQuery(this).hasClass('ff_other_content_calculate')) {
                    rwt_id = jQuery(this).parents('#family_friendly').attr('data-value');
                }

                post = {
                    'refresh_rating': 1,
                    'movie_id': movie_id,
                    'rwt_id': rwt_id
                }
            } else if (jQuery(this).hasClass('read_more_rating')) {
                post = {
                    'read_more_rating': 1,
                    'movie_id': movie_id,
                    'rwt_id': rwt_id
                }
            }
        }


        jQuery.ajax({
            type: 'POST',
            data: (post),
            url: window.location.protocol +"//"+window.location.host+ "/wp-content/themes/custom_twentysixteen/template/ajax/search_ajax.php",
            success: function (html) {

                add_popup();

                jQuery('.popup-content').html('<div class="white_popup">' + html + '<label style="margin-top: -22px;margin-right: 15px;" for="action-popup" class="popup-close-btn">Close</label></div>');
                jQuery('input[id="action-popup"]:not(:checked)').click();
            }
        });


        return false;
    });


    jQuery('body').on('click', '.s_container_load', function () {
        let prnt = jQuery(this).parents('.column_inner_content');

        jQuery('.column_inner_content').removeClass('max_with');
        jQuery('.column_inner_content .s_container').addClass('smoched');
        prnt.addClass('max_with');
        prnt.find('.s_container').removeClass('smoched');

    });

    jQuery('body').on('click', '.column_inner_content.max_with .popup-close, .column_inner_content.max_with .calobl', function () {
        jQuery('.column_inner_content').removeClass('max_with');
        jQuery('.column_inner_content .s_container').addClass('smoched');
    });


    jQuery('body').on('click', '.s_container a.wordcloud', function () {

        let src = jQuery(this).attr('href');
        add_popup();

        jQuery('.popup-content').html('<div class="white_popup maxcontent"><a class="open_popup gl_zr_extlink" target="_blank" href="' + src + '"></a><iframe src="' + src + '"></iframe></div>');
        jQuery('input[id="action-popup"]').click();

        return false;

    });


    jQuery('body').on('click', '.s_container_smoth', function () {
        jQuery('.column_inner_content').removeClass('max_with');
        jQuery('.column_inner_content .s_container').addClass('smoched');
        let prnt = jQuery(this).parents('.s_container');
        let src = prnt.find('iframe').attr('src');
        add_popup();

        jQuery('.popup-content').html('<div class="white_popup maxcontent"><iframe src="' + src + '"></iframe></div>');
        jQuery('input[id="action-popup"]').click();

    });
    jQuery('body').on('click', '.ethnycity_select', function () {

        jQuery(this).toggleClass('select_disabled');

        //loaddata();
    });

    function load_main_graph() {

        var data = jQuery('.ethnic_graph.main_ethnic_graph').next('.ethnic_graph_data').html();
        create_Highcharts(data, 'main_movie_graph');

    }

    jQuery('body').on('click', '.ethnic_graph', function () {

        jQuery(this).toggleClass('activated');


        var data = jQuery(this).next('.ethnic_graph_data').html();

        if (jQuery(this).hasClass('main_ethnic_graph')) {

            var big_parent = jQuery('div.section_chart[id="container_main_movie_graph"]');

            if (!big_parent.html()) {
                if (typeof Highcharts == 'undefined') {
                    var third_scripts = {
                        hrts: 'https://code.highcharts.com/highcharts.js'
                    };
                    use_ext_js(load_main_graph, third_scripts);

                } else {
                    load_main_graph();

                }

            } else {
                if (jQuery(this).hasClass('activated')) {
                    big_parent.slideDown();
                } else {
                    big_parent.slideUp();
                }

            }


        } else {
            var big_parent = jQuery(this).parents('tr.actor_data');

            var count_col = big_parent.find('td').length;

            var graph_block = big_parent.next('tr.graph_block').html();
            if (!graph_block) {
                var id = Math.random().toString(36).substring(7);


                if (jQuery(this).hasClass('ethnic_graph_column')) {
                    big_parent.after('<tr class="graph_block"><td colspan="' + count_col + '"><div class="section_chart_big section_chart section_ethnic" id="container_' + id + '"></div></td></tr>');

                    create_Highcharts_columns(data, id);
                } else if (jQuery(this).hasClass('ethnic_graph_column_single')) {
                    big_parent.after('<tr class="graph_block"><td colspan="' + count_col + '"><div class="section_chart_main section_chart section_ethnic" id="container_' + id + '"></div></td></tr>');

                    create_Highcharts_colomn_single(data, id);
                } else {
                    big_parent.after('<tr class="graph_block"><td colspan="' + count_col + '"><div class="section_chart section_ethnic" id="container_' + id + '"></div></td></tr>');


                    create_Highcharts(data, id);
                }


            } else {
                if (jQuery(this).hasClass('activated')) {
                    big_parent.next('tr.graph_block').find('div.section_chart').slideDown();
                } else {
                    big_parent.next('tr.graph_block').find('div.section_chart').slideUp();
                }

            }
        }

        //loaddata();
    });
    jQuery('body').on('click', '.open_demographic', function (e) {


        var op = jQuery(this).attr('id');


        if (op == 'cl') {
            jQuery('.row_demograpic').hide();
            jQuery(this).attr('id', 'op');

        } else {
            jQuery('.row_demograpic').show();
            jQuery(this).attr('id', 'cl');
        }

        return false;
    });


    // if (jQuery('body').width()<=450)
    // {
    //  jQuery('#rwt_footer').addClass('not_load');
    //
    // }

    jQuery('body').on('click', '.disqus_content', function () {
        jQuery(this).addClass('disqus_content_full');
    });


    jQuery('body').on('click', '.fchan_btn input', function () {

        jQuery('.fchan_btn input.selected').removeClass('selected');
        jQuery(this).addClass('selected');

        let pnrt = $(this).parents('.column_inner_bottom');

        let id = $(this).attr('dataid');
        let bigprnt = $(this).parents('.column_inner_content');
        let target_contaner = bigprnt.find('.s_container a[id="' + id + '"]');

        let link_ext = bigprnt.find('a.gl_zr_extlink');
        let link_int = bigprnt.find('a[id="no_cloud"]');
        let url = link_ext.attr('href');

        jQuery('.fchan_btn input').each(function () {
            let inid = $(this).attr('dataid');
            if (url.indexOf('/' + inid + '/') !== -1) {
                url = url.replace('/' + inid + '/', '/' + id + '/');
                link_ext.attr('href', url);

                if (link_int.length) {
                    link_int.attr('href', url);

                }

                return;
            }

        });


        if (target_contaner.length) {

            bigprnt.find('.s_container>a').hide();
            target_contaner.show();
            let canvs = target_contaner.find('canvas');
            if (!canvs.attr('id')) {
                word_cloud(id);
            }
        }


        // iframe.attr('src',link);

    });


    jQuery('body').on('click', 'summary', function () {
            const details = jQuery(this).parent();
             let  firstDiv = details.find('section');

            if (!firstDiv)firstDiv = details.find('div');

            if (details.prop('open')) {

            }
            else {
                let height = firstDiv.height();
                if (height<200)height=200;

                const targetHeight = height;
                const startHeight = 0;
                const duration = 200;
                const startTime = performance.now();
                let animationFrame = null;

                firstDiv.css('overflow','hidden');

                function animate() {
                    const currentTime = performance.now();
                    const progress = (currentTime - startTime) / duration;

                    if (progress < 1) {
                        const newHeight = startHeight + (targetHeight - startHeight) * progress;
                        firstDiv.css('height', newHeight + 'px');
                        animationFrame = requestAnimationFrame(animate);
                    } else {

                        firstDiv.css('height','auto');
                        firstDiv.css('overflow','unset');
                    }
                }

                cancelAnimationFrame(animationFrame);
                animate();
            }
        });



    jQuery('body').on('click', '.nte_info', function () {
        let ts = jQuery(this);


        let prnt_popup = ts.parents('.nte_cnt').length;
        let inner_content;
        if (prnt_popup) {
            inner_content = ts.next('div.tlp_cnt').length;
        } else {

            inner_content = ts.find('.nte').length;
        }


        if (!inner_content) {
            let type = ts.attr('data-value');


            if (prnt_popup) {
                ts.after('<div class="tlp_cnt"><div class="nte_ajax_loading icon-loader"></div></div>');
            } else {
                let dopclass = '';
                if (ts.hasClass('nte_open_down')) {
                    dopclass = 'dwn';
                }
                let data = add_rating_block('nte_ajax', ' ', '<div class="nte_ajax_loading icon-loader"></div>', type, true, dopclass);
                ts.html(data);

            }


            //  ts.find('.nte').addClass('open');
            //  init_nte();
            ///load data
            jQuery.ajax({
                type: "GET",
                url: '/wp-content/themes/custom_twentysixteen/template/ajax/tooltips.php',
                data: {'type': type},

                success: function (html) {


                    if (prnt_popup) {
                        ts.next('div.tlp_cnt').html(html);
                    } else {
                        ts.find('.nte .nte_cnt').html(html);
                        ts.find('.btn').click();
                    }

                }
            });

        } else {

            if (prnt_popup) {
                ts.next('div.tlp_cnt').toggleClass('tlp_hidden');
            }

            ///console.log('already added');
        }

    });



    jQuery('body').on('click', '.accordion-header', function() {
        let prnt =jQuery(this).parent('.accordion-item');

        if (window.innerWidth >= 1240) {

            let big_prnt = prnt.parent('.accordion_section');
            if (big_prnt.length)
            {
                if (jQuery('.accordion_section > .accordion-item.active').length)
                {
                    jQuery('.accordion_section > .accordion-item.active').each( function (){
                      let b = jQuery(this);
                        if ( b ) {
                            if (prnt.hasClass('active') ) {
                             //   console.log('skip');
                                //skip
                            } else {
                                   b.removeClass('active');
                                   b.find('.accordion-content').hide();
                            }
                        }
                    });
                }

            }


        }

        var content = jQuery(this).next('.accordion-content');
        jQuery(this).toggleClass('is_open');
        content.slideToggle(500,function (e){
            prnt.toggleClass('active');

        if (prnt.hasClass('active'))
        {
        let  inner_cnt =  content.find('.not_load');
        if (inner_cnt.length)
        {
           let id = inner_cnt.attr('id');
            load_next_block(id);
        }
        }

        });
    });


});



jQuery('body').on('click', '.hide_left_sidebar', function () {
    let prnt = jQuery(this).parent('.site-header-menu');
    prnt.toggleClass('hidden_left');

    if (prnt.hasClass('hidden_left')) {
        localStorage.setItem('left_sidebar', 'hidden');
    } else {
        localStorage.setItem('left_sidebar', 'open');
    }

});


jQuery('body').on('click', '.disqus_content spoiler', function () {
    jQuery(this).toggleClass('spoiler_visible');
});

function init_short_codes(data_object) {
    init_spoilers();
    return false;
}

function init_spoilers() {
    jQuery('.spoiler_default:not(.init)').each(function () {
        var $this = jQuery(this);
        if (typeof $this.spoilerAlert !== "undefined") {
            $this.addClass('init');
            $this.spoilerAlert({max: 4, partial: 2});
        } else {
            ////try to load
            if (jQuery('body').hasClass('spoilerAlert')) {
                //console.log('spoilerAlert not init');
            } else {
                //console.log('spoilerAlert not load');
                var third_scripts = {
                    spoilerAlert: '/wp-content/themes/custom_twentysixteen/js/spoiler.min.js'
                };
                use_ext_js(init_spoilers, third_scripts);
            }
        }
    });
}


function author_autocomplete(form_class = '') {
    var $this = jQuery(form_class)
    if ($this.hasClass('init_at')) {
        return false;
    }
    $this.addClass('init_at');

    var cname_form = $this.find('input.critic_name:not(:disabled)').first();
    if (cname_form.length) {
        var aid_form = $this.find('input.critic_id').first();
        var aid = aid_form.attr('value');
        if (aid > 0) {
            cname_form.addClass('valid');
        }

        cname_form.closest('.row').after('<div class="crowd_items_search hide"><div class="advanced_search_menu crowd_items"><p class="advanced_search_head">Maybe you were looking for...<span class="advanced_search_head_close"></span></p><div class="search_results"></div></div></div>');
        var $crowd_items_search = $this.find('.crowd_items_search').first();
        $this.find('.advanced_search_head_close').click(function () {
            $crowd_items_search.removeClass('show');
            return false;
        });

        cname_form.keyup(function (e) {
            var $results = $this.find('.search_results').first();
            aid_form.val(0);
            cname_form.removeClass('valid');

            var keyword = cname_form.val();
            if (keyword.length >= 2) {
                $.ajax({
                    type: 'POST',
                    dataType: "json",
                    url: crowdsource_url,
                    data: {"action": "author_autocomplite", "keyword": keyword},
                    success: function (response) {
                        if (debug_mode) {
                            console.log('author_autocomplite', response);
                        }
                        if (response.type == "ok") {
                            $results.html('');
                            for (var i = 0; i < response.data.length; i++) {
                                var id = response.data[i]['id'];
                                var title = response.data[i]['title'];
                                $results.append('<div class="result" data-id="' + id + '" data-title="' + title + '">' + title + '</div>');
                            }

                            if (!$crowd_items_search.hasClass('show')) {
                                $crowd_items_search.addClass('show');
                            }

                            $this.find('.result').click(function () {
                                var $this_res = $(this);
                                cname_form.val($this_res.attr('data-title'));
                                var auto_aid = $this_res.attr('data-id');
                                if (auto_aid > 0) {
                                    cname_form.addClass('valid');
                                }
                                aid_form.val(auto_aid);
                                $crowd_items_search.removeClass('show');
                                return false;
                            });

                        } else {
                            $crowd_items_search.removeClass('show');
                        }


                    }
                });
            }
        });
    }
}

function init_tags() {
    var block_id = "review_scroll";
    var review_scroll = jQuery('#review_scroll');
    if (review_scroll.length && !review_scroll.hasClass('init_tags')) {
        review_scroll.addClass('init_tags');
        if (typeof review_scroll_tags !== 'undefined') {
            // console.log(review_scroll_tags);
            review_scroll.before('<div id="r_tags" class="search-sort"><ul class="sort-wrapper"><li class="nav-tab">Tags:</li></ul></div>');
            var r_tags = jQuery('#r_tags .sort-wrapper');
            for (var i in review_scroll_tags) {
                r_tags.append('<li class="nav-tab"><a href="/search/tab_critics/author_critic/tags_' + review_scroll_tags[i]['slug'] + '" data-id="' + review_scroll_tags[i]['id'] + '">' + review_scroll_tags[i]['name'] + '</a></li>');
            }

            r_tags.find('a').click(function () {
                var $this = jQuery(this);
                if ($this.hasClass('active')) {
                    $this.removeClass('active');
                } else {
                    $this.addClass('active');
                }

                var data = {};
                var type = 'tags[]';
                data[type] = [];
                jQuery('#r_tags li a.active').each(function (i, v) {
                    var v = jQuery(v), id = v.attr('data-id');
                    data[type].push(id);
                });

                jQuery.ajax({
                    type: "GET",
                    url: '/wp-content/themes/custom_twentysixteen/template/ajax/review_scroll.php',
                    data: data,
                    success: function (rtn) {
                        set_video_scroll(rtn, block_id);
                        initializeScroller(0, 'div[id="' + block_id + '"]');
                        init_nte();                        
                    },
                    error: function (rtn) {

                    }
                });

                return false;
            });
        }
    }
}

function loadScript($url, success = '') {
    jQuery.ajax({
        url: $url,
        dataType: 'script',
        cache: true,
        success: function () {
            if (success !== '') {
                success();
            }
        }
    });
}

function use_ext_js(f, third_scripts) {
    for (var n in third_scripts) {
        if (jQuery('body').hasClass(n)) {
            continue;
        } else {
            jQuery('body').addClass(n);
            var success = function () {
                use_ext_js(f, third_scripts);
            }
            loadScript(third_scripts[n], success);
            return;
        }
    }

    if (typeof f == 'function') {
        f();
    }

}

function add_css_list(css_list) {
    for (var n in css_list) {
        if (jQuery('body').hasClass(n)) {
            continue;
        } else {
            jQuery('body').addClass(n);
            jQuery("head").append("<link rel='stylesheet' id='dashicons-css'  href='" + css_list[n] + "' type='text/css' media='all' />");
        }
    }

}

jQuery.fn.upScrollButton = function (options) {
    var $ = jQuery;
    var options = $.extend({
        heightForButtonAppear: 20,
        heightForScrollUpTo: 0,
        scrollTopTime: 800,
        upScrollButtonId: 'move_up',
        upScrollButtonText: '',
        upScrollButtonFadeInTime: 0,
        upScrollButtonFadeOutTime: 300

    }, options);
    return this.each(function () {

        $('body').append('<a id="' + options.upScrollButtonId + '" href="#"></a>');
        $(window).scroll(function () {
            if ($(this).scrollTop() > options.heightForButtonAppear)
                $('a#' + options.upScrollButtonId).fadeIn(options.upScrollButtonFadeInTime);
            else
                $('a#' + options.upScrollButtonId).fadeOut(options.upScrollButtonFadeOutTime);
        });
        $('a#' + options.upScrollButtonId).click(function () {
            $('body,html').animate({
                scrollTop: options.heightForScrollUpTo
            }, options.scrollTopTime);
            return false;
        });
    });

}

// let previousHistoryLength = history.length;
//
// // Функция для удаления строки начиная с #gsc.tab= из URL
// function removeGscTabFromURL() {
//     console.log(history);
//
//     if (window.location.hash.startsWith('#gsc.tab=')) {
//         history.replaceState({}, document.title, window.location.href.split('#')[0]);
//     }
// }
//
//
// function checkAndRemove() {
//     if (history.length !== previousHistoryLength) {
//         previousHistoryLength = history.length;
//         removeGscTabFromURL();
//     }
// }
//
// setInterval(checkAndRemove, 500);