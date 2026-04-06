<?php
/**
 * Server-side render for the Art Market Chart block.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Block inner content (empty for dynamic blocks).
 */

defined( 'ABSPATH' ) || exit;

$chart_type      = esc_attr( $attributes['chartType'] ?? 'line' );
$data_source     = esc_attr( $attributes['dataSource'] ?? 'art-market-index' );
$period          = esc_attr( $attributes['period'] ?? '5y' );
$segments        = $attributes['segments'] ?? [];
$show_benchmarks = ! empty( $attributes['showBenchmarks'] ) ? 'true' : 'false';
$title           = esc_html( $attributes['title'] ?? 'Art Market Performance' );
$height          = absint( $attributes['height'] ?? 400 );
$color_scheme    = esc_attr( $attributes['colorScheme'] ?? 'brand' );
$unique_id       = 'mw-amc-' . wp_unique_id();

$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'mw-art-market-chart mw-dataviz-block',
    'id'    => $unique_id,
] );
?>
<div
    <?php echo $wrapper_attributes; ?>
    data-chart-type="<?php echo $chart_type; ?>"
    data-source="<?php echo $data_source; ?>"
    data-period="<?php echo $period; ?>"
    data-segments="<?php echo esc_attr( implode( ',', $segments ) ); ?>"
    data-show-benchmarks="<?php echo $show_benchmarks; ?>"
    data-title="<?php echo esc_attr( $title ); ?>"
    data-height="<?php echo $height; ?>"
    data-color-scheme="<?php echo $color_scheme; ?>"
    style="min-height: <?php echo $height; ?>px;"
>
    <div class="mw-dataviz-block__header">
        <h3 class="mw-dataviz-block__title"><?php echo $title; ?></h3>
    </div>
    <div class="mw-dataviz-block__chart" aria-label="<?php echo esc_attr( $title ); ?>"></div>
    <div class="mw-dataviz-block__legend"></div>
    <div class="mw-dataviz-block__loading">
        <div class="mw-dataviz-spinner"></div>
        <span>Loading chart data&hellip;</span>
    </div>
    <div class="mw-dataviz-block__error" style="display: none;">
        <p>Unable to load chart data. Please try again later.</p>
    </div>
    <div class="mw-dataviz-block__no-data" style="display: none;">
        <p>No data available for the selected criteria.</p>
    </div>
</div>
