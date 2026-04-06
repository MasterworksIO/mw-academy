/**
 * MW Data Visualizations - Shared D3 Chart Utility Functions
 *
 * Provides common helpers used across all D3-based visualization blocks.
 * Exposed as `window.mwChartHelpers`.
 */
( function () {
    'use strict';

    /* global d3, mwDataViz */

    /**
     * Masterworks brand color palette.
     */
    var brandColors = [
        '#6B2FA0', // Purple (primary)
        '#C9A227', // Gold (secondary)
        '#1A1A2E', // Navy (tertiary)
        '#8B5CF6', // Light purple
        '#D4A843', // Light gold
        '#2D2D5E', // Deep navy
        '#A855F7', // Vivid purple
        '#E5C76B', // Warm gold
        '#4A4A8A', // Slate purple
    ];

    /**
     * Create a responsive SVG element with a viewBox.
     *
     * @param {HTMLElement} container - DOM element to append SVG to.
     * @param {Object}      margin   - { top, right, bottom, left }
     * @param {number}      [height] - Optional explicit height. Defaults to container clientHeight or 400.
     * @returns {{ svg: d3.Selection, g: d3.Selection, width: number, height: number, innerWidth: number, innerHeight: number }}
     */
    function createResponsiveSvg( container, margin, height ) {
        var w = container.clientWidth || 600;
        var h = height || container.clientHeight || 400;
        var innerW = w - margin.left - margin.right;
        var innerH = h - margin.top - margin.bottom;

        var svg = d3.select( container )
            .append( 'svg' )
            .attr( 'viewBox', '0 0 ' + w + ' ' + h )
            .attr( 'preserveAspectRatio', 'xMidYMid meet' )
            .attr( 'width', '100%' )
            .attr( 'height', h )
            .attr( 'role', 'img' );

        var g = svg.append( 'g' )
            .attr( 'transform', 'translate(' + margin.left + ',' + margin.top + ')' );

        return {
            svg: svg,
            g: g,
            width: w,
            height: h,
            innerWidth: innerW,
            innerHeight: innerH,
        };
    }

    /**
     * Format a number as USD currency.
     *
     * @param {number} value
     * @returns {string}
     */
    function formatCurrency( value ) {
        if ( value == null || isNaN( value ) ) return '$0';

        var abs = Math.abs( value );
        if ( abs >= 1e9 ) {
            return '$' + ( value / 1e9 ).toFixed( 1 ) + 'B';
        }
        if ( abs >= 1e6 ) {
            return '$' + ( value / 1e6 ).toFixed( 1 ) + 'M';
        }
        if ( abs >= 1e3 ) {
            return '$' + ( value / 1e3 ).toFixed( 1 ) + 'K';
        }

        return '$' + value.toFixed( 0 );
    }

    /**
     * Format a Date object to a readable string.
     *
     * @param {Date}   date
     * @param {string} granularity - 'day' | 'month' | 'quarter' | 'year'
     * @returns {string}
     */
    function formatDate( date, granularity ) {
        if ( ! ( date instanceof Date ) || isNaN( date ) ) return '';

        var months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
        ];

        switch ( granularity ) {
            case 'day':
                return months[ date.getMonth() ] + ' ' + date.getDate() + ', ' + date.getFullYear();
            case 'month':
                return months[ date.getMonth() ] + ' ' + date.getFullYear();
            case 'quarter':
                var q = Math.floor( date.getMonth() / 3 ) + 1;
                return 'Q' + q + ' ' + date.getFullYear();
            case 'year':
                return '' + date.getFullYear();
            default:
                return months[ date.getMonth() ] + ' ' + date.getFullYear();
        }
    }

    /**
     * Create a tooltip element attached to a container.
     *
     * @param {HTMLElement} container
     * @returns {HTMLElement} The tooltip element.
     */
    function createTooltip( container ) {
        // Reuse existing tooltip if present
        var existing = container.querySelector( '.mw-dataviz-tooltip' );
        if ( existing ) {
            existing.innerHTML = '';
            return existing;
        }

        var tip = document.createElement( 'div' );
        tip.className = 'mw-dataviz-tooltip';
        tip.style.display = 'none';
        container.appendChild( tip );
        return tip;
    }

    /**
     * IntersectionObserver-based scroll animation trigger.
     * Calls `callback` once when the element first scrolls into view.
     *
     * @param {HTMLElement} element
     * @param {Function}    callback
     * @param {Object}      [options]
     * @param {number}      [options.threshold=0.15]
     */
    function animateOnScroll( element, callback, options ) {
        var threshold = ( options && options.threshold ) || 0.15;

        if ( typeof IntersectionObserver === 'undefined' ) {
            // Fallback: run immediately
            callback();
            return;
        }

        var observer = new IntersectionObserver( function ( entries ) {
            entries.forEach( function ( entry ) {
                if ( entry.isIntersecting ) {
                    observer.unobserve( element );
                    callback();
                }
            } );
        }, { threshold: threshold } );

        observer.observe( element );
    }

    /**
     * Fetch chart data from the MW Academy REST API.
     *
     * @param {string} endpoint - Relative endpoint path (e.g., 'indices', 'artists/123/performance').
     * @param {Object} [params] - Query parameters as key/value pairs.
     * @returns {Promise<Object>} Parsed JSON response.
     */
    function fetchChartData( endpoint, params ) {
        var baseUrl = ( window.mwDataViz && window.mwDataViz.restUrl ) || '/wp-json/mw-academy/v1/';
        var nonce = ( window.mwDataViz && window.mwDataViz.nonce ) || '';

        var queryParts = [];
        if ( params ) {
            Object.keys( params ).forEach( function ( key ) {
                var val = params[ key ];
                if ( val !== undefined && val !== null && val !== '' ) {
                    queryParts.push( encodeURIComponent( key ) + '=' + encodeURIComponent( val ) );
                }
            } );
        }

        var url = baseUrl + endpoint;
        if ( queryParts.length > 0 ) {
            url += ( url.indexOf( '?' ) > -1 ? '&' : '?' ) + queryParts.join( '&' );
        }

        var headers = {
            'Content-Type': 'application/json',
        };
        if ( nonce ) {
            headers[ 'X-WP-Nonce' ] = nonce;
        }

        return fetch( url, { headers: headers } )
            .then( function ( response ) {
                if ( ! response.ok ) {
                    throw new Error( 'HTTP ' + response.status + ': ' + response.statusText );
                }
                return response.json();
            } )
            .then( function ( json ) {
                if ( json && json.success === false ) {
                    throw new Error( json.message || 'API returned unsuccessful response.' );
                }
                return json;
            } );
    }

    /**
     * Debounce utility.
     *
     * @param {Function} fn
     * @param {number}   delay
     * @returns {Function}
     */
    function debounce( fn, delay ) {
        var timer;
        return function () {
            var context = this;
            var args = arguments;
            clearTimeout( timer );
            timer = setTimeout( function () {
                fn.apply( context, args );
            }, delay );
        };
    }

    /**
     * Generate an array of colors from the brand palette for a given count.
     *
     * @param {number} count
     * @returns {string[]}
     */
    function getPaletteColors( count ) {
        var colors = [];
        for ( var i = 0; i < count; i++ ) {
            colors.push( brandColors[ i % brandColors.length ] );
        }
        return colors;
    }

    // Expose as global
    window.mwChartHelpers = {
        brandColors: brandColors,
        createResponsiveSvg: createResponsiveSvg,
        formatCurrency: formatCurrency,
        formatDate: formatDate,
        createTooltip: createTooltip,
        animateOnScroll: animateOnScroll,
        fetchChartData: fetchChartData,
        debounce: debounce,
        getPaletteColors: getPaletteColors,
    };

} )();
