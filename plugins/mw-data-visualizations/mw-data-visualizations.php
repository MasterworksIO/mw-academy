<?php
/**
 * Plugin Name: MW Data Visualizations
 * Plugin URI:  https://masterworks.io/academy
 * Description: Interactive D3.js and Three.js data visualization blocks for Masterworks Academy art investment research.
 * Version:     1.0.0
 * Author:      Masterworks Engineering
 * Author URI:  https://masterworks.io
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mw-data-visualizations
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'MW_DATAVIZ_VERSION', '1.0.0' );
define( 'MW_DATAVIZ_PATH', plugin_dir_path( __FILE__ ) );
define( 'MW_DATAVIZ_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
final class MW_Data_Visualizations {

    private static ?self $instance = null;

    /** CDN URLs */
    private const D3_CDN     = 'https://cdn.jsdelivr.net/npm/d3@7/dist/d3.min.js';
    private const THREE_CDN  = 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js';
    private const ORBIT_CDN  = 'https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/controls/OrbitControls.js';

    /** Block slugs that use D3 */
    private const D3_BLOCKS = [
        'mw/art-market-chart',
        'mw/artist-performance',
        'mw/segment-comparison',
    ];

    /** Block slugs that use Three.js */
    private const THREE_BLOCKS = [
        'mw/globe-visualization',
    ];

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
        add_filter( 'render_block', [ $this, 'maybe_enqueue_frontend_libs' ], 10, 2 );
    }

    /**
     * Register all Gutenberg blocks.
     */
    public function register_blocks(): void {
        $blocks = [
            'art-market-chart',
            'artist-performance',
            'segment-comparison',
            'globe-visualization',
        ];

        foreach ( $blocks as $block ) {
            $block_dir = MW_DATAVIZ_PATH . "blocks/{$block}";

            register_block_type( $block_dir, [
                'render_callback' => function ( array $attributes, string $content ) use ( $block ) {
                    $file = MW_DATAVIZ_PATH . "blocks/{$block}/render.php";
                    if ( ! file_exists( $file ) ) {
                        return '';
                    }
                    ob_start();
                    include $file;
                    return ob_get_clean();
                },
            ] );
        }
    }

    /**
     * Enqueue shared editor assets (CSS).
     */
    public function enqueue_editor_assets(): void {
        wp_enqueue_style(
            'mw-dataviz-blocks',
            MW_DATAVIZ_URL . 'assets/css/blocks.css',
            [],
            MW_DATAVIZ_VERSION
        );
    }

    /**
     * Conditionally enqueue D3 / Three.js on the frontend only when
     * the relevant block is present in rendered output.
     */
    public function maybe_enqueue_frontend_libs( string $block_content, array $block ): string {
        if ( is_admin() ) {
            return $block_content;
        }

        $block_name = $block['blockName'] ?? '';

        // D3.js blocks
        if ( in_array( $block_name, self::D3_BLOCKS, true ) ) {
            $this->enqueue_d3();
            $this->enqueue_block_view_script( $block_name );
        }

        // Three.js blocks
        if ( in_array( $block_name, self::THREE_BLOCKS, true ) ) {
            $this->enqueue_three();
            $this->enqueue_block_view_script( $block_name );
        }

        // Always enqueue shared CSS when any of our blocks appear
        if ( in_array( $block_name, array_merge( self::D3_BLOCKS, self::THREE_BLOCKS ), true ) ) {
            wp_enqueue_style(
                'mw-dataviz-blocks',
                MW_DATAVIZ_URL . 'assets/css/blocks.css',
                [],
                MW_DATAVIZ_VERSION
            );
        }

        return $block_content;
    }

    /**
     * Enqueue D3.js v7 from CDN.
     */
    private function enqueue_d3(): void {
        if ( wp_script_is( 'mw-d3', 'enqueued' ) ) {
            return;
        }
        wp_enqueue_script( 'mw-d3', self::D3_CDN, [], '7.0.0', true );

        // Also enqueue the shared chart helpers
        wp_enqueue_script(
            'mw-chart-helpers',
            MW_DATAVIZ_URL . 'src/utils/chart-helpers.js',
            [ 'mw-d3' ],
            MW_DATAVIZ_VERSION,
            true
        );

        wp_localize_script( 'mw-chart-helpers', 'mwDataViz', [
            'restUrl' => rest_url( 'mw-academy/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    /**
     * Enqueue Three.js from CDN as an ES module.
     */
    private function enqueue_three(): void {
        if ( wp_script_is( 'mw-three', 'enqueued' ) ) {
            return;
        }

        // Register Three.js as a module script via wp_enqueue_script_module if available (WP 6.5+)
        // Fallback: register as regular script with module type added via filter.
        wp_enqueue_script( 'mw-three', self::THREE_CDN, [], '0.160.0', true );
        add_filter( 'script_loader_tag', function ( $tag, $handle ) {
            if ( 'mw-three' === $handle && false === strpos( $tag, 'type="module"' ) ) {
                $tag = str_replace( '<script ', '<script type="module" ', $tag );
            }
            return $tag;
        }, 10, 2 );
    }

    /**
     * Enqueue a block's frontend view.js.
     */
    private function enqueue_block_view_script( string $block_name ): void {
        $slug   = str_replace( 'mw/', '', $block_name );
        $handle = "mw-dataviz-{$slug}-view";

        if ( wp_script_is( $handle, 'enqueued' ) ) {
            return;
        }

        $deps = in_array( $block_name, self::THREE_BLOCKS, true )
            ? [ 'mw-three' ]
            : [ 'mw-d3', 'mw-chart-helpers' ];

        wp_enqueue_script(
            $handle,
            MW_DATAVIZ_URL . "blocks/{$slug}/view.js",
            $deps,
            MW_DATAVIZ_VERSION,
            true
        );

        // Add type="module" for globe block
        if ( in_array( $block_name, self::THREE_BLOCKS, true ) ) {
            add_filter( 'script_loader_tag', function ( $tag, $handle_check ) use ( $handle ) {
                if ( $handle_check === $handle && false === strpos( $tag, 'type="module"' ) ) {
                    $tag = str_replace( '<script ', '<script type="module" ', $tag );
                }
                return $tag;
            }, 10, 2 );
        }
    }

    /**
     * Register REST API routes for chart data.
     */
    public function register_rest_routes(): void {
        register_rest_route( 'mw-academy/v1', '/indices', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_indices' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'source' => [
                    'type'    => 'string',
                    'default' => 'art-market-index',
                    'enum'    => [ 'art-market-index', 'segment-index', 'custom' ],
                ],
                'period' => [
                    'type'    => 'string',
                    'default' => '5y',
                    'enum'    => [ '1y', '3y', '5y', '10y', 'all' ],
                ],
                'segments' => [
                    'type'    => 'string',
                    'default' => '',
                ],
                'benchmarks' => [
                    'type'    => 'string',
                    'default' => '',
                ],
            ],
        ] );

        register_rest_route( 'mw-academy/v1', '/artists/(?P<id>\d+)/performance', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_artist_performance' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'validate_callback' => function ( $param ) {
                        return is_numeric( $param ) && (int) $param > 0;
                    },
                ],
                'metric' => [
                    'type'    => 'string',
                    'default' => 'price-trajectory',
                ],
                'period' => [
                    'type'    => 'string',
                    'default' => '5y',
                ],
                'comparisons' => [
                    'type'    => 'string',
                    'default' => '',
                ],
            ],
        ] );

        register_rest_route( 'mw-academy/v1', '/segments/comparison', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_segment_comparison' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'segments'   => [ 'type' => 'string', 'default' => '' ],
                'benchmarks' => [ 'type' => 'string', 'default' => '' ],
                'metric'     => [ 'type' => 'string', 'default' => 'total-return' ],
                'period'     => [ 'type' => 'string', 'default' => '5y' ],
            ],
        ] );

        register_rest_route( 'mw-academy/v1', '/globe', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_globe_data' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'metric' => [ 'type' => 'string', 'default' => 'auction-volume' ],
                'year'   => [ 'type' => 'integer', 'default' => (int) date( 'Y' ) ],
            ],
        ] );
    }

    /**
     * REST: Return art market index data.
     *
     * In production this would query a real data source.
     * Provides realistic sample data for development.
     */
    public function rest_get_indices( \WP_REST_Request $request ): \WP_REST_Response {
        $period   = $request->get_param( 'period' );
        $source   = $request->get_param( 'source' );
        $segments = array_filter( explode( ',', $request->get_param( 'segments' ) ) );

        $years = match ( $period ) {
            '1y'  => 1,
            '3y'  => 3,
            '5y'  => 5,
            '10y' => 10,
            'all' => 20,
            default => 5,
        };

        $data = $this->generate_index_data( $years, $segments );

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'source' => $source,
                'period' => $period,
                'generated' => current_time( 'c' ),
            ],
        ] );
    }

    /**
     * REST: Return artist performance data.
     */
    public function rest_get_artist_performance( \WP_REST_Request $request ): \WP_REST_Response {
        $artist_id   = (int) $request->get_param( 'id' );
        $metric      = $request->get_param( 'metric' );
        $period      = $request->get_param( 'period' );
        $comparisons = array_filter( explode( ',', $request->get_param( 'comparisons' ) ) );

        $years = match ( $period ) {
            '5y'  => 5,
            '10y' => 10,
            'all' => 20,
            default => 5,
        };

        $data = $this->generate_artist_data( $artist_id, $metric, $years, $comparisons );

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ] );
    }

    /**
     * REST: Return segment comparison data.
     */
    public function rest_get_segment_comparison( \WP_REST_Request $request ): \WP_REST_Response {
        $segments   = array_filter( explode( ',', $request->get_param( 'segments' ) ) );
        $benchmarks = array_filter( explode( ',', $request->get_param( 'benchmarks' ) ) );
        $metric     = $request->get_param( 'metric' );
        $period     = $request->get_param( 'period' );

        if ( empty( $segments ) ) {
            $segments = [ 'contemporary', 'impressionist', 'post-war', 'modern', 'old-masters' ];
        }

        $data = $this->generate_segment_comparison( $segments, $benchmarks, $metric );

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ] );
    }

    /**
     * REST: Return globe visualization data.
     */
    public function rest_get_globe_data( \WP_REST_Request $request ): \WP_REST_Response {
        $metric = $request->get_param( 'metric' );
        $year   = (int) $request->get_param( 'year' );

        $data = $this->generate_globe_data( $metric, $year );

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => $data,
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Sample Data Generators (replace with real data sources in prod)   */
    /* ------------------------------------------------------------------ */

    private function generate_index_data( int $years, array $segments ): array {
        $series = [];
        $now    = time();
        $points = $years * 12; // monthly data points

        $default_segments = [
            'art-market' => [ 'label' => 'Art Market Index', 'base' => 100, 'growth' => 0.008 ],
            'contemporary' => [ 'label' => 'Contemporary Art', 'base' => 100, 'growth' => 0.012 ],
            'impressionist' => [ 'label' => 'Impressionist', 'base' => 100, 'growth' => 0.006 ],
            'post-war' => [ 'label' => 'Post-War', 'base' => 100, 'growth' => 0.009 ],
        ];

        $benchmarks = [
            'sp500'       => [ 'label' => 'S&P 500', 'base' => 100, 'growth' => 0.007 ],
            'real-estate' => [ 'label' => 'Real Estate', 'base' => 100, 'growth' => 0.004 ],
            'gold'        => [ 'label' => 'Gold', 'base' => 100, 'growth' => 0.003 ],
        ];

        $active = empty( $segments ) ? $default_segments : array_intersect_key( $default_segments, array_flip( $segments ) );
        if ( empty( $active ) ) {
            $active = $default_segments;
        }

        foreach ( array_merge( $active, $benchmarks ) as $key => $config ) {
            $values = [];
            $value  = $config['base'];
            for ( $i = $points; $i >= 0; $i-- ) {
                $date  = date( 'Y-m-d', strtotime( "-{$i} months", $now ) );
                $value = $value * ( 1 + $config['growth'] + ( mt_rand( -30, 30 ) / 1000 ) );
                $values[] = [
                    'date'  => $date,
                    'value' => round( $value, 2 ),
                ];
            }
            $series[ $key ] = [
                'label'  => $config['label'],
                'values' => $values,
                'type'   => isset( $benchmarks[ $key ] ) ? 'benchmark' : 'primary',
            ];
        }

        return $series;
    }

    private function generate_artist_data( int $artist_id, string $metric, int $years, array $comparisons ): array {
        $now    = time();
        $points = $years * 4; // quarterly

        $artists = [
            $artist_id => 'Primary Artist',
        ];
        foreach ( $comparisons as $cid ) {
            $artists[ (int) $cid ] = "Comparison Artist #{$cid}";
        }

        $result = [];
        foreach ( $artists as $id => $name ) {
            $values = [];
            $value  = 100;
            for ( $i = $points; $i >= 0; $i-- ) {
                $date  = date( 'Y-m-d', strtotime( "-{$i} quarters", $now ) );
                $value = $value * ( 1 + 0.01 + ( mt_rand( -40, 50 ) / 1000 ) );
                $values[] = [
                    'date'  => $date,
                    'value' => round( $value, 2 ),
                    'upper' => round( $value * 1.15, 2 ),
                    'lower' => round( $value * 0.85, 2 ),
                    'volume' => mt_rand( 5, 80 ),
                ];
            }
            $result[] = [
                'artistId'   => $id,
                'artistName' => $name,
                'values'     => $values,
            ];
        }

        return $result;
    }

    private function generate_segment_comparison( array $segments, array $benchmarks, string $metric ): array {
        $items = [];

        $labels = [
            'contemporary'  => 'Contemporary',
            'impressionist' => 'Impressionist',
            'post-war'      => 'Post-War',
            'modern'        => 'Modern',
            'old-masters'   => 'Old Masters',
            'sp500'         => 'S&P 500',
            'real-estate'   => 'Real Estate',
            'gold'          => 'Gold',
            'bonds'         => 'Bonds',
            'crypto'        => 'Crypto',
        ];

        foreach ( array_merge( $segments, $benchmarks ) as $key ) {
            $items[] = [
                'key'          => $key,
                'label'        => $labels[ $key ] ?? ucfirst( $key ),
                'totalReturn'  => round( mt_rand( 20, 180 ) / 10, 1 ),
                'volatility'   => round( mt_rand( 5, 35 ) / 10, 1 ),
                'sharpeRatio'  => round( mt_rand( 3, 25 ) / 10, 2 ),
                'type'         => in_array( $key, $benchmarks, true ) ? 'benchmark' : 'segment',
            ];
        }

        return $items;
    }

    private function generate_globe_data( string $metric, int $year ): array {
        $regions = [
            [ 'id' => 'USA', 'name' => 'United States', 'lat' => 39.8, 'lng' => -98.5 ],
            [ 'id' => 'GBR', 'name' => 'United Kingdom', 'lat' => 55.3, 'lng' => -3.4 ],
            [ 'id' => 'CHN', 'name' => 'China', 'lat' => 35.8, 'lng' => 104.1 ],
            [ 'id' => 'FRA', 'name' => 'France', 'lat' => 46.2, 'lng' => 2.2 ],
            [ 'id' => 'DEU', 'name' => 'Germany', 'lat' => 51.1, 'lng' => 10.4 ],
            [ 'id' => 'CHE', 'name' => 'Switzerland', 'lat' => 46.8, 'lng' => 8.2 ],
            [ 'id' => 'JPN', 'name' => 'Japan', 'lat' => 36.2, 'lng' => 138.2 ],
            [ 'id' => 'ARE', 'name' => 'UAE', 'lat' => 23.4, 'lng' => 53.8 ],
            [ 'id' => 'HKG', 'name' => 'Hong Kong', 'lat' => 22.3, 'lng' => 114.1 ],
            [ 'id' => 'ITA', 'name' => 'Italy', 'lat' => 41.8, 'lng' => 12.5 ],
            [ 'id' => 'AUS', 'name' => 'Australia', 'lat' => -25.2, 'lng' => 133.7 ],
            [ 'id' => 'BRA', 'name' => 'Brazil', 'lat' => -14.2, 'lng' => -51.9 ],
        ];

        foreach ( $regions as &$region ) {
            $region['value'] = mt_rand( 10, 1000 );
        }
        unset( $region );

        return [
            'metric'  => $metric,
            'year'    => $year,
            'regions' => $regions,
        ];
    }
}

MW_Data_Visualizations::instance();
