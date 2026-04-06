/**
 * Segment Comparison - Frontend D3.js Visualization
 *
 * Grouped bar chart, radar chart, and scatter plot for comparing
 * art segments against financial benchmarks.
 */
( function () {
    'use strict';

    /* global d3, mwDataViz, mwChartHelpers */

    var helpers = window.mwChartHelpers || {};
    var brandColors = helpers.brandColors || [
        '#6B2FA0', '#C9A227', '#1A1A2E', '#8B5CF6', '#D4A843',
        '#2D2D5E', '#A855F7', '#E5C76B', '#4A4A8A',
    ];
    var benchmarkColor = '#3B82F6';

    function init() {
        var containers = document.querySelectorAll( '.mw-segment-comparison' );
        containers.forEach( function ( container ) {
            if ( container.dataset.initialized ) return;
            container.dataset.initialized = 'true';
            helpers.animateOnScroll( container, function () {
                loadAndRender( container );
            } );
        } );
    }

    function loadAndRender( container ) {
        var config = {
            segments: ( container.dataset.segments || '' ).split( ',' ).filter( Boolean ),
            benchmarks: ( container.dataset.benchmarks || '' ).split( ',' ).filter( Boolean ),
            metric: container.dataset.metric || 'total-return',
            period: container.dataset.period || '5y',
            chartType: container.dataset.chartType || 'bar',
        };

        var params = {
            segments: config.segments.join( ',' ),
            benchmarks: config.benchmarks.join( ',' ),
            metric: config.metric,
            period: config.period,
        };

        helpers.fetchChartData( 'segments/comparison', params )
            .then( function ( response ) {
                hideLoading( container );
                if ( ! response || ! response.data || response.data.length === 0 ) {
                    showNoData( container );
                    return;
                }
                renderChart( container, config, response.data );
                setupResize( container, config, response.data );
            } )
            .catch( function ( err ) {
                console.error( 'MW Segment Comparison error:', err );
                hideLoading( container );
                showError( container );
            } );
    }

    function renderChart( container, config, data ) {
        switch ( config.chartType ) {
            case 'bar':
                renderGroupedBar( container, config, data );
                break;
            case 'radar':
                renderRadar( container, config, data );
                break;
            case 'scatter':
                renderScatter( container, config, data );
                break;
        }
    }

    /**
     * Grouped Bar Chart for return comparison.
     */
    function renderGroupedBar( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 20, bottom: 70, left: 55 };
        var width = chartEl.clientWidth;
        var height = 420;
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;

        if ( innerWidth <= 0 ) return;

        var metricKey = config.metric === 'total-return' ? 'totalReturn'
            : config.metric === 'volatility' ? 'volatility' : 'sharpeRatio';
        var metricUnit = config.metric === 'sharpe-ratio' ? '' : '%';

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        var xScale = d3.scaleBand()
            .domain( data.map( function ( d ) { return d.label; } ) )
            .range( [ 0, innerWidth ] )
            .padding( 0.25 );

        var maxVal = d3.max( data, function ( d ) { return d[ metricKey ]; } );
        var yScale = d3.scaleLinear()
            .domain( [ 0, maxVal * 1.2 ] )
            .nice()
            .range( [ innerHeight, 0 ] );

        // Gridlines
        g.append( 'g' ).selectAll( 'line' )
            .data( yScale.ticks( 5 ) )
            .join( 'line' )
            .attr( 'x1', 0 ).attr( 'x2', innerWidth )
            .attr( 'y1', function ( d ) { return yScale( d ); } )
            .attr( 'y2', function ( d ) { return yScale( d ); } )
            .attr( 'stroke', '#e5e7eb' ).attr( 'stroke-dasharray', '2,2' );

        // Axes
        g.append( 'g' )
            .attr( 'transform', 'translate(0,' + innerHeight + ')' )
            .call( d3.axisBottom( xScale ) )
            .selectAll( 'text' )
            .attr( 'transform', 'rotate(-35)' ).style( 'text-anchor', 'end' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        g.append( 'g' )
            .call( d3.axisLeft( yScale ).ticks( 5 ).tickFormat( function ( d ) { return d + metricUnit; } ) )
            .selectAll( 'text' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        var tooltip = helpers.createTooltip( container );

        g.selectAll( '.seg-bar' )
            .data( data )
            .join( 'rect' )
            .attr( 'class', 'seg-bar' )
            .attr( 'x', function ( d ) { return xScale( d.label ); } )
            .attr( 'y', innerHeight )
            .attr( 'width', xScale.bandwidth() )
            .attr( 'height', 0 )
            .attr( 'fill', function ( d, i ) {
                return d.type === 'benchmark' ? benchmarkColor : brandColors[ i % brandColors.length ];
            } )
            .attr( 'rx', 3 )
            .on( 'mouseover', function ( event, d ) {
                d3.select( this ).attr( 'opacity', 0.8 );
                tooltip.innerHTML = '<strong>' + d.label + '</strong><br>' +
                    d[ metricKey ] + metricUnit;
                tooltip.style.display = 'block';
            } )
            .on( 'mousemove', function ( event ) {
                var rect = container.getBoundingClientRect();
                tooltip.style.left = ( event.clientX - rect.left + 15 ) + 'px';
                tooltip.style.top = ( event.clientY - rect.top - 10 ) + 'px';
            } )
            .on( 'mouseout', function () {
                d3.select( this ).attr( 'opacity', 1 );
                tooltip.style.display = 'none';
            } )
            .transition()
            .duration( 800 )
            .delay( function ( d, i ) { return i * 100; } )
            .attr( 'y', function ( d ) { return yScale( d[ metricKey ] ); } )
            .attr( 'height', function ( d ) { return innerHeight - yScale( d[ metricKey ] ); } );

        // Value labels
        g.selectAll( '.val-label' )
            .data( data )
            .join( 'text' )
            .attr( 'x', function ( d ) { return xScale( d.label ) + xScale.bandwidth() / 2; } )
            .attr( 'y', function ( d ) { return yScale( d[ metricKey ] ) - 6; } )
            .attr( 'text-anchor', 'middle' )
            .style( 'font-size', '11px' ).style( 'font-weight', '600' ).style( 'fill', '#374151' )
            .style( 'opacity', 0 )
            .text( function ( d ) { return d[ metricKey ] + metricUnit; } )
            .transition().delay( 1000 ).duration( 400 ).style( 'opacity', 1 );

        buildLegend( legendEl, data );
    }

    /**
     * Radar Chart for multi-metric comparison.
     */
    function renderRadar( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var width = chartEl.clientWidth;
        var height = 420;
        var radius = Math.min( width, height ) / 2 - 60;
        var cx = width / 2;
        var cy = height / 2;

        if ( radius <= 0 ) return;

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + cx + ',' + cy + ')' );

        // Axes are the three metrics
        var axes = [ 'totalReturn', 'volatility', 'sharpeRatio' ];
        var axisLabels = [ 'Total Return (%)', 'Volatility (%)', 'Sharpe Ratio' ];
        var angleSlice = ( 2 * Math.PI ) / axes.length;

        // Max values for each axis
        var maxValues = axes.map( function ( axis ) {
            return d3.max( data, function ( d ) { return d[ axis ]; } ) * 1.2;
        } );

        var rScale = d3.scaleLinear().domain( [ 0, 1 ] ).range( [ 0, radius ] );

        // Grid levels
        var levels = 4;
        for ( var lvl = 1; lvl <= levels; lvl++ ) {
            var levelFactor = lvl / levels;
            var points = axes.map( function ( _, i ) {
                return [
                    rScale( levelFactor ) * Math.cos( angleSlice * i - Math.PI / 2 ),
                    rScale( levelFactor ) * Math.sin( angleSlice * i - Math.PI / 2 ),
                ].join( ',' );
            } ).join( ' ' );

            g.append( 'polygon' )
                .attr( 'points', points )
                .attr( 'fill', 'none' )
                .attr( 'stroke', '#e5e7eb' )
                .attr( 'stroke-width', 0.5 );
        }

        // Axis lines and labels
        axes.forEach( function ( _, i ) {
            var x = radius * Math.cos( angleSlice * i - Math.PI / 2 );
            var y = radius * Math.sin( angleSlice * i - Math.PI / 2 );

            g.append( 'line' )
                .attr( 'x1', 0 ).attr( 'y1', 0 )
                .attr( 'x2', x ).attr( 'y2', y )
                .attr( 'stroke', '#d1d5db' ).attr( 'stroke-width', 1 );

            g.append( 'text' )
                .attr( 'x', x * 1.18 )
                .attr( 'y', y * 1.18 )
                .attr( 'text-anchor', 'middle' )
                .attr( 'dominant-baseline', 'middle' )
                .style( 'font-size', '11px' )
                .style( 'fill', '#6b7280' )
                .text( axisLabels[ i ] );
        } );

        var tooltip = helpers.createTooltip( container );

        // Data polygons
        data.forEach( function ( item, idx ) {
            var color = item.type === 'benchmark' ? benchmarkColor : brandColors[ idx % brandColors.length ];

            var points = axes.map( function ( axis, i ) {
                var normalized = item[ axis ] / maxValues[ i ];
                return [
                    rScale( normalized ) * Math.cos( angleSlice * i - Math.PI / 2 ),
                    rScale( normalized ) * Math.sin( angleSlice * i - Math.PI / 2 ),
                ].join( ',' );
            } ).join( ' ' );

            g.append( 'polygon' )
                .attr( 'points', axes.map( function () { return '0,0'; } ).join( ' ' ) )
                .attr( 'fill', color )
                .attr( 'fill-opacity', 0.12 )
                .attr( 'stroke', color )
                .attr( 'stroke-width', 2 )
                .on( 'mouseover', function ( event ) {
                    d3.select( this ).attr( 'fill-opacity', 0.25 );
                    tooltip.innerHTML = '<strong>' + item.label + '</strong><br>' +
                        'Return: ' + item.totalReturn + '%<br>' +
                        'Volatility: ' + item.volatility + '%<br>' +
                        'Sharpe: ' + item.sharpeRatio;
                    tooltip.style.display = 'block';
                } )
                .on( 'mousemove', function ( event ) {
                    var rect = container.getBoundingClientRect();
                    tooltip.style.left = ( event.clientX - rect.left + 15 ) + 'px';
                    tooltip.style.top = ( event.clientY - rect.top - 10 ) + 'px';
                } )
                .on( 'mouseout', function () {
                    d3.select( this ).attr( 'fill-opacity', 0.12 );
                    tooltip.style.display = 'none';
                } )
                .transition()
                .duration( 1000 )
                .delay( idx * 150 )
                .attr( 'points', points );

            // Data points
            axes.forEach( function ( axis, i ) {
                var normalized = item[ axis ] / maxValues[ i ];
                var px = rScale( normalized ) * Math.cos( angleSlice * i - Math.PI / 2 );
                var py = rScale( normalized ) * Math.sin( angleSlice * i - Math.PI / 2 );

                g.append( 'circle' )
                    .attr( 'cx', 0 ).attr( 'cy', 0 )
                    .attr( 'r', 4 )
                    .attr( 'fill', color )
                    .attr( 'stroke', '#fff' )
                    .attr( 'stroke-width', 1.5 )
                    .transition()
                    .duration( 1000 )
                    .delay( idx * 150 )
                    .attr( 'cx', px )
                    .attr( 'cy', py );
            } );
        } );

        buildLegend( legendEl, data );
    }

    /**
     * Scatter Plot for risk/return analysis.
     */
    function renderScatter( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 30, bottom: 60, left: 60 };
        var width = chartEl.clientWidth;
        var height = 420;
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;

        if ( innerWidth <= 0 ) return;

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        var xScale = d3.scaleLinear()
            .domain( [ 0, d3.max( data, function ( d ) { return d.volatility; } ) * 1.3 ] )
            .nice()
            .range( [ 0, innerWidth ] );

        var yScale = d3.scaleLinear()
            .domain( [ 0, d3.max( data, function ( d ) { return d.totalReturn; } ) * 1.3 ] )
            .nice()
            .range( [ innerHeight, 0 ] );

        // Gridlines
        g.append( 'g' ).selectAll( 'line.h' )
            .data( yScale.ticks( 5 ) )
            .join( 'line' )
            .attr( 'x1', 0 ).attr( 'x2', innerWidth )
            .attr( 'y1', function ( d ) { return yScale( d ); } )
            .attr( 'y2', function ( d ) { return yScale( d ); } )
            .attr( 'stroke', '#e5e7eb' ).attr( 'stroke-dasharray', '2,2' );

        g.append( 'g' ).selectAll( 'line.v' )
            .data( xScale.ticks( 5 ) )
            .join( 'line' )
            .attr( 'x1', function ( d ) { return xScale( d ); } )
            .attr( 'x2', function ( d ) { return xScale( d ); } )
            .attr( 'y1', 0 ).attr( 'y2', innerHeight )
            .attr( 'stroke', '#e5e7eb' ).attr( 'stroke-dasharray', '2,2' );

        // Axes
        g.append( 'g' )
            .attr( 'transform', 'translate(0,' + innerHeight + ')' )
            .call( d3.axisBottom( xScale ).ticks( 5 ).tickFormat( function ( d ) { return d + '%'; } ) )
            .selectAll( 'text' ).style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        g.append( 'g' )
            .call( d3.axisLeft( yScale ).ticks( 5 ).tickFormat( function ( d ) { return d + '%'; } ) )
            .selectAll( 'text' ).style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        // Axis labels
        svg.append( 'text' )
            .attr( 'x', margin.left + innerWidth / 2 )
            .attr( 'y', height - 8 )
            .attr( 'text-anchor', 'middle' )
            .style( 'font-size', '12px' ).style( 'fill', '#6b7280' )
            .text( 'Volatility (%)' );

        svg.append( 'text' )
            .attr( 'x', 14 )
            .attr( 'y', margin.top + innerHeight / 2 )
            .attr( 'text-anchor', 'middle' )
            .attr( 'transform', 'rotate(-90, 14, ' + ( margin.top + innerHeight / 2 ) + ')' )
            .style( 'font-size', '12px' ).style( 'fill', '#6b7280' )
            .text( 'Total Return (%)' );

        var tooltip = helpers.createTooltip( container );

        // Dots
        g.selectAll( 'circle.dot' )
            .data( data )
            .join( 'circle' )
            .attr( 'class', 'dot' )
            .attr( 'cx', function ( d ) { return xScale( d.volatility ); } )
            .attr( 'cy', innerHeight )
            .attr( 'r', 0 )
            .attr( 'fill', function ( d, i ) {
                return d.type === 'benchmark' ? benchmarkColor : brandColors[ i % brandColors.length ];
            } )
            .attr( 'stroke', '#fff' )
            .attr( 'stroke-width', 2 )
            .style( 'cursor', 'pointer' )
            .on( 'mouseover', function ( event, d ) {
                d3.select( this ).transition().duration( 200 ).attr( 'r', 12 );
                tooltip.innerHTML = '<strong>' + d.label + '</strong><br>' +
                    'Return: ' + d.totalReturn + '%<br>' +
                    'Volatility: ' + d.volatility + '%<br>' +
                    'Sharpe: ' + d.sharpeRatio;
                tooltip.style.display = 'block';
            } )
            .on( 'mousemove', function ( event ) {
                var rect = container.getBoundingClientRect();
                tooltip.style.left = ( event.clientX - rect.left + 15 ) + 'px';
                tooltip.style.top = ( event.clientY - rect.top - 10 ) + 'px';
            } )
            .on( 'mouseout', function () {
                d3.select( this ).transition().duration( 200 ).attr( 'r', 8 );
                tooltip.style.display = 'none';
            } )
            .transition()
            .duration( 800 )
            .delay( function ( d, i ) { return i * 100; } )
            .attr( 'cy', function ( d ) { return yScale( d.totalReturn ); } )
            .attr( 'r', 8 );

        // Labels
        g.selectAll( 'text.dot-label' )
            .data( data )
            .join( 'text' )
            .attr( 'class', 'dot-label' )
            .attr( 'x', function ( d ) { return xScale( d.volatility ) + 12; } )
            .attr( 'y', function ( d ) { return yScale( d.totalReturn ) + 4; } )
            .style( 'font-size', '10px' ).style( 'fill', '#6b7280' )
            .style( 'opacity', 0 )
            .text( function ( d ) { return d.label; } )
            .transition().delay( 1200 ).duration( 400 ).style( 'opacity', 1 );

        buildLegend( legendEl, data );
    }

    /* ------------------------------------------------------------------ */
    /*  Shared helpers                                                     */
    /* ------------------------------------------------------------------ */

    function buildLegend( legendEl, data ) {
        data.forEach( function ( item, idx ) {
            var color = item.type === 'benchmark' ? benchmarkColor : brandColors[ idx % brandColors.length ];
            var el = document.createElement( 'div' );
            el.className = 'mw-dataviz-legend__item';
            var swatch = document.createElement( 'span' );
            swatch.className = 'mw-dataviz-legend__swatch';
            swatch.style.backgroundColor = color;
            var text = document.createElement( 'span' );
            text.className = 'mw-dataviz-legend__label';
            text.textContent = item.label;
            el.appendChild( swatch );
            el.appendChild( text );
            legendEl.appendChild( el );
        } );
    }

    function setupResize( container, config, data ) {
        var timer;
        var observer = new ResizeObserver( function () {
            clearTimeout( timer );
            timer = setTimeout( function () { renderChart( container, config, data ); }, 250 );
        } );
        observer.observe( container );
    }

    function hideLoading( c ) {
        var el = c.querySelector( '.mw-dataviz-block__loading' );
        if ( el ) el.style.display = 'none';
    }
    function showError( c ) {
        var el = c.querySelector( '.mw-dataviz-block__error' );
        if ( el ) el.style.display = 'flex';
    }
    function showNoData( c ) {
        var el = c.querySelector( '.mw-dataviz-block__no-data' );
        if ( el ) el.style.display = 'flex';
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();
