<?php
/**
 * Server-side render for the Segment Comparison block.
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Block inner content.
 */

defined( 'ABSPATH' ) || exit;

$segments   = $attributes['segments'] ?? [ 'contemporary', 'impressionist', 'post-war' ];
$benchmarks = $attributes['benchmarks'] ?? [ 'sp500' ];
$metric     = esc_attr( $attributes['metric'] ?? 'total-return' );
$period     = esc_attr( $attributes['period'] ?? '5y' );
$chart_type = esc_attr( $attributes['chartType'] ?? 'bar' );
$unique_id  = 'mw-sc-' . wp_unique_id();

$metric_labels = [
    'total-return'  => 'Total Return',
    'volatility'    => 'Volatility',
    'sharpe-ratio'  => 'Sharpe Ratio',
];

$wrapper_attributes = get_block_wrapper_attributes( [
    'class' => 'mw-segment-comparison mw-dataviz-block',
    'id'    => $unique_id,
] );
?>
<div
    <?php echo $wrapper_attributes; ?>
    data-segments="<?php echo esc_attr( implode( ',', $segments ) ); ?>"
    data-benchmarks="<?php echo esc_attr( implode( ',', $benchmarks ) ); ?>"
    data-metric="<?php echo $metric; ?>"
    data-period="<?php echo $period; ?>"
    data-chart-type="<?php echo $chart_type; ?>"
    style="min-height: 420px;"
>
    <div class="mw-dataviz-block__header">
        <h3 class="mw-dataviz-block__title">
            Segment Comparison &mdash; <?php echo esc_html( $metric_labels[ $metric ] ?? $metric ); ?>
        </h3>
    </div>
    <div class="mw-dataviz-block__chart" aria-label="Segment comparison chart"></div>
    <div class="mw-dataviz-block__legend"></div>
    <div class="mw-dataviz-block__loading">
        <div class="mw-dataviz-spinner"></div>
        <span>Loading comparison data&hellip;</span>
    </div>
    <div class="mw-dataviz-block__error" style="display: none;">
        <p>Unable to load comparison data.</p>
    </div>
    <div class="mw-dataviz-block__no-data" style="display: none;">
        <p>No comparison data available for the selected criteria.</p>
    </div>
</div>
