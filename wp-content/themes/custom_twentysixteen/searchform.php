<?php
/**
 * Template for displaying search forms in Twenty Sixteen
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

?>
<div class="customsearch_container">
    <div class="customsearch_component__inner">
        <input type="text" class="customsearch_input"  placeholder="Search Movies, TV,  Reviews" autocomplete="off" >
        <a class="customsearch_container__advanced-search-button" href="#" title="Advanced Search"></a>
        <a class="customsearch_component__button" href="#" type="button" title="Search"></a>
        <div class="advanced_search_ajaxload"></div>

        <div class="advanced_search_menu advanced_search_hidden">
            <div class="advanced_search_field">
                <input type="text" class="customsearch_input_advanced" placeholder="Search movies, reviews" autocomplete="off"><a class="customsearch_button_advanced" href="#" type="button" title="Search"></a>
            </div>
            <div class="advanced_search_first"></div>
            <div class="advanced_search_data advanced_search_hidden"></div>
        </div>
    </div>
</div>

