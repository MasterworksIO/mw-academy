<?php
/**
 * Server-side render for the Artist Performance block.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Block inner content.
 */

defined( 'ABSPATH' ) || exit;

$artist_id          = absint( $attributes['artistId'] ?? 0 );
$artist_name        = esc_html( $attributes['artistName'] ?? '' );
$metric             = esc_attr( $attributes['metric'] ?? 'price-trajectory' );
$period             = esc_attr( $attributes['period'] ?? '5y' );
$show_comparisons   = ! empty( $attributes['showComparisons'] ) ? 'true' : 'false';
$comparison_artists = $attributes['comparisonArtists'] ?? [];
$unique_id          = 'mw-ap-' . wp_unique_id();

if ( ! $artist_id ) {
    echo '<div class="mw-dataviz-block mw-dataviz-block--empty"><p>No artist selected.</p></div>';
    return;
}

$metric_labels = [
    'price-trajectory'   => 'Price Trajectory',
    'auction-volume'     => 'Auction Volume',
    'return-comparison'  => 'Return Comparison',
];

$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'mw-artist-performance mw-dataviz-block',
    'id'    => $unique_id,
] );
?>
<div
    <?php echo $wrapper_attributes; ?>
    data-artist-id="<?php echo $artist_id; ?>"
    data-artist-name="<?php echo esc_attr( $artist_name ); ?>"
    data-metric="<?php echo $metric; ?>"
    data-period="<?php echo $period; ?>"
    data-show-comparisons="<?php echo $show_comparisons; ?>"
    data-comparison-artists="<?php echo esc_attr( implode( ',', array_map( 'absint', $comparison_artists ) ) ); ?>"
    style="min-height: 400px;"
>
    <div class="mw-dataviz-block__header">
        <h3 class="mw-dataviz-block__title">
            <?php echo $artist_name; ?> &mdash; <?php echo esc_html( $metric_labels[ $metric ] ?? $metric ); ?>
        </h3>
    </div>
    <div class="mw-dataviz-block__chart" aria-label="<?php echo esc_attr( $artist_name . ' performance chart' ); ?>"></div>
    <div class="mw-dataviz-block__legend"></div>
    <div class="mw-dataviz-block__loading">
        <div class="mw-dataviz-spinner"></div>
        <span>Loading artist data&hellip;</span>
    </div>
    <div class="mw-dataviz-block__error" style="display: none;">
        <p>Unable to load artist performance data.</p>
    </div>
    <div class="mw-dataviz-block__no-data" style="display: none;">
        <p>No performance data available for this artist.</p>
    </div>
</div>
