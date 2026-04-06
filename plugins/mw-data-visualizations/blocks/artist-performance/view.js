/**
 * Artist Performance - Frontend D3.js Visualization
 *
 * Renders price trajectory (with confidence bands), auction volume (bars + trend),
 * and return comparison (grouped bars) charts.
 */
( function () {
    'use strict';

    /* global d3, mwDataViz, mwChartHelpers */

    var helpers = window.mwChartHelpers || {};
    var brandColors = helpers.brandColors || [
        '#6B2FA0', '#C9A227', '#1A1A2E', '#8B5CF6', '#D4A843',
        '#2D2D5E', '#A855F7', '#E5C76B', '#4A4A8A',
    ];

    function init() {
        var containers = document.querySelectorAll( '.mw-artist-performance' );
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
            artistId: parseInt( container.dataset.artistId, 10 ),
            artistName: container.dataset.artistName || 'Artist',
            metric: container.dataset.metric || 'price-trajectory',
            period: container.dataset.period || '5y',
            showComparisons: container.dataset.showComparisons === 'true',
            comparisonArtists: ( container.dataset.comparisonArtists || '' )
                .split( ',' ).filter( Boolean ).map( Number ),
        };

        if ( ! config.artistId ) {
            hideLoading( container );
            showNoData( container );
            return;
        }

        var params = {
            metric: config.metric,
            period: config.period,
            comparisons: config.showComparisons ? config.comparisonArtists.join( ',' ) : '',
        };

        helpers.fetchChartData( 'artists/' + config.artistId + '/performance', params )
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
                console.error( 'MW Artist Performance error:', err );
                hideLoading( container );
                showError( container );
            } );
    }

    function renderChart( container, config, data ) {
        switch ( config.metric ) {
            case 'price-trajectory':
                renderPriceTrajectory( container, config, data );
                break;
            case 'auction-volume':
                renderAuctionVolume( container, config, data );
                break;
            case 'return-comparison':
                renderReturnComparison( container, config, data );
                break;
        }
    }

    /**
     * Price Trajectory: line chart with confidence bands
     */
    function renderPriceTrajectory( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 30, bottom: 50, left: 65 };
        var width = chartEl.clientWidth;
        var height = 400;
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

        var allDates = [];
        var allValues = [];
        data.forEach( function ( series ) {
            series.values.forEach( function ( d ) {
                allDates.push( new Date( d.date ) );
                allValues.push( d.value );
                allValues.push( d.upper );
                allValues.push( d.lower );
            } );
        } );

        var xScale = d3.scaleTime()
            .domain( d3.extent( allDates ) )
            .range( [ 0, innerWidth ] );

        var yScale = d3.scaleLinear()
            .domain( [ d3.min( allValues ) * 0.9, d3.max( allValues ) * 1.1 ] )
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
            .call( d3.axisBottom( xScale ).ticks( 6 ).tickFormat( d3.timeFormat( '%b %Y' ) ) )
            .selectAll( 'text' )
            .attr( 'transform', 'rotate(-30)' ).style( 'text-anchor', 'end' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        g.append( 'g' )
            .call( d3.axisLeft( yScale ).ticks( 5 ).tickFormat( helpers.formatCurrency ) )
            .selectAll( 'text' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        var tooltip = helpers.createTooltip( container );

        data.forEach( function ( series, idx ) {
            var color = brandColors[ idx % brandColors.length ];
            var parsed = series.values.map( function ( d ) {
                return {
                    date: new Date( d.date ),
                    value: d.value,
                    upper: d.upper,
                    lower: d.lower,
                };
            } );

            // Confidence band (primary artist only)
            if ( idx === 0 ) {
                var area = d3.area()
                    .x( function ( d ) { return xScale( d.date ); } )
                    .y0( function ( d ) { return yScale( d.lower ); } )
                    .y1( function ( d ) { return yScale( d.upper ); } )
                    .curve( d3.curveMonotoneX );

                g.append( 'path' )
                    .datum( parsed )
                    .attr( 'fill', color )
                    .attr( 'fill-opacity', 0.1 )
                    .attr( 'd', area );
            }

            // Line
            var lineGen = d3.line()
                .x( function ( d ) { return xScale( d.date ); } )
                .y( function ( d ) { return yScale( d.value ); } )
                .curve( d3.curveMonotoneX );

            var path = g.append( 'path' )
                .datum( parsed )
                .attr( 'fill', 'none' )
                .attr( 'stroke', color )
                .attr( 'stroke-width', idx === 0 ? 2.5 : 1.8 )
                .attr( 'stroke-dasharray', idx > 0 ? '6 3' : 'none' )
                .attr( 'd', lineGen );

            // Animate
            var len = path.node().getTotalLength();
            path.attr( 'stroke-dasharray', len + ' ' + len )
                .attr( 'stroke-dashoffset', len )
                .transition().duration( 1500 ).ease( d3.easeCubicOut )
                .attr( 'stroke-dashoffset', 0 )
                .on( 'end', function () {
                    d3.select( this ).attr( 'stroke-dasharray', idx > 0 ? '6 3' : 'none' );
                } );

            addLegendItem( legendEl, series.artistName, color, idx > 0 );
        } );

        // Tooltip overlay
        addTooltipOverlay( g, innerWidth, innerHeight, xScale, data, tooltip, container );
    }

    /**
     * Auction Volume: bar chart with trend line
     */
    function renderAuctionVolume( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 30, bottom: 50, left: 55 };
        var width = chartEl.clientWidth;
        var height = 400;
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;

        if ( innerWidth <= 0 ) return;

        var primaryData = data[0];
        if ( ! primaryData ) return;

        var parsed = primaryData.values.map( function ( d ) {
            return { date: new Date( d.date ), volume: d.volume, value: d.value };
        } );

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        var xScale = d3.scaleBand()
            .domain( parsed.map( function ( d ) { return d.date.toISOString(); } ) )
            .range( [ 0, innerWidth ] )
            .padding( 0.2 );

        var yScale = d3.scaleLinear()
            .domain( [ 0, d3.max( parsed, function ( d ) { return d.volume; } ) * 1.15 ] )
            .nice()
            .range( [ innerHeight, 0 ] );

        // Axes
        g.append( 'g' )
            .attr( 'transform', 'translate(0,' + innerHeight + ')' )
            .call(
                d3.axisBottom( xScale )
                    .tickValues( xScale.domain().filter( function ( d, i ) {
                        return i % Math.max( 1, Math.floor( parsed.length / 8 ) ) === 0;
                    } ) )
                    .tickFormat( function ( d ) { return d3.timeFormat( '%b %Y' )( new Date( d ) ); } )
            )
            .selectAll( 'text' )
            .attr( 'transform', 'rotate(-30)' ).style( 'text-anchor', 'end' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        g.append( 'g' )
            .call( d3.axisLeft( yScale ).ticks( 5 ) )
            .selectAll( 'text' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        // Bars
        var tooltip = helpers.createTooltip( container );

        g.selectAll( '.volume-bar' )
            .data( parsed )
            .join( 'rect' )
            .attr( 'class', 'volume-bar' )
            .attr( 'x', function ( d ) { return xScale( d.date.toISOString() ); } )
            .attr( 'y', innerHeight )
            .attr( 'width', xScale.bandwidth() )
            .attr( 'height', 0 )
            .attr( 'fill', '#6B2FA0' )
            .attr( 'rx', 2 )
            .on( 'mouseover', function ( event, d ) {
                d3.select( this ).attr( 'fill', '#8B5CF6' );
                tooltip.innerHTML = '<div class="mw-tooltip__date">' +
                    helpers.formatDate( d.date, 'quarter' ) + '</div>' +
                    '<strong>Volume:</strong> ' + d.volume + ' lots';
                tooltip.style.display = 'block';
            } )
            .on( 'mousemove', function ( event ) {
                var rect = container.getBoundingClientRect();
                tooltip.style.left = ( event.clientX - rect.left + 15 ) + 'px';
                tooltip.style.top = ( event.clientY - rect.top - 10 ) + 'px';
            } )
            .on( 'mouseout', function () {
                d3.select( this ).attr( 'fill', '#6B2FA0' );
                tooltip.style.display = 'none';
            } )
            .transition()
            .duration( 800 )
            .delay( function ( d, i ) { return i * 30; } )
            .attr( 'y', function ( d ) { return yScale( d.volume ); } )
            .attr( 'height', function ( d ) { return innerHeight - yScale( d.volume ); } );

        // Trend line
        var trendLine = d3.line()
            .x( function ( d ) { return xScale( d.date.toISOString() ) + xScale.bandwidth() / 2; } )
            .y( function ( d ) { return yScale( d.volume ); } )
            .curve( d3.curveMonotoneX );

        g.append( 'path' )
            .datum( parsed )
            .attr( 'fill', 'none' )
            .attr( 'stroke', '#C9A227' )
            .attr( 'stroke-width', 2 )
            .attr( 'd', trendLine )
            .attr( 'opacity', 0 )
            .transition().delay( 1000 ).duration( 500 )
            .attr( 'opacity', 1 );

        addLegendItem( legendEl, 'Auction Volume', '#6B2FA0', false );
        addLegendItem( legendEl, 'Trend', '#C9A227', true );
    }

    /**
     * Return Comparison: grouped bar chart
     */
    function renderReturnComparison( container, config, data ) {
        var chartEl = container.querySelector( '.mw-dataviz-block__chart' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        chartEl.innerHTML = '';
        legendEl.innerHTML = '';

        var margin = { top: 20, right: 30, bottom: 60, left: 55 };
        var width = chartEl.clientWidth;
        var height = 400;
        var innerWidth = width - margin.left - margin.right;
        var innerHeight = height - margin.top - margin.bottom;

        if ( innerWidth <= 0 ) return;

        // Compute average annual return per artist
        var artists = data.map( function ( series, idx ) {
            var vals = series.values;
            var start = vals[0].value;
            var end = vals[ vals.length - 1 ].value;
            var annualized = ( ( end / start ) - 1 ) * 100;
            return {
                name: series.artistName,
                return: Math.round( annualized * 10 ) / 10,
                color: brandColors[ idx % brandColors.length ],
            };
        } );

        var svg = d3.select( chartEl )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + width + ' ' + height )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', height );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        var xScale = d3.scaleBand()
            .domain( artists.map( function ( a ) { return a.name; } ) )
            .range( [ 0, innerWidth ] )
            .padding( 0.3 );

        var maxReturn = d3.max( artists, function ( a ) { return Math.abs( a.return ); } );
        var yScale = d3.scaleLinear()
            .domain( [ -maxReturn * 0.3, maxReturn * 1.2 ] )
            .nice()
            .range( [ innerHeight, 0 ] );

        // Zero line
        g.append( 'line' )
            .attr( 'x1', 0 ).attr( 'x2', innerWidth )
            .attr( 'y1', yScale( 0 ) ).attr( 'y2', yScale( 0 ) )
            .attr( 'stroke', '#9ca3af' ).attr( 'stroke-width', 1 );

        g.append( 'g' )
            .attr( 'transform', 'translate(0,' + innerHeight + ')' )
            .call( d3.axisBottom( xScale ) )
            .selectAll( 'text' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        g.append( 'g' )
            .call( d3.axisLeft( yScale ).ticks( 5 ).tickFormat( function ( d ) { return d + '%'; } ) )
            .selectAll( 'text' )
            .style( 'font-size', '11px' ).style( 'fill', '#6b7280' );

        var tooltip = helpers.createTooltip( container );

        g.selectAll( '.return-bar' )
            .data( artists )
            .join( 'rect' )
            .attr( 'class', 'return-bar' )
            .attr( 'x', function ( d ) { return xScale( d.name ); } )
            .attr( 'y', yScale( 0 ) )
            .attr( 'width', xScale.bandwidth() )
            .attr( 'height', 0 )
            .attr( 'fill', function ( d ) { return d.color; } )
            .attr( 'rx', 3 )
            .on( 'mouseover', function ( event, d ) {
                d3.select( this ).attr( 'opacity', 0.8 );
                tooltip.innerHTML = '<strong>' + d.name + '</strong><br>Return: ' + d.return + '%';
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
            .delay( function ( d, i ) { return i * 150; } )
            .attr( 'y', function ( d ) {
                return d.return >= 0 ? yScale( d.return ) : yScale( 0 );
            } )
            .attr( 'height', function ( d ) {
                return Math.abs( yScale( d.return ) - yScale( 0 ) );
            } );

        // Value labels
        g.selectAll( '.return-label' )
            .data( artists )
            .join( 'text' )
            .attr( 'class', 'return-label' )
            .attr( 'x', function ( d ) { return xScale( d.name ) + xScale.bandwidth() / 2; } )
            .attr( 'y', function ( d ) { return d.return >= 0 ? yScale( d.return ) - 8 : yScale( d.return ) + 18; } )
            .attr( 'text-anchor', 'middle' )
            .style( 'font-size', '12px' )
            .style( 'font-weight', '600' )
            .style( 'fill', '#374151' )
            .style( 'opacity', 0 )
            .text( function ( d ) { return d.return + '%'; } )
            .transition().delay( 1000 ).duration( 400 )
            .style( 'opacity', 1 );

        artists.forEach( function ( a ) {
            addLegendItem( legendEl, a.name, a.color, false );
        } );
    }

    /* ------------------------------------------------------------------ */
    /*  Shared helpers                                                     */
    /* ------------------------------------------------------------------ */

    function addTooltipOverlay( g, innerWidth, innerHeight, xScale, data, tooltip, container ) {
        var bisectDate = d3.bisector( function ( d ) { return d.date; } ).left;

        var focusLine = g.append( 'line' )
            .attr( 'y1', 0 ).attr( 'y2', innerHeight )
            .style( 'stroke', '#9ca3af' ).style( 'stroke-dasharray', '3,3' ).style( 'opacity', 0 );

        g.append( 'rect' )
            .attr( 'width', innerWidth ).attr( 'height', innerHeight )
            .attr( 'fill', 'none' ).attr( 'pointer-events', 'all' )
            .on( 'mousemove', function ( event ) {
                var coords = d3.pointer( event );
                var x0 = xScale.invert( coords[0] );
                focusLine.attr( 'x1', coords[0] ).attr( 'x2', coords[0] ).style( 'opacity', 1 );

                var lines = [];
                data.forEach( function ( series ) {
                    var parsed = series.values.map( function ( d ) {
                        return { date: new Date( d.date ), value: d.value };
                    } );
                    var idx = bisectDate( parsed, x0, 1 );
                    var d0 = parsed[ idx - 1 ];
                    if ( ! d0 ) return;
                    var d1 = parsed[ idx ];
                    var d = ( d1 && x0 - d0.date > d1.date - x0 ) ? d1 : d0;
                    lines.push( '<strong>' + series.artistName + ':</strong> ' + helpers.formatCurrency( d.value ) );
                } );

                tooltip.innerHTML = '<div class="mw-tooltip__date">' + helpers.formatDate( x0, 'month' ) + '</div>' + lines.join( '<br>' );
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
