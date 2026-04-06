( function ( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const {
        PanelBody,
        SelectControl,
        ToggleControl,
        TextControl,
        FormTokenField,
        Placeholder,
        Spinner,
    } = wp.components;
    const { Fragment, createElement: el, useState } = wp.element;
    const { __ } = wp.i18n;

    const METRICS = [
        { label: 'Price Trajectory', value: 'price-trajectory' },
        { label: 'Auction Volume', value: 'auction-volume' },
        { label: 'Return Comparison', value: 'return-comparison' },
    ];

    const PERIODS = [
        { label: '5 Years', value: '5y' },
        { label: '10 Years', value: '10y' },
        { label: 'All Time', value: 'all' },
    ];

    const METRIC_ICONS = {
        'price-trajectory': 'chart-line',
        'auction-volume': 'chart-bar',
        'return-comparison': 'chart-area',
    };

    registerBlockType( 'mw/artist-performance', {
        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const {
                artistId,
                artistName,
                metric,
                period,
                showComparisons,
                comparisonArtists,
            } = attributes;

            const blockProps = useBlockProps( {
                className: 'mw-artist-performance-editor',
            } );

            // Artist search state
            const [ artistSearch, setArtistSearch ] = useState( '' );
            const [ compSearch, setCompSearch ] = useState( '' );

            function renderMetricDescription( m ) {
                var descriptions = {
                    'price-trajectory': 'Line chart showing price index over time with confidence bands.',
                    'auction-volume': 'Bar chart showing auction lot counts with trend line overlay.',
                    'return-comparison': 'Grouped bar chart comparing annualized returns across artists.',
                };
                return descriptions[ m ] || '';
            }

            function renderPreview() {
                if ( ! artistId || ! artistName ) {
                    return el( Placeholder, {
                        icon: 'chart-area',
                        label: __( 'Artist Performance', 'mw-data-visualizations' ),
                        instructions: __( 'Enter an Artist ID and name to configure this visualization.', 'mw-data-visualizations' ),
                    } );
                }

                return el( 'div', { className: 'mw-dataviz-editor-preview' },
                    el( 'div', { className: 'mw-dataviz-editor-preview__header' },
                        el( 'h3', null, artistName + ' — ' + (
                            METRICS.find( function ( m ) { return m.value === metric; } ) || {}
                        ).label ),
                        el( 'span', { className: 'mw-dataviz-editor-preview__badge' },
                            metric.replace( /-/g, ' ' ).replace( /\b\w/g, function ( l ) { return l.toUpperCase(); } )
                        )
                    ),
                    el( 'svg', {
                        viewBox: '0 0 400 160',
                        width: '100%',
                        height: 180,
                        className: 'mw-dataviz-editor-preview__svg',
                    },
                        el( 'line', { x1: 20, y1: 150, x2: 380, y2: 150, stroke: '#999', strokeWidth: 1 } ),
                        el( 'line', { x1: 20, y1: 10, x2: 20, y2: 150, stroke: '#999', strokeWidth: 1 } ),
                        metric === 'auction-volume'
                            ? el( 'g', null,
                                [ 50, 90, 130, 170, 210, 250, 290, 330 ].map( function ( x, i ) {
                                    var h = 30 + Math.random() * 90;
                                    return el( 'rect', {
                                        key: i, x: x, y: 150 - h, width: 25, height: h,
                                        fill: '#6B2FA0', rx: 2, opacity: 0.8,
                                    } );
                                } )
                            )
                            : el( 'g', null,
                                el( 'path', {
                                    d: 'M 20 120 C 60 115, 100 90, 140 85 S 220 60, 280 50 S 340 35, 380 30',
                                    fill: 'none', stroke: '#6B2FA0', strokeWidth: 2.5,
                                } ),
                                metric === 'price-trajectory'
                                    ? el( 'g', null,
                                        el( 'path', {
                                            d: 'M 20 105 C 60 100, 100 75, 140 70 S 220 45, 280 35 S 340 20, 380 15 L 380 45 S 340 50, 280 65 S 220 75, 140 100 C 100 105, 60 130, 20 135 Z',
                                            fill: '#6B2FA0', opacity: 0.08,
                                        } )
                                    )
                                    : null,
                                showComparisons
                                    ? el( 'path', {
                                        d: 'M 20 130 C 80 120, 140 100, 200 95 S 300 75, 380 60',
                                        fill: 'none', stroke: '#C9A227', strokeWidth: 1.5, strokeDasharray: '5 3',
                                    } )
                                    : null
                            )
                    ),
                    el( 'p', { className: 'mw-dataviz-editor-preview__note' },
                        renderMetricDescription( metric )
                    )
                );
            }

            return el( Fragment, null,
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Artist', 'mw-data-visualizations' ), initialOpen: true },
                        el( TextControl, {
                            label: __( 'Artist ID', 'mw-data-visualizations' ),
                            type: 'number',
                            value: artistId || '',
                            onChange: function ( val ) {
                                setAttributes( { artistId: parseInt( val, 10 ) || 0 } );
                            },
                            help: __( 'Enter the artist ID from the database.', 'mw-data-visualizations' ),
                        } ),
                        el( TextControl, {
                            label: __( 'Artist Name', 'mw-data-visualizations' ),
                            value: artistName,
                            onChange: function ( val ) {
                                setAttributes( { artistName: val } );
                            },
                            placeholder: __( 'e.g., Banksy', 'mw-data-visualizations' ),
                        } )
                    ),
                    el( PanelBody, { title: __( 'Metric', 'mw-data-visualizations' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Performance Metric', 'mw-data-visualizations' ),
                            value: metric,
                            options: METRICS,
                            onChange: function ( val ) { setAttributes( { metric: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Time Period', 'mw-data-visualizations' ),
                            value: period,
                            options: PERIODS,
                            onChange: function ( val ) { setAttributes( { period: val } ); },
                        } )
                    ),
                    el( PanelBody, { title: __( 'Comparisons', 'mw-data-visualizations' ), initialOpen: false },
                        el( ToggleControl, {
                            label: __( 'Show Comparison Artists', 'mw-data-visualizations' ),
                            checked: showComparisons,
                            onChange: function ( val ) { setAttributes( { showComparisons: val } ); },
                        } ),
                        showComparisons
                            ? el( TextControl, {
                                label: __( 'Comparison Artist IDs (comma-separated)', 'mw-data-visualizations' ),
                                value: ( comparisonArtists || [] ).join( ', ' ),
                                onChange: function ( val ) {
                                    var ids = val.split( ',' )
                                        .map( function ( s ) { return parseInt( s.trim(), 10 ); } )
                                        .filter( function ( n ) { return ! isNaN( n ) && n > 0; } );
                                    setAttributes( { comparisonArtists: ids } );
                                },
                                help: __( 'Enter artist IDs separated by commas, e.g., 12, 34, 56', 'mw-data-visualizations' ),
                            } )
                            : null
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
