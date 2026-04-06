( function ( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls, BlockControls } = wp.blockEditor;
    const {
        PanelBody,
        SelectControl,
        ToggleControl,
        TextControl,
        RangeControl,
        ToolbarGroup,
        ToolbarButton,
        CheckboxControl,
    } = wp.components;
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;

    const CHART_TYPES = [
        { label: 'Line Chart', value: 'line' },
        { label: 'Bar Chart', value: 'bar' },
        { label: 'Area Chart', value: 'area' },
    ];

    const DATA_SOURCES = [
        { label: 'Art Market Index', value: 'art-market-index' },
        { label: 'Segment Index', value: 'segment-index' },
        { label: 'Custom Data', value: 'custom' },
    ];

    const PERIODS = [
        { label: '1 Year', value: '1y' },
        { label: '3 Years', value: '3y' },
        { label: '5 Years', value: '5y' },
        { label: '10 Years', value: '10y' },
        { label: 'All Time', value: 'all' },
    ];

    const COLOR_SCHEMES = [
        { label: 'Brand', value: 'brand' },
        { label: 'Sequential', value: 'sequential' },
        { label: 'Diverging', value: 'diverging' },
    ];

    const SEGMENTS = [
        { key: 'contemporary', label: 'Contemporary Art' },
        { key: 'impressionist', label: 'Impressionist' },
        { key: 'post-war', label: 'Post-War' },
        { key: 'modern', label: 'Modern' },
        { key: 'old-masters', label: 'Old Masters' },
    ];

    const CHART_TYPE_ICONS = {
        line: 'chart-line',
        bar: 'chart-bar',
        area: 'chart-area',
    };

    registerBlockType( 'mw/art-market-chart', {
        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const {
                chartType,
                dataSource,
                period,
                segments,
                showBenchmarks,
                title,
                height,
                colorScheme,
            } = attributes;

            const blockProps = useBlockProps( {
                className: 'mw-art-market-chart-editor',
            } );

            function toggleSegment( key ) {
                const next = segments.includes( key )
                    ? segments.filter( function ( s ) { return s !== key; } )
                    : segments.concat( [ key ] );
                setAttributes( { segments: next } );
            }

            // Static SVG preview
            function renderPreview() {
                var pathD = '';
                if ( chartType === 'line' || chartType === 'area' ) {
                    pathD = 'M 20 140 C 60 130, 80 100, 120 90 S 200 60, 240 55 S 320 40, 360 30 L 380 25';
                }

                return el( 'div', { className: 'mw-dataviz-editor-preview' },
                    el( 'div', { className: 'mw-dataviz-editor-preview__header' },
                        el( 'h3', null, title || __( 'Art Market Performance', 'mw-data-visualizations' ) ),
                        el( 'span', { className: 'mw-dataviz-editor-preview__badge' },
                            chartType.charAt( 0 ).toUpperCase() + chartType.slice( 1 ) + ' Chart'
                        )
                    ),
                    el( 'svg', {
                        viewBox: '0 0 400 160',
                        width: '100%',
                        height: Math.min( height, 200 ),
                        className: 'mw-dataviz-editor-preview__svg',
                    },
                        // Grid lines
                        el( 'line', { x1: 20, y1: 30, x2: 380, y2: 30, stroke: '#e0e0e0', strokeWidth: 0.5 } ),
                        el( 'line', { x1: 20, y1: 70, x2: 380, y2: 70, stroke: '#e0e0e0', strokeWidth: 0.5 } ),
                        el( 'line', { x1: 20, y1: 110, x2: 380, y2: 110, stroke: '#e0e0e0', strokeWidth: 0.5 } ),
                        // Axes
                        el( 'line', { x1: 20, y1: 10, x2: 20, y2: 150, stroke: '#999', strokeWidth: 1 } ),
                        el( 'line', { x1: 20, y1: 150, x2: 390, y2: 150, stroke: '#999', strokeWidth: 1 } ),
                        // Chart content
                        chartType === 'bar'
                            ? el( 'g', null,
                                el( 'rect', { x: 40, y: 80, width: 30, height: 70, fill: '#6B2FA0', rx: 2 } ),
                                el( 'rect', { x: 90, y: 60, width: 30, height: 90, fill: '#6B2FA0', rx: 2 } ),
                                el( 'rect', { x: 140, y: 50, width: 30, height: 100, fill: '#6B2FA0', rx: 2 } ),
                                el( 'rect', { x: 190, y: 40, width: 30, height: 110, fill: '#C9A227', rx: 2 } ),
                                el( 'rect', { x: 240, y: 45, width: 30, height: 105, fill: '#C9A227', rx: 2 } ),
                                el( 'rect', { x: 290, y: 30, width: 30, height: 120, fill: '#C9A227', rx: 2 } ),
                                el( 'rect', { x: 340, y: 25, width: 30, height: 125, fill: '#1A1A2E', rx: 2 } )
                            )
                            : el( 'g', null,
                                chartType === 'area'
                                    ? el( 'path', {
                                        d: pathD + ' L 380 150 L 20 150 Z',
                                        fill: '#6B2FA0',
                                        opacity: 0.15,
                                    } )
                                    : null,
                                el( 'path', {
                                    d: pathD,
                                    fill: 'none',
                                    stroke: '#6B2FA0',
                                    strokeWidth: 2.5,
                                } ),
                                showBenchmarks
                                    ? el( 'path', {
                                        d: 'M 20 135 C 80 125, 120 110, 180 100 S 280 85, 380 70',
                                        fill: 'none',
                                        stroke: '#C9A227',
                                        strokeWidth: 1.5,
                                        strokeDasharray: '6 3',
                                    } )
                                    : null
                            )
                    ),
                    el( 'p', { className: 'mw-dataviz-editor-preview__note' },
                        __( 'Interactive chart renders on the frontend.', 'mw-data-visualizations' )
                    )
                );
            }

            return el( Fragment, null,
                el( BlockControls, null,
                    el( ToolbarGroup, null,
                        CHART_TYPES.map( function ( ct ) {
                            return el( ToolbarButton, {
                                key: ct.value,
                                icon: CHART_TYPE_ICONS[ ct.value ],
                                label: ct.label,
                                isPressed: chartType === ct.value,
                                onClick: function () {
                                    setAttributes( { chartType: ct.value } );
                                },
                            } );
                        } )
                    )
                ),
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Chart Settings', 'mw-data-visualizations' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'Title', 'mw-data-visualizations' ),
                            value: title,
                            onChange: function ( val ) { setAttributes( { title: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Chart Type', 'mw-data-visualizations' ),
                            value: chartType,
                            options: CHART_TYPES,
                            onChange: function ( val ) { setAttributes( { chartType: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Data Source', 'mw-data-visualizations' ),
                            value: dataSource,
                            options: DATA_SOURCES,
                            onChange: function ( val ) { setAttributes( { dataSource: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Time Period', 'mw-data-visualizations' ),
                            value: period,
                            options: PERIODS,
                            onChange: function ( val ) { setAttributes( { period: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Color Scheme', 'mw-data-visualizations' ),
                            value: colorScheme,
                            options: COLOR_SCHEMES,
                            onChange: function ( val ) { setAttributes( { colorScheme: val } ); },
                        } ),
                        el( RangeControl, {
                            label: __( 'Chart Height (px)', 'mw-data-visualizations' ),
                            value: height,
                            onChange: function ( val ) { setAttributes( { height: val } ); },
                            min: 200,
                            max: 800,
                            step: 50,
                        } )
                    ),
                    el( PanelBody, { title: __( 'Segments', 'mw-data-visualizations' ), initialOpen: false },
                        SEGMENTS.map( function ( seg ) {
                            return el( CheckboxControl, {
                                key: seg.key,
                                label: seg.label,
                                checked: segments.includes( seg.key ),
                                onChange: function () { toggleSegment( seg.key ); },
                            } );
                        } )
                    ),
                    el( PanelBody, { title: __( 'Benchmarks', 'mw-data-visualizations' ), initialOpen: false },
                        el( ToggleControl, {
                            label: __( 'Show Benchmarks (S&P 500, Real Estate, Gold)', 'mw-data-visualizations' ),
                            checked: showBenchmarks,
                            onChange: function ( val ) { setAttributes( { showBenchmarks: val } ); },
                        } )
                    )
                ),
                el( 'div', blockProps, renderPreview() )
            );
        },

        save: function () {
            // Dynamic block rendered via PHP
            return null;
        },
    } );
} )( window.wp );
