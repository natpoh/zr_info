<form method="post" action="/analytics" id="search-form" class="analytics">
    <div class="flex-page">
        <div id="primary" class="content-full with-sidebar-left">
            <main id="main" class="site-main" role="main">
                <header class="page-header">
                    <h1 id="search-title" class="page-title ajload"><?php print $search_title ?></h1>
                </header><!-- .page-header -->
                <div id="spform">
                    <div class="sbar">
                        <a class="clear<?php if ($keywords) print ' active' ?>" href="/analytics" title="Clear"></a>                        
                        <input type="search" name="s" id="sbar" size="15" value="<?php print $keywords ?>" placeholder="Search Movies, TV, Reviews" autocomplete="off">
                    </div>
                    <input type="submit" id="submit" class="btn" value="find">        
                </div>
                <div class="filters_btn">
                    <a id="fiters-btn" class="search-filters-btn" href="#filters" title="Advanced search filters"><span class="filters-icon"></span> Search filters</a>                    
                </div>
                <?php $search_front->theme_search_url($search_url, $search_text, $inc, $user_filter_id); ?>
                <?php
                if ($show_content):
                    //Search tabs
                    print $search_tabs;
                    print $fiters;
                    ?>
                    <div id="page-facet">
                        <?php
                        $search_front->page_facet($results, $tab_key);
                        gmi('page_facet');
                        ?>
                    </div>
                    <?php print $sort; ?>
                    <div id="page-content" class="ajload">
                        <?php
                        $search_front->page_content($results, $tab_key);
                        gmi('page_content');
                        ?>                        
                    </div>
                <?php endif; ?>
                <footer class="entry-footer">
                    <?php // TODO Edit post link            ?>
                </footer><!-- .entry-footer -->
            </main><!-- .site-main --> 
        </div><!-- .content-area -->
        <div id="secondary" class="sidebar-left">
            <div class="sidebar-inner">
                <div class="mob-header"><span class="close"></span></div>
                <div id="search-facets">                   
                    <h2 class="title">Filters</h2>
                    <ul class="tab-wrapper sidebar-tabs">
                        <li class="nav-tab"><a href="/search">Search</a></li>
                        <li class="nav-tab active"><a href="/analytics">Analytics</a></li>                        
                    </ul>
                    <div id="facets">
                        <?php
                        if ($show_facets) {
                            $search_front->show_facets($facets, $tab_key);
                            gmi('show_facets');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<?php
if (defined("DEBUG_GMI")) {
    global $gmi;
    print '<pre>';
    print_r($gmi);
    print '</pre>';
}
?>