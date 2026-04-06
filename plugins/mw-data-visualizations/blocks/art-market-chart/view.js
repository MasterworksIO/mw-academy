/**
 * Art Market Chart - Frontend D3.js Visualization
 *
 * Renders interactive line, bar, and area charts for art market index data.
 */
( function () {
    'use strict';

    /* global d3, mwDataViz, mwChartHelpers */

    var helpers = window.mwChartHelpers || {};
    var brandColors = helpers.brandColors || [
        '#6B2FA0', '#C9A227', '#1A1A2E', '#8B5CF6', '#D4A843',
        '#2D2D5E', '#A855F7', '#E5C76B', '#4A4A8A',
    ];
    var benchmarkColors = {
        sp500: '#3B82F6',
        'real-estate': '#10B981',
        gold: '#F59E0B',
    };

    function init() {
        var containers = document.querySelectorAll( '.mw-art-market-chart' );
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
            chartType: container.dataset.chartType || 'line',
            source: container.dataset.source || 'art-market-index',
            period: container.dataset.period || '5y',
            segments: ( container.dataset.segments || '' ).split( ',' ).filter( Boolean ),
            showBenchmarks: container.dataset.showBenchmarks === 'true',
            title: container.dataset.title || 'Art Market Performance',
            height: parseInt( container.dataset.height, 10 ) || 400,
            colorScheme: container.dataset.colorScheme || 'brand',
        };

        var params = {
            source: config.source,
            period: config.period,
            segments: config.segments.join( ',' ),
            benchmarks: config.showBenchmarks ? 'sp500,real-estate,gold' : '',
        };

        helpers.fetchChartData( 'indices', params )
            .then( function ( response ) {
                hideLoading( container );
                if ( ! response || ! response.data || Object.keys( response.data ).length === 0 ) {
                    showNoData( container );
                    return;
                }
                renderChart( container, config, response.data );
                setupResize( container, config, response.data );
            } )
            .catch( function ( err ) {
                console.error( 'MW Art Market Chart error:', err );
                hideLoading( container );
                showError( container );
            } );
    }

    function renderChart( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 30, bottom: 50, left: 65 };
        var width = chartEl.clientWidth;
        var height = config.height;
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;

        if ( innerWidth <= 0 || innerHeight <= 0 ) return;

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height )
            .attr( 'role', 'img' )
            .attr( 'aria-label', config.title );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        // Parse all series
        var seriesKeys = Object.keys( data );
        var allDates = [];
        var allValues = [];

        seriesKeys.forEach( function ( key ) {
            var series = data[ key ];
            series.values.forEach( function ( d ) {
                var date = new Date( d.date );
                allDates.push( date );
                allValues.push( d.value );
            } );
        } );

        // Scales
        var xScale = d3.scaleTime()
            .domain( d3.extent( allDates ) )
            .range( [ 0, innerWidth ] );

        var yScale = d3.scaleLinear()
            .domain( [ d3.min( allValues ) * 0.95, d3.max( allValues ) * 1.05 ] )
            .nice()
            .range( [ innerHeight, 0 ] );

        // Gridlines
        g.append( 'g' )
            .attr( 'class', 'mw-grid' )
            .selectAll( 'line' )
            .data( yScale.ticks( 5 ) )
            .join( 'line' )
            .attr( 'x1', 0 )
            .attr( 'x2', innerWidth )
            .attr( 'y1', function ( d ) { return yScale( d ); } )
            .attr( 'y2', function ( d ) { return yScale( d ); } )
            .attr( 'stroke', '#e5e7eb' )
            .attr( 'stroke-dasharray', '2,2' );

        // X Axis
        g.append( 'g' )
            .attr( 'class', 'mw-axis mw-axis--x' )
            .attr( 'transform', 'translate(0,' + innerHeight + ')' )
            .call(
                d3.axisBottom( xScale )
                    .ticks( width < 500 ? 4 : 8 )
                    .tickFormat( d3.timeFormat( '%b %Y' ) )
            )
            .selectAll( 'text' )
            .attr( 'dy', '0.8em' )
            .attr( 'transform', 'rotate(-30)' )
            .style( 'text-anchor', 'end' )
            .style( 'font-size', '11px' )
            .style( 'fill', '#6b7280' );

        // Y Axis
        g.append( 'g' )
            .attr( 'class', 'mw-axis mw-axis--y' )
            .call(
                d3.axisLeft( yScale )
                    .ticks( 5 )
                    .tickFormat( function ( d ) { return helpers.formatCurrency( d ); } )
            )
            .selectAll( 'text' )
            .style( 'font-size', '11px' )
            .style( 'fill', '#6b7280' );

        // Tooltip
        var tooltip = helpers.createTooltip( container );

        // Draw series
        var colorIndex = 0;
        seriesKeys.forEach( function ( key ) {
            var series = data[ key ];
            var isBenchmark = series.type === 'benchmark';

            if ( isBenchmark && ! config.showBenchmarks ) return;

            var color = isBenchmark
                ? ( benchmarkColors[ key ] || brandColors[ colorIndex % brandColors.length ] )
                : brandColors[ colorIndex % brandColors.length ];

            var parsed = series.values.map( function ( d ) {
                return { date: new Date( d.date ), value: d.value };
            } );

            if ( config.chartType === 'line' || config.chartType === 'area' ) {
                var lineGen = d3.line()
                    .x( function ( d ) { return xScale( d.date ); } )
                    .y( function ( d ) { return yScale( d.value ); } )
                    .curve( d3.curveMonotoneX );

                if ( config.chartType === 'area' && ! isBenchmark ) {
                    var areaGen = d3.area()
                        .x( function ( d ) { return xScale( d.date ); } )
                        .y0( innerHeight )
                        .y1( function ( d ) { return yScale( d.value ); } )
                        .curve( d3.curveMonotoneX );

                    g.append( 'path' )
                        .datum( parsed )
                        .attr( 'fill', color )
                        .attr( 'fill-opacity', 0.1 )
                        .attr( 'd', areaGen )
                        .attr( 'clip-path', 'url(#clip)' );
                }

                var path = g.append( 'path' )
                    .datum( parsed )
                    .attr( 'fill', 'none' )
                    .attr( 'stroke', color )
                    .attr( 'stroke-width', isBenchmark ? 1.5 : 2.5 )
                    .attr( 'stroke-dasharray', isBenchmark ? '6 3' : 'none' )
                    .attr( 'd', lineGen );

                // Animate path drawing
                var totalLength = path.node().getTotalLength();
                path
                    .attr( 'stroke-dasharray', totalLength + ' ' + totalLength )
                    .attr( 'stroke-dashoffset', totalLength )
                    .transition()
                    .duration( 1500 )
                    .ease( d3.easeCubicOut )
                    .attr( 'stroke-dashoffset', 0 )
                    .on( 'end', function () {
                        if ( ! isBenchmark ) {
                            d3.select( this ).attr( 'stroke-dasharray', 'none' );
                        } else {
                            d3.select( this ).attr( 'stroke-dasharray', '6 3' );
                        }
                    } );
            }

            if ( config.chartType === 'bar' && ! isBenchmark ) {
                var barWidth = Math.max( 1, ( innerWidth / parsed.length ) * 0.7 );
                g.selectAll( '.bar-' + key )
                    .data( parsed )
                    .join( 'rect' )
                    .attr( 'class', 'bar-' + key )
                    .attr( 'x', function ( d ) { return xScale( d.date ) - barWidth / 2; } )
                    .attr( 'y', innerHeight )
                    .attr( 'width', barWidth )
                    .attr( 'height', 0 )
                    .attr( 'fill', color )
                    .attr( 'rx', 1 )
                    .transition()
                    .duration( 800 )
                    .delay( function ( d, i ) { return i * 10; } )
                    .attr( 'y', function ( d ) { return yScale( d.value ); } )
                    .attr( 'height', function ( d ) { return innerHeight - yScale( d.value ); } );
            }

            // Legend entry
            addLegendItem( legendEl, series.label, color, isBenchmark );
            if ( ! isBenchmark ) colorIndex++;
        } );

        // Hover overlay for tooltips
        var bisectDate = d3.bisector( function ( d ) { return d.date; } ).left;

        var overlay = g.append( 'rect' )
            .attr( 'width', innerWidth )
            .attr( 'height', innerHeight )
            .attr( 'fill', 'none' )
            .attr( 'pointer-events', 'all' );

        var focusLine = g.append( 'line' )
            .attr( 'class', 'mw-focus-line' )
            .attr( 'y1', 0 )
            .attr( 'y2', innerHeight )
            .style( 'stroke', '#9ca3af' )
            .style( 'stroke-width', 1 )
            .style( 'stroke-dasharray', '3,3' )
            .style( 'opacity', 0 );

        overlay
            .on( 'mousemove', function ( event ) {
                var coords = d3.pointer( event );
                var x0 = xScale.invert( coords[0] );
                focusLine.attr( 'x1', coords[0] ).attr( 'x2', coords[0] ).style( 'opacity', 1 );

                var lines = [];
                seriesKeys.forEach( function ( key ) {
                    var series = data[ key ];
                    if ( series.type === 'benchmark' && ! config.showBenchmarks ) return;
                    var parsed = series.values.map( function ( d ) {
                        return { date: new Date( d.date ), value: d.value };
                    } );
                    var idx = bisectDate( parsed, x0, 1 );
                    var d0 = parsed[ idx - 1 ];
                    var d1 = parsed[ idx ];
                    if ( ! d0 ) return;
                    var d = ( d1 && x0 - d0.date > d1.date - x0 ) ? d1 : d0;
                    lines.push( '<strong>' + series.label + ':</strong> ' + helpers.formatCurrency( d.value ) );
                } );

                var dateStr = helpers.formatDate( x0, 'month' );
                tooltip.innerHTML = '<div class="mw-tooltip__date">' + dateStr + '</div>' + lines.join( '<br>' );
                tooltip.style.display = 'block';

                var rect = container.getBoundingClientRect();
                tooltip.style.left = ( event.clientX - rect.left + 15 ) + 'px';
                tooltip.style.top = ( event.clientY - rect.top - 10 ) + 'px';
            } )
            .on( 'mouseleave', function () {
                focusLine.style( 'opacity', 0 );
                tooltip.style.display = 'none';
            } );
    }

    function addLegendItem( legendEl, label, color, isDashed ) {
        var item = document.createElement( 'div' );
        item.className = 'mw-dataviz-legend__item';
        var swatch = document.createElement( 'span' );
        swatch.className = 'mw-dataviz-legend__swatch';
        swatch.style.backgroundColor = color;
        if ( isDashed ) {
            swatch.style.backgroundImage = 'repeating-linear-gradient(90deg, ' + color + ' 0, ' + color + ' 4px, transparent 4px, transparent 7px)';
            swatch.style.backgroundColor = 'transparent';
        }
        var text = document.createElement( 'span' );
        text.className = 'mw-dataviz-legend__label';
        text.textContent = label;
        item.appendChild( swatch );
        item.appendChild( text );
        legendEl.appendChild( item );
    }

    function setupResize( container, config, data ) {
        var resizeTimer;
        var observer = new ResizeObserver( function () {
            clearTimeout( resizeTimer );
            resizeTimer = setTimeout( function () {
                renderChart( container, config, data );
            }, 250 );
        } );
        observer.observe( container );
    }

    function hideLoading( container ) {
        var el = container.querySelector( '.mw-dataviz-block__loading' );
        if ( el ) el.style.display = 'none';
    }

    function showError( container ) {
        var el = container.querySelector( '.mw-dataviz-block__error' );
        if ( el ) el.style.display = 'flex';
    }

    function showNoData( container ) {
        var el = container.querySelector( '.mw-dataviz-block__no-data' );
        if ( el ) el.style.display = 'flex';
    }

    // Initialize when DOM is ready
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();
