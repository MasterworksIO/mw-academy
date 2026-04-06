<?php
/**
 * Plugin Name: MW SEO Schema
 * Plugin URI:  https://masterworks.com
 * Description: Outputs structured data (JSON-LD schema markup) for SEO/AEO optimization across Masterworks Academy content types.
 * Version:     1.0.0
 * Author:      Masterworks
 * Author URI:  https://masterworks.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mw-seo-schema
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MW_SEO_SCHEMA_VERSION', '1.0.0' );

/**
 * Main schema output class.
 */
class MW_SEO_Schema {

    /**
     * Whether Yoast SEO is active.
     *
     * @var bool
     */
    private $yoast_active = false;

    /**
     * Publisher organization data.
     *
     * @var array
     */
    private $publisher = array();

    /**
     * Initialize the plugin.
     */
    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_schema' ), 1 );
    }

    /**
     * Main output handler. Builds and prints all relevant schema blocks.
     */
    public function output_schema() {
        $this->yoast_active = defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' );

        $this->publisher = array(
            '@type' => 'Organization',
            'name'  => 'Masterworks',
            'url'   => 'https://masterworks.com',
            'logo'  => array(
                '@type'  => 'ImageObject',
                'url'    => get_theme_mod( 'custom_logo' )
                    ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' )
                    : 'https://masterworks.com/images/logo.png',
                'width'  => 200,
                'height' => 64,
            ),
            'sameAs' => array(
                'https://twitter.com/masterworks',
                'https://www.linkedin.com/company/masterworks',
                'https://www.instagram.com/masterworks',
                'https://www.youtube.com/masterworks',
            ),
        );

        $schemas = array();

        // Homepage: Organization schema.
        if ( is_front_page() || is_home() ) {
            $schemas[] = $this->get_organization_schema();
            $schemas[] = $this->get_site_navigation_schema();
        }

        // BreadcrumbList for all non-homepage pages.
        if ( ! is_front_page() ) {
            $breadcrumb = $this->get_breadcrumb_schema();
            if ( $breadcrumb ) {
                $schemas[] = $breadcrumb;
            }
        }

        // Single post type schemas.
        if ( is_singular() ) {
            $post_type = get_post_type();
            $article   = $this->get_article_schema_for_post_type( $post_type );
            if ( $article ) {
                foreach ( (array) $article as $item ) {
                    $schemas[] = $item;
                }
            }
        }

        // Archive ItemList.
        if ( is_post_type_archive( 'daily-news' ) ) {
            $schemas[] = $this->get_daily_news_archive_schema();
        }

        // Output each schema block.
        foreach ( $schemas as $schema ) {
            if ( empty( $schema ) ) {
                continue;
            }
            $schema['@context'] = 'https://schema.org';
            echo "\n<!-- MW SEO Schema -->\n";
            echo '<script type="application/ld+json">';
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo '</script>' . "\n";
        }
    }

    // ------------------------------------------------------------------
    // Organization.
    // ------------------------------------------------------------------

    /**
     * Build Organization schema for homepage.
     *
     * @return array
     */
    private function get_organization_schema() {
        return array(
            '@type'       => 'Organization',
            '@id'         => home_url( '/#organization' ),
            'name'        => 'Masterworks',
            'alternateName' => 'Masterworks Academy',
            'url'         => 'https://masterworks.com',
            'logo'        => $this->publisher['logo'],
            'description' => get_bloginfo( 'description' ),
            'sameAs'      => $this->publisher['sameAs'],
            'foundingDate' => '2017',
            'founder'     => array(
                '@type' => 'Person',
                'name'  => 'Scott Lynn',
            ),
        );
    }

    // ------------------------------------------------------------------
    // SiteNavigationElement.
    // ------------------------------------------------------------------

    /**
     * Build SiteNavigationElement schema.
     *
     * @return array
     */
    private function get_site_navigation_schema() {
        $nav_items = array(
            'Research'      => home_url( '/academy/research/' ),
            'Data & Indices' => home_url( '/academy/data/' ),
            'Artists'       => home_url( '/academy/artists/' ),
            'Commentary'    => home_url( '/academy/commentary/' ),
            'Daily News'    => home_url( '/academy/news/' ),
            'White Papers'  => home_url( '/academy/white-papers/' ),
            'Culture'       => home_url( '/academy/cultural/' ),
        );

        $elements = array();
        foreach ( $nav_items as $name => $url ) {
            $elements[] = array(
                '@type' => 'SiteNavigationElement',
                'name'  => $name,
                'url'   => $url,
            );
        }

        return array(
            '@type'           => 'ItemList',
            'name'            => 'Academy Navigation',
            'itemListElement' => $elements,
        );
    }

    // ------------------------------------------------------------------
    // BreadcrumbList.
    // ------------------------------------------------------------------

    /**
     * Build BreadcrumbList schema.
     *
     * @return array|null
     */
    private function get_breadcrumb_schema() {
        // Skip if Yoast already handles breadcrumbs.
        if ( $this->yoast_active && apply_filters( 'mw_seo_schema_skip_yoast_breadcrumbs', true ) ) {
            return null;
        }

        $items   = array();
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Academy',
            'item'     => home_url( '/' ),
        );

        $position = 2;

        if ( is_singular() ) {
            $post_type_obj = get_post_type_object( get_post_type() );
            if ( $post_type_obj && $post_type_obj->has_archive ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'name'     => $post_type_obj->label,
                    'item'     => get_post_type_archive_link( get_post_type() ),
                );
                $position++;
            }
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            );
        } elseif ( is_post_type_archive() ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => post_type_archive_title( '', false ),
                'item'     => get_post_type_archive_link( get_queried_object()->name ?? get_post_type() ),
            );
        } elseif ( is_tax() || is_category() || is_tag() ) {
            $term    = get_queried_object();
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $term->name,
                'item'     => get_term_link( $term ),
            );
        } elseif ( is_page() ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            );
        }

        return array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        );
    }

    // ------------------------------------------------------------------
    // Per-post-type article schema.
    // ------------------------------------------------------------------

    /**
     * Route to the appropriate schema builder for the current post type.
     *
     * @param string $post_type The post type slug.
     * @return array|array[]|null
     */
    private function get_article_schema_for_post_type( $post_type ) {
        switch ( $post_type ) {
            case 'research-report':
                return $this->get_research_report_schema();
            case 'artist-dossier':
                return $this->get_artist_dossier_schema();
            case 'market-commentary':
                return $this->get_market_commentary_schema();
            case 'explainer':
                return $this->get_explainer_schema();
            case 'data-index':
                return $this->get_data_index_schema();
            case 'daily-news':
                return $this->get_daily_news_single_schema();
            case 'white-paper':
                return $this->get_white_paper_schema();
            case 'cultural-update':
                return $this->get_cultural_update_schema();
            default:
                // For standard posts, only output if Yoast is not active.
                if ( ! $this->yoast_active && 'post' === $post_type ) {
                    return array( $this->build_base_article( 'Article' ) );
                }
                return null;
        }
    }

    // ------------------------------------------------------------------
    // Research Reports: ScholarlyArticle + Dataset.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_research_report_schema() {
        $schemas   = array();
        $scholarly = $this->build_base_article( 'ScholarlyArticle' );

        $report_type = '';
        if ( function_exists( 'get_field' ) ) {
            $report_type = get_field( 'report_type' ) ?: '';
        }
        if ( $report_type ) {
            $scholarly['articleSection'] = ucwords( str_replace( '_', ' ', $report_type ) );
        }

        // Skip base article if Yoast is active, but always include ScholarlyArticle.
        $schemas[] = $scholarly;

        // Dataset for the underlying data.
        $dataset = array(
            '@type'       => 'Dataset',
            'name'        => get_the_title() . ' Data',
            'description' => wp_strip_all_tags( get_the_excerpt() ),
            'creator'     => $this->publisher,
            'datePublished' => get_the_date( 'c' ),
        );

        $pdf = function_exists( 'get_field' ) ? get_field( 'download_pdf' ) : null;
        if ( $pdf && ! empty( $pdf['url'] ) ) {
            $dataset['distribution'] = array(
                '@type'       => 'DataDownload',
                'encodingFormat' => 'application/pdf',
                'contentUrl'  => $pdf['url'],
            );
        }

        $pub_date = function_exists( 'get_field' ) ? get_field( 'publication_date' ) : '';
        if ( $pub_date ) {
            $dataset['temporalCoverage'] = $pub_date;
        }

        $schemas[] = $dataset;

        return $schemas;
    }

    // ------------------------------------------------------------------
    // Artist Dossiers: Article + Person.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_artist_dossier_schema() {
        $schemas = array();

        $article = $this->build_base_article( 'Article' );
        $schemas[] = $article;

        // Person schema for the artist.
        $person = array(
            '@type' => 'Person',
            'name'  => get_the_title(),
            'url'   => get_permalink(),
        );

        if ( function_exists( 'get_field' ) ) {
            $nationality = get_field( 'nationality' );
            $birth_year  = get_field( 'birth_year' );
            $death_year  = get_field( 'death_year' );
            $medium      = get_field( 'medium' );

            if ( $nationality ) {
                $person['nationality'] = $nationality;
            }
            if ( $birth_year ) {
                $person['birthDate'] = (string) $birth_year;
            }
            if ( $death_year ) {
                $person['deathDate'] = (string) $death_year;
            }
            if ( $medium ) {
                $person['knowsAbout'] = ucfirst( str_replace( '_', ' ', $medium ) );
            }
        }

        if ( has_post_thumbnail() ) {
            $person['image'] = get_the_post_thumbnail_url( get_the_ID(), 'mw-hero' );
        }

        $person['description'] = wp_strip_all_tags( get_the_excerpt() );

        $schemas[] = $person;

        return $schemas;
    }

    // ------------------------------------------------------------------
    // Market Commentary: OpinionNewsArticle.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_market_commentary_schema() {
        $article = $this->build_base_article( 'OpinionNewsArticle' );

        if ( function_exists( 'get_field' ) ) {
            $sentiment = get_field( 'sentiment' );
            if ( $sentiment ) {
                $article['backstory'] = 'Market sentiment: ' . ucfirst( $sentiment );
            }
        }

        return array( $article );
    }

    // ------------------------------------------------------------------
    // Explainers: Article + FAQPage (if FAQ fields exist).
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_explainer_schema() {
        $schemas = array();

        $article = $this->build_base_article( 'Article' );

        if ( function_exists( 'get_field' ) ) {
            $difficulty = get_field( 'difficulty_level' );
            if ( $difficulty ) {
                $article['educationalLevel'] = ucfirst( $difficulty );
            }
        }

        $schemas[] = $article;

        // FAQPage schema from ACF repeater.
        if ( function_exists( 'get_field' ) ) {
            $faq_items = get_field( 'faq_items' );
            if ( $faq_items && is_array( $faq_items ) && count( $faq_items ) > 0 ) {
                $main_entity = array();
                foreach ( $faq_items as $faq ) {
                    if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
                        continue;
                    }
                    $main_entity[] = array(
                        '@type' => 'Question',
                        'name'  => $faq['question'],
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text'  => wp_strip_all_tags( $faq['answer'] ),
                        ),
                    );
                }

                if ( ! empty( $main_entity ) ) {
                    $schemas[] = array(
                        '@type'      => 'FAQPage',
                        'mainEntity' => $main_entity,
                    );
                }
            }
        }

        return $schemas;
    }

    // ------------------------------------------------------------------
    // Data & Indices: Dataset + DataCatalog.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_data_index_schema() {
        $schemas = array();

        $dataset = array(
            '@type'         => 'Dataset',
            'name'          => get_the_title(),
            'description'   => wp_strip_all_tags( get_the_excerpt() ),
            'creator'       => $this->publisher,
            'datePublished' => get_the_date( 'c' ),
            'dateModified'  => get_the_modified_date( 'c' ),
            'url'           => get_permalink(),
            'license'       => 'https://masterworks.com/terms',
        );

        $schemas[] = $dataset;

        $schemas[] = array(
            '@type'   => 'DataCatalog',
            'name'    => 'Masterworks Art Market Data',
            'url'     => get_post_type_archive_link( 'data-index' ),
            'creator' => $this->publisher,
            'dataset' => array(
                '@type' => 'Dataset',
                'name'  => get_the_title(),
                'url'   => get_permalink(),
            ),
        );

        return $schemas;
    }

    // ------------------------------------------------------------------
    // Daily News (single): NewsArticle + ItemList.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_daily_news_single_schema() {
        $schemas = array();

        $news_article = $this->build_base_article( 'NewsArticle' );
        $schemas[]    = $news_article;

        // Build ItemList from news_items ACF repeater.
        if ( function_exists( 'get_field' ) ) {
            $news_items = get_field( 'news_items' );
            if ( $news_items && is_array( $news_items ) ) {
                $list_items = array();
                $pos        = 1;
                foreach ( $news_items as $item ) {
                    $list_item = array(
                        '@type'    => 'ListItem',
                        'position' => $pos,
                        'name'     => $item['headline'] ?? '',
                    );
                    if ( ! empty( $item['source_url'] ) ) {
                        $list_item['url'] = $item['source_url'];
                    }
                    $list_items[] = $list_item;
                    $pos++;
                }
                if ( ! empty( $list_items ) ) {
                    $schemas[] = array(
                        '@type'           => 'ItemList',
                        'name'            => get_the_title(),
                        'itemListElement' => $list_items,
                    );
                }
            }
        }

        return $schemas;
    }

    // ------------------------------------------------------------------
    // Daily News (archive): ItemList.
    // ------------------------------------------------------------------

    /**
     * @return array
     */
    private function get_daily_news_archive_schema() {
        global $wp_query;

        $list_items = array();
        $pos        = 1;

        if ( $wp_query->have_posts() ) {
            foreach ( $wp_query->posts as $p ) {
                $list_items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'url'      => get_permalink( $p ),
                    'name'     => get_the_title( $p ),
                );
                $pos++;
            }
        }

        return array(
            '@type'           => 'ItemList',
            'name'            => 'The Daily Brushstroke',
            'url'             => get_post_type_archive_link( 'daily-news' ),
            'itemListElement' => $list_items,
        );
    }

    // ------------------------------------------------------------------
    // White Papers: ScholarlyArticle.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_white_paper_schema() {
        $article = $this->build_base_article( 'ScholarlyArticle' );

        if ( function_exists( 'get_field' ) ) {
            $abstract = get_field( 'abstract' );
            if ( $abstract ) {
                $article['abstract'] = wp_strip_all_tags( $abstract );
            }

            $page_count = get_field( 'page_count' );
            if ( $page_count ) {
                $article['pagination'] = $page_count . ' pages';
            }

            $pdf = get_field( 'download_pdf' );
            if ( $pdf && ! empty( $pdf['url'] ) ) {
                $article['associatedMedia'] = array(
                    '@type'          => 'MediaObject',
                    'contentUrl'     => $pdf['url'],
                    'encodingFormat' => 'application/pdf',
                );
            }
        }

        return array( $article );
    }

    // ------------------------------------------------------------------
    // Cultural Updates: NewsArticle.
    // ------------------------------------------------------------------

    /**
     * @return array[]
     */
    private function get_cultural_update_schema() {
        $article = $this->build_base_article( 'NewsArticle' );

        if ( function_exists( 'get_field' ) ) {
            $update_type = get_field( 'update_type' );
            if ( $update_type ) {
                $article['articleSection'] = ucwords( str_replace( '_', ' ', $update_type ) );
            }
        }

        return array( $article );
    }

    // ------------------------------------------------------------------
    // Helpers.
    // ------------------------------------------------------------------

    /**
     * Build a base article schema with common properties.
     *
     * If Yoast is active, we skip outputting generic Article/NewsArticle
     * that Yoast already handles, but we still return the array so
     * specialized properties (ScholarlyArticle, FAQPage, Dataset, Person)
     * can be layered on top.
     *
     * @param string $type Schema.org type (Article, NewsArticle, ScholarlyArticle, etc.).
     * @return array
     */
    private function build_base_article( $type = 'Article' ) {
        $post_id = get_the_ID();

        $article = array(
            '@type'            => $type,
            '@id'              => get_permalink() . '#article',
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id'   => get_permalink(),
            ),
            'headline'      => get_the_title(),
            'description'   => wp_strip_all_tags( get_the_excerpt() ),
            'datePublished' => get_the_date( 'c' ),
            'dateModified'  => get_the_modified_date( 'c' ),
            'author'        => array(
                '@type' => 'Person',
                'name'  => get_the_author(),
                'url'   => get_author_posts_url( get_the_author_meta( 'ID' ) ),
            ),
            'publisher' => $this->publisher,
            'url'       => get_permalink(),
        );

        // Image.
        if ( has_post_thumbnail( $post_id ) ) {
            $img_id  = get_post_thumbnail_id( $post_id );
            $img_src = wp_get_attachment_image_src( $img_id, 'mw-hero' );
            if ( $img_src ) {
                $article['image'] = array(
                    '@type'  => 'ImageObject',
                    'url'    => $img_src[0],
                    'width'  => $img_src[1],
                    'height' => $img_src[2],
                );
            }
        }

        // Word count for reading time.
        $content    = get_post_field( 'post_content', $post_id );
        $word_count = str_word_count( wp_strip_all_tags( $content ) );
        if ( $word_count > 0 ) {
            $article['wordCount'] = $word_count;
        }

        return $article;
    }
}

// Initialize.
new MW_SEO_Schema();
