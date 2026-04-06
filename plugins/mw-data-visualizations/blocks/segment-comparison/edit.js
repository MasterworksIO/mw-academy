( function ( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls, BlockControls } = wp.blockEditor;
    const {
        PanelBody,
        SelectControl,
        CheckboxControl,
        ToolbarGroup,
        ToolbarButton,
    } = wp.components;
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;

    const CHART_TYPES = [
        { label: 'Bar Chart', value: 'bar' },
        { label: 'Radar Chart', value: 'radar' },
        { label: 'Scatter Plot', value: 'scatter' },
    ];

    const METRICS = [
        { label: 'Total Return', value: 'total-return' },
        { label: 'Volatility', value: 'volatility' },
        { label: 'Sharpe Ratio', value: 'sharpe-ratio' },
    ];

    const PERIODS = [
        { label: '1 Year', value: '1y' },
        { label: '3 Years', value: '3y' },
        { label: '5 Years', value: '5y' },
        { label: '10 Years', value: '10y' },
        { label: 'All Time', value: 'all' },
    ];

    const SEGMENTS = [
        { key: 'contemporary', label: 'Contemporary Art' },
        { key: 'impressionist', label: 'Impressionist' },
        { key: 'post-war', label: 'Post-War' },
        { key: 'modern', label: 'Modern' },
        { key: 'old-masters', label: 'Old Masters' },
    ];

    const BENCHMARKS = [
        { key: 'sp500', label: 'S&P 500' },
        { key: 'real-estate', label: 'Real Estate' },
        { key: 'gold', label: 'Gold' },
        { key: 'bonds', label: 'Bonds' },
        { key: 'crypto', label: 'Crypto' },
    ];

    const CHART_ICONS = {
        bar: 'chart-bar',
        radar: 'chart-pie',
        scatter: 'marker',
    };

    registerBlockType( 'mw/segment-comparison', {
        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const { segments, benchmarks, metric, period, chartType } = attributes;

            const blockProps = useBlockProps( {
                className: 'mw-segment-comparison-editor',
            } );

            function toggleItem( list, key, attr ) {
                var next = list.includes( key )
                    ? list.filter( function ( s ) { return s !== key; } )
                    : list.concat( [ key ] );
                var obj = {};
                obj[ attr ] = next;
                setAttributes( obj );
            }

            function renderPreview() {
                var metricLabel = ( METRICS.find( function ( m ) { return m.value === metric; } ) || {} ).label || metric;

                return el( 'div', { className: 'mw-dataviz-editor-preview' },
                    el( 'div', { className: 'mw-dataviz-editor-preview__header' },
                        el( 'h3', null, 'Segment Comparison \u2014 ' + metricLabel ),
                        el( 'span', { className: 'mw-dataviz-editor-preview__badge' },
                            chartType.charAt( 0 ).toUpperCase() + chartType.slice( 1 ) + ' Chart'
                        )
                    ),
                    el( 'svg', {
                        viewBox: '0 0 400 200',
                        width: '100%',
                        height: 180,
                        className: 'mw-dataviz-editor-preview__svg',
                    },
                        chartType === 'radar'
                            ? el( 'g', { transform: 'translate(200, 100)' },
                                // Pentagon outline
                                el( 'polygon', {
                                    points: '0,-80 76,-25 47,65 -47,65 -76,-25',
                                    fill: 'none', stroke: '#e5e7eb', strokeWidth: 1,
                                } ),
                                el( 'polygon', {
                                    points: '0,-40 38,-12 24,32 -24,32 -38,-12',
                                    fill: 'none', stroke: '#e5e7eb', strokeWidth: 1,
                                } ),
                                el( 'polygon', {
                                    points: '0,-60 55,-18 35,50 -35,50 -55,-18',
                                    fill: '#6B2FA0', fillOpacity: 0.15, stroke: '#6B2FA0', strokeWidth: 2,
                                } ),
                                el( 'polygon', {
                                    points: '0,-45 42,-12 28,38 -28,38 -42,-12',
                                    fill: '#C9A227', fillOpacity: 0.1, stroke: '#C9A227', strokeWidth: 1.5,
                                    strokeDasharray: '4 2',
                                } )
                            )
                            : chartType === 'scatter'
                                ? el( 'g', null,
                                    el( 'line', { x1: 30, y1: 180, x2: 380, y2: 180, stroke: '#999', strokeWidth: 1 } ),
                                    el( 'line', { x1: 30, y1: 10, x2: 30, y2: 180, stroke: '#999', strokeWidth: 1 } ),
                                    el( 'text', { x: 200, y: 198, textAnchor: 'middle', fontSize: 10, fill: '#6b7280' }, 'Volatility' ),
                                    el( 'text', { x: 12, y: 100, textAnchor: 'middle', fontSize: 10, fill: '#6b7280', transform: 'rotate(-90, 12, 100)' }, 'Return' ),
                                    [ [80,50], [120,70], [160,40], [220,90], [280,60], [340,100] ].map( function ( p, i ) {
                                        return el( 'circle', {
                                            key: i, cx: p[0], cy: p[1], r: 8,
                                            fill: i < 3 ? '#6B2FA0' : '#C9A227', opacity: 0.7,
                                        } );
                                    } )
                                )
                                : el( 'g', null,
                                    el( 'line', { x1: 20, y1: 180, x2: 380, y2: 180, stroke: '#999', strokeWidth: 1 } ),
                                    el( 'line', { x1: 20, y1: 10, x2: 20, y2: 180, stroke: '#999', strokeWidth: 1 } ),
                                    [ 50, 100, 150, 200, 250, 310 ].map( function ( x, i ) {
                                        var h = 40 + Math.floor( Math.random() * 100 );
                                        return el( 'rect', {
                                            key: i, x: x, y: 180 - h, width: 35, height: h,
                                            fill: i < segments.length ? '#6B2FA0' : '#C9A227',
                                            rx: 2, opacity: 0.85,
                                        } );
                                    } )
                                )
                    ),
                    el( 'p', { className: 'mw-dataviz-editor-preview__note' },
                        segments.length + ' segments, ' + benchmarks.length + ' benchmarks selected'
                    )
                );
            }

            return el( Fragment, null,
                el( BlockControls, null,
                    el( ToolbarGroup, null,
                        CHART_TYPES.map( function ( ct ) {
                            return el( ToolbarButton, {
                                key: ct.value,
                                icon: CHART_ICONS[ ct.value ],
                                label: ct.label,
                                isPressed: chartType === ct.value,
                                onClick: function () { setAttributes( { chartType: ct.value } ); },
                            } );
                        } )
                    )
                ),
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Chart Settings', 'mw-data-visualizations' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Chart Type', 'mw-data-visualizations' ),
                            value: chartType,
                            options: CHART_TYPES,
                            onChange: function ( val ) { setAttributes( { chartType: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Metric', 'mw-data-visualizations' ),
                            value: metric,
                            options: METRICS,
                            onChange: function ( val ) { setAttributes( { metric: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Period', 'mw-data-visualizations' ),
                            value: period,
                            options: PERIODS,
                            onChange: function ( val ) { setAttributes( { period: val } ); },
                        } )
                    ),
                    el( PanelBody, { title: __( 'Art Segments', 'mw-data-visualizations' ), initialOpen: true },
                        SEGMENTS.map( function ( seg ) {
                            return el( CheckboxControl, {
                                key: seg.key,
                                label: seg.label,
                                checked: segments.includes( seg.key ),
                                onChange: function () { toggleItem( segments, seg.key, 'segments' ); },
                            } );
                        } )
                    ),
                    el( PanelBody, { title: __( 'Benchmarks', 'mw-data-visualizations' ), initialOpen: true },
                        BENCHMARKS.map( function ( bm ) {
                            return el( CheckboxControl, {
                                key: bm.key,
                                label: bm.label,
                                checked: benchmarks.includes( bm.key ),
                                onChange: function () { toggleItem( benchmarks, bm.key, 'benchmarks' ); },
                            } );
                        } )
                    )
                ),
                el( 'div', blockProps, renderPreview() )
            );
        },

        save: function () {
            return null;
        },
    } );
} )( window.wp );
