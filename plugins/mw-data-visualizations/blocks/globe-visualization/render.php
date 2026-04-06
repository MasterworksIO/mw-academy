<?php
/**
 * Server-side render for the Globe Visualization block.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Block inner content.
 */

defined( 'ABSPATH' ) || exit;

$metric            = esc_attr( $attributes['metric'] ?? 'auction-volume' );
$year              = absint( $attributes['year'] ?? date( 'Y' ) );
$auto_rotate       = ! empty( $attributes['autoRotate'] ) ? 'true' : 'false';
$highlight_regions = $attributes['highlightRegions'] ?? [];
$unique_id         = 'mw-globe-' . wp_unique_id();

$metric_labels = [
    'auction-volume'   => 'Auction Volume',
    'price-growth'     => 'Price Growth',
    'collector-density' => 'Collector Density',
];

$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'mw-globe-visualization mw-dataviz-block',
    'id'    => $unique_id,
] );
?>
<div
    <?php echo $wrapper_attributes; ?>
    data-metric="<?php echo $metric; ?>"
    data-year="<?php echo $year; ?>"
    data-auto-rotate="<?php echo $auto_rotate; ?>"
    data-highlight-regions="<?php echo esc_attr( implode( ',', $highlight_regions ) ); ?>"
    style="min-height: 500px; background: #0D0D1A;"
>
    <div class="mw-dataviz-block__header mw-dataviz-block__header--dark">
        <h3 class="mw-dataviz-block__title">
            Global Art Market &mdash; <?php echo esc_html( $metric_labels[ $metric ] ?? $metric ); ?> (<?php echo $year; ?>)
        </h3>
    </div>
    <div class="mw-dataviz-block__canvas" aria-label="3D globe showing global art market data"></div>
    <div class="mw-dataviz-block__legend mw-dataviz-block__legend--dark"></div>
    <div class="mw-dataviz-block__loading mw-dataviz-block__loading--dark">
        <div class="mw-dataviz-spinner mw-dataviz-spinner--light"></div>
        <span>Loading globe&hellip;</span>
    </div>
    <div class="mw-dataviz-block__error mw-dataviz-block__error--dark" style="display: none;">
        <p>Unable to load globe visualization. WebGL may not be supported by your browser.</p>
    </div>
    <noscript>
        <div class="mw-dataviz-block__fallback">
            <p>JavaScript is required for the interactive globe visualization.</p>
        </div>
    </noscript>
</div>
