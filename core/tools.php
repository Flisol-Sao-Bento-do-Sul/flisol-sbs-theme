<?php
/**
 * Odin tools.
 */

/**
 * Pagination.
 *
 * @global array $wp_query   Current WP Query.
 * @global array $wp_rewrite URL rewrite rules.
 *
 * @param int $mid           Total of items that will show along with the current page.
 * @param int $end           Total of items displayed for the last few pages.
 * @param bool $show         Show all items.
 *
 * @return string            Return the pagination.
 */
function odin_pagination( $mid = 2, $end = 1, $show = false ) {

    // Prevent show pagination number if Infinite Scroll of JetPack is active.
    if ( ! isset( $_GET[ 'infinity' ] ) ) {

        global $wp_query, $wp_rewrite;

        $total_pages = $wp_query->max_num_pages;

        if ( $total_pages > 1 ) {
            $current_page = max( 1, get_query_var( 'paged' ) );
            $url_base = $wp_rewrite->pagination_base;
            $big = 999999999; // Need an unlikely integer.

            // Sets the URL format.
            if ( $wp_rewrite->permalink_structure )
                $format = '?paged=%#%';
            else
                $format = '/' . $url_base . '/%#%';

            // Sets the paginate_links arguments.
            $arguments = apply_filters( 'odin_pagination_args', array(
                    'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                    'format' => $format,
                    'current' => $current_page,
                    'total' => $total_pages,
                    'show_all' => $show,
                    'end_size' => $end,
                    'mid_size' => $mid,
                    'type' => 'list',
                    'prev_text' => __( '&laquo; Previous', 'odin' ),
                    'next_text' => __( 'Next &raquo;', 'odin' ),
                )
            );

            $pagination = '<div class="pagination-wrap">' . paginate_links( $arguments ) . '</div>';

            // Prevents duplicate bars in the middle of the url.
            if ( $url_base )
                $pagination = str_replace( '//' . $url_base . '/', '/' . $url_base . '/', $pagination );

            return $pagination;
        }
    }
}

/**
 * Related Posts.
 *
 * Usage:
 * To show related by categories:
 * Add in single.php <?php odin_related_posts(); ?>
 * To show related by tags:
 * Add in single.php <?php odin_related_posts( 'tag' ); ?>
 *
 * @global array $post         WP global post.
 *
 * @param string $display      Set category or tag.
 * @param int    $qty          Number of posts to be displayed (default 5).
 * @param string $title        Set the widget title.
 * @param bool   $thumb        Enable or disable displaying images.
 *
 * @return string              Related Posts.
 */
function odin_related_posts( $display = 'category', $qty = 5, $title = 'Artigos Relacionados', $thumb = true ) {
    global $post;

    $show = false;
    $post_qty = (int) $qty;

    // Creates arguments for WP_Query.
    switch ( $display ) {
        case 'tag':
            $tags = wp_get_post_tags( $post->ID );

            if ( $tags ) {
                // Enables the display.
                $show = true;

                $tag_ids = array();
                foreach ( $tags as $individual_tag )
                    $tag_ids[] = $individual_tag->term_id;

                $args = array(
                    'tag__in' => $tag_ids,
                    'post__not_in' => array( $post->ID ),
                    'posts_per_page' => $post_qty,
                    'ignore_sticky_posts' => 1
                );
            }
            break;

        default :
            $categories = get_the_category( $post->ID );

            if ( $categories ) {

                // Enables the display.
                $show = true;

                $category_ids = array();
                foreach ( $categories as $individual_category )
                    $category_ids[] = $individual_category->term_id;

                $args = array(
                    'category__in' => $category_ids,
                    'post__not_in' => array( $post->ID ),
                    'showposts' => $post_qty,
                    'ignore_sticky_posts' => 1,
                );
            }
            break;
    }

    if ( $show ) {

        $related = new WP_Query( $args );
        if ( $related->have_posts() ) {

            $layout = '<div id="related-post">';
            $layout .= '<h3>' . esc_attr( $title ) . '</h3>';
            $layout .= '<ul>';

            while ( $related->have_posts() ) {
                $related->the_post();

                $layout .= '<li>';

                if ( $thumb ) {
                    // Filter for use the functions of thumbnails.php in place of the_post_thumbnails().
                    $image = apply_filters( 'odin_related_posts', get_the_post_thumbnail( get_the_ID(), 'thumbnail' ) );

                    $layout .= '<span class="thumb">';
                    $layout .= sprintf( '<a href="%s" title="%s">%s</a>', get_permalink(), get_the_title(), $image );
                    $layout .= '</span>';
                }

                $layout .= '<span class="text">';
                $layout .= sprintf( '<a href="%1$s" title="%2$s">%2$s</a>', get_permalink(), get_the_title() );
                $layout .= '</span>';

                $layout .= '</li>';
            }

            $layout .= '</ul>';
            $layout .= '</div>';

            echo $layout;
        }
        wp_reset_postdata();
    }
}

/**
 * Custom excerpt for content or title.
 *
 * Usage:
 * Place: <?php echo odin_excerpt( 'excerpt', value ); ?>
 *
 * @param string $type  Sets excerpt or title.
 * @param int    $limit Sets the length of excerpt.
 *
 * @return string       Return the excerpt.
 */
function odin_excerpt( $type = 'excerpt', $limit = 5 ) {
    $limit = (int) $limit;

    // Set excerpt type.
    switch ( $type ) {
        case 'title':
            $excerpt = get_the_title();
            break;

        default :
            $excerpt = get_the_excerpt();
            break;
    }

    return wp_trim_words( $excerpt, $limit );
}

/**
 * Breadcrumbs.
 *
 * @param  string $homepage  Homepage name.
 *
 * @return string            HTML of breadcrumbs.
 */
function odin_breadcrumbs( $homepage = 'In&iacute;cio' ) {
    // Default html.
    $current_before = '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/">';
    $current_after  = '</li>';

    if ( ! is_home() && ! is_front_page() || is_paged() ) {
        global $post;

        // First level.
        echo '<ol id="breadcrumbs" class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">';
        echo '<li itemprop="title"><a href="' . home_url() . '" rel="nofollow" itemprop="url"><span itemprop="title">' . $homepage . '</span></a></li>';

        // Single post.
        if ( is_single() && ! is_attachment() ) {
            global $post;

            // Checks if is a custom post type.
            if ( 'post' != $post->post_type ) {
                $post_type = get_post_type_object($post->post_type);

                echo '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_post_type_archive_link($post_type->name) . '"><span itemprop="title">' . $post_type->label . '</span></a></span> ';

                // Gets post type taxonomies.
                $taxonomy = get_object_taxonomies( $post_type->name );
                if ( $taxonomy ) {
                    // Gets post terms.
                    $term = get_the_terms( $post->ID, $taxonomy[0] ) ? array_shift( get_the_terms( $post->ID, $taxonomy[0] ) ) : '';

                    if ( $term ) {
                        echo '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_term_link( $term ) . '"><span itemprop="title">' . $term->name . '</span></a></span> ';
                    }
                }
            } else {
                $category = get_the_category();
                $category = $category[0];

                echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a itemprop="url" href="' . get_category_link( $category->term_id ) . '"><span itemprop="title">' . $category->name . '</span></a></li>';
            }

            echo $current_before . '<span class="active" itemprop="title">' . get_the_title() . '</span>' . $current_after;

        // Single attachment.
        } elseif ( is_attachment() ) {
            $parent   = get_post( $post->post_parent );
            $category = get_the_category( $parent->ID );
            $category = $category[0];

            echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_category_link( $category->term_id ) . '"><span itemprop="title">' . $category->name . '</span></a></li>';

            echo '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_permalink( $parent ) . '"><span itemprop="title">' . $parent->post_title . '</span></a></span> ';

            echo $current_before . get_the_title() . $current_after;

        // Page without parents.
        } elseif ( is_page() && ! $post->post_parent ) {
            echo $current_before . get_the_title() . $current_after;

        // Page with parents.
        } elseif ( is_page() && $post->post_parent ) {
            $parent_id   = $post->post_parent;
            $breadcrumbs = array();

            while ( $parent_id ) {
                $page = get_page( $parent_id );

                $breadcrumbs[] = '<span itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a itemprop="url" href="' . get_permalink( $page->ID ) . '"><span itemprop="title">' . get_the_title( $page->ID ) . '</span></a></span>';
                $parent_id  = $page->post_parent;
            }

            $breadcrumbs = array_reverse( $breadcrumbs );

            foreach ( $breadcrumbs as $crumb ) {
                echo $crumb . ' ';
            }

            echo $current_before . get_the_title() . $current_after;

        // Category archive.
        } elseif ( is_category() ) {
            global $wp_query;

            $category_object  = $wp_query->get_queried_object();
            $category_id      = $category_object->term_id;
            $current_category = get_category( $category_id );
            $parent_category  = get_category( $current_category->parent );

            // Displays parent category.
            if ( 0 != $current_category->parent ) {
                echo get_category_parents( $parent_category, TRUE, ' ' );
            }

            printf( __( '%sCategory: %s%s', 'odin' ), $current_before, single_cat_title( '', false ), $current_after );

        // Tags archive.
        } elseif ( is_tag() ) {
            printf( __( '%sTag: %s%s', 'odin' ), $current_before, single_tag_title( '', false ), $current_after );

        // Custom post type archive.
        } elseif ( is_post_type_archive() ) {
            echo $current_before . post_type_archive_title( '', false ) . $current_after;

        // Search page.
        } elseif ( is_search() ) {
            printf( __( '%sSearch result for: &quot;%s&quot;%s', 'odin' ), $current_before, get_search_query(), $current_after );

        // Author archive.
        } elseif ( is_author() ) {
            global $author;
            $userdata = get_userdata( $author );

            echo $current_before . __( 'Posted by', 'odin' ) . ' ' . $userdata->display_name . $current_after;

        // Archives per days.
        } elseif ( is_day() ) {
            echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_year_link( get_the_time( 'Y' ) ) . '"><span itemprop="title">' . get_the_time( 'Y' ) . '</span></a></li>';

            echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a itemprop="url" href="' . get_month_link( get_the_time( 'Y' ),get_the_time( 'm' ) ) . '"><span itemprop="title">' . get_the_time( 'F' ) . '</span></a></li>';

            echo $current_before . '<span class="active" itemprop="title">' . get_the_time( 'd' ) . $current_after . '</span>';

        // Archives per month.
        } elseif ( is_month() ) {
            echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a itemprop="url" href="' . get_year_link( get_the_time( 'Y' ) ) . '"><span itemprop="title">' . get_the_time( 'Y' ) . '</span></a></li>';

            echo $current_before . '<span class="active" itemprop="title">' . get_the_time( 'F' ) . $current_after . '</span>';

        // Archives per year.
        } elseif ( is_year() ) {
            echo $current_before . '<span class="active" itemprop="title">' . get_the_time( 'Y' ) . $current_after . '</span>';

        // Archive fallback for custom taxonomies.
        } elseif ( is_archive() ) {
            global $wp_query;

            $current_object = $wp_query->get_queried_object();
            $taxonomy        = get_taxonomy( $current_object->taxonomy );
            $term_name       = $current_object->name;

            // Displays parent term.
            if ( 0 != $current_object->parent ) {
                $parent_term = get_term( $current_object->parent, $current_object->taxonomy );

                echo '<li itemprop="child" itemscope itemtype="http://data-vocabulary.org/Breadcrumb/"><a itemprop="url" href="' . get_term_link( $parent_term ) . '"><span itemprop="title">' . $parent_term->name . '</span></a></li>';
            }

            echo $current_before . $taxonomy->label . ': ' . $term_name . $current_after;

        // 404 page.
        } elseif ( is_404() ) {
            echo $current_before . __('404 Error', 'odin' ) . $current_after;
        }

        // Gets pagination.
        if ( get_query_var( 'paged' ) ) {

            if ( is_archive() ) {
                echo ' (' . sprintf( __( 'Page %s', 'odin' ), get_query_var('paged') ) . ')';
            } else {
                printf( __( 'Page %s', 'odin' ), get_query_var('paged') );
            }
        }

        echo '</ol>';
    }
}

/**
 * Debug variables.
 *
 * @param  mixed $variable Object or Array for debug.
 *
 * @return string          Human-readable information.
 */
function odin_debug( $variable ) {
    echo '<pre>' . print_r( $variable, true ) . '</pre>';
}

/**
 * Menu Fallback.
 *
 * @param  array $args Menu arguments.
 *
 * @return string      Menu in HTML.
 */
function odin_menu_fallback( $args ) {
    if ( ! current_user_can( 'manage_options' ) )
        return;

    extract( $args );

    $link = sprintf( '%s<a href="%s">%s%s%s</a>%s', $link_before, admin_url( 'nav-menus.php' ), $before, __( 'Add a menu', 'odin' ), $after, $link_after );

    if ( false !== stripos( $items_wrap, '<ul' ) || false !== stripos( $items_wrap, '<ol' ) )
        $link = '<li>' . $link . '</li>';

    $output = sprintf( $items_wrap, $menu_id, $menu_class, $link );
    if ( ! empty ( $container ) )
        $output  = sprintf( '<%1$s class="%2$s" id="%3$s">%5$s</%1$s>', $container, $container_class, $container_id, $output );

    if ( $echo )
        echo $output;

    return $output;
}
