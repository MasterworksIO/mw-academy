( function ( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const {
        PanelBody,
        SelectControl,
        ToggleControl,
        RangeControl,
        CheckboxControl,
    } = wp.components;
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;

    const METRICS = [
        { label: 'Auction Volume', value: 'auction-volume' },
        { label: 'Price Growth', value: 'price-growth' },
        { label: 'Collector Density', value: 'collector-density' },
    ];

    const REGIONS = [
        { key: 'USA', label: 'United States' },
        { key: 'GBR', label: 'United Kingdom' },
        { key: 'CHN', label: 'China' },
        { key: 'FRA', label: 'France' },
        { key: 'DEU', label: 'Germany' },
        { key: 'CHE', label: 'Switzerland' },
        { key: 'JPN', label: 'Japan' },
        { key: 'ARE', label: 'UAE' },
        { key: 'HKG', label: 'Hong Kong' },
        { key: 'ITA', label: 'Italy' },
    ];

    registerBlockType( 'mw/globe-visualization', {
        edit: function ( props ) {
            const { attributes, setAttributes } = props;
            const { metric, year, autoRotate, highlightRegions } = attributes;

            const blockProps = useBlockProps( {
                className: 'mw-globe-visualization-editor',
            } );

            function toggleRegion( key ) {
                var next = highlightRegions.includes( key )
                    ? highlightRegions.filter( function ( r ) { return r !== key; } )
                    : highlightRegions.concat( [ key ] );
                setAttributes( { highlightRegions: next } );
            }

            var metricLabel = ( METRICS.find( function ( m ) { return m.value === metric; } ) || {} ).label || metric;

            function renderPreview() {
                return el( 'div', { className: 'mw-dataviz-editor-preview mw-dataviz-editor-preview--dark' },
                    el( 'div', { className: 'mw-dataviz-editor-preview__header' },
                        el( 'h3', null, 'Global Art Market \u2014 ' + metricLabel + ' (' + year + ')' ),
                        el( 'span', { className: 'mw-dataviz-editor-preview__badge mw-dataviz-editor-preview__badge--3d' }, '3D Globe' )
                    ),
                    el( 'svg', {
                        viewBox: '0 0 300 300',
                        width: '100%',
                        height: 260,
                        className: 'mw-dataviz-editor-preview__svg',
                    },
                        // Globe circle
                        el( 'circle', { cx: 150, cy: 150, r: 110, fill: '#1A1A2E', stroke: '#6B2FA0', strokeWidth: 2 } ),
                        // Grid lines on globe
                        el( 'ellipse', { cx: 150, cy: 150, rx: 110, ry: 30, fill: 'none', stroke: '#2D2D5E', strokeWidth: 0.5 } ),
                        el( 'ellipse', { cx: 150, cy: 150, rx: 110, ry: 60, fill: 'none', stroke: '#2D2D5E', strokeWidth: 0.5 } ),
                        el( 'ellipse', { cx: 150, cy: 150, rx: 110, ry: 90, fill: 'none', stroke: '#2D2D5E', strokeWidth: 0.5 } ),
                        el( 'ellipse', { cx: 150, cy: 150, rx: 30, ry: 110, fill: 'none', stroke: '#2D2D5E', strokeWidth: 0.5 } ),
                        el( 'ellipse', { cx: 150, cy: 150, rx: 70, ry: 110, fill: 'none', stroke: '#2D2D5E', strokeWidth: 0.5 } ),
                        // Hot spots
                        el( 'circle', { cx: 100, cy: 110, r: 8, fill: '#C9A227', opacity: 0.8 } ),
                        el( 'circle', { cx: 160, cy: 95, r: 12, fill: '#C9A227', opacity: 0.9 } ),
                        el( 'circle', { cx: 200, cy: 120, r: 6, fill: '#C9A227', opacity: 0.7 } ),
                        el( 'circle', { cx: 130, cy: 140, r: 5, fill: '#6B2FA0', opacity: 0.7 } ),
                        el( 'circle', { cx: 175, cy: 160, r: 10, fill: '#6B2FA0', opacity: 0.8 } ),
                        // Glow effect
                        el( 'circle', { cx: 160, cy: 95, r: 18, fill: '#C9A227', opacity: 0.15 } ),
                        el( 'circle', { cx: 175, cy: 160, r: 15, fill: '#6B2FA0', opacity: 0.12 } )
                    ),
                    el( 'p', { className: 'mw-dataviz-editor-preview__note' },
                        __( 'Interactive 3D globe renders on the frontend via Three.js. Requires WebGL.', 'mw-data-visualizations' )
                    )
                );
            }

            return el( Fragment, null,
                el( InspectorControls, null,
                    el( PanelBody, { title: __( 'Globe Settings', 'mw-data-visualizations' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Metric', 'mw-data-visualizations' ),
                            value: metric,
                            options: METRICS,
                            onChange: function ( val ) { setAttributes( { metric: val } ); },
                        } ),
                        el( RangeControl, {
                            label: __( 'Year', 'mw-data-visualizations' ),
                            value: year,
                            onChange: function ( val ) { setAttributes( { year: val } ); },
                            min: 2000,
                            max: 2026,
                            step: 1,
                        } ),
                        el( ToggleControl, {
                            label: __( 'Auto-Rotate', 'mw-data-visualizations' ),
                            checked: autoRotate,
                            onChange: function ( val ) { setAttributes( { autoRotate: val } ); },
                        } )
                    ),
                    el( PanelBody, { title: __( 'Highlight Regions', 'mw-data-visualizations' ), initialOpen: false },
                        REGIONS.map( function ( region ) {
                            return el( CheckboxControl, {
                                key: region.key,
                                label: region.label,
                                checked: highlightRegions.includes( region.key ),
                                onChange: function () { toggleRegion( region.key ); },
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
