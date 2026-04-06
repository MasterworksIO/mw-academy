/**
 * Globe Visualization - Frontend Three.js Visualization
 *
 * Renders a 3D Earth globe with heat-colored regions,
 * orbit controls, and data-driven markers.
 */
( function () {
    'use strict';

    /* global THREE, mwDataViz */

    var BRAND = {
        navy: 0x1A1A2E,
        purple: 0x6B2FA0,
        gold: 0xC9A227,
        bg: 0x0D0D1A,
    };

    /**
     * Check for WebGL support.
     */
    function hasWebGL() {
        try {
            var canvas = document.createElement( 'canvas' );
            return !! ( window.WebGLRenderingContext &&
                ( canvas.getContext( 'webgl' ) || canvas.getContext( 'experimental-webgl' ) ) );
        } catch ( e ) {
            return false;
        }
    }

    function init() {
        var containers = document.querySelectorAll( '.mw-globe-visualization' );
        containers.forEach( function ( container ) {
            if ( container.dataset.initialized ) return;
            container.dataset.initialized = 'true';

            if ( ! hasWebGL() ) {
                hideLoading( container );
                showError( container );
                return;
            }

            // Only render when visible
            var observer = new IntersectionObserver( function ( entries ) {
                entries.forEach( function ( entry ) {
                    if ( entry.isIntersecting ) {
                        observer.unobserve( container );
                        loadAndRender( container );
                    }
                } );
            }, { threshold: 0.1 } );
            observer.observe( container );
        } );
    }

    function loadAndRender( container ) {
        var config = {
            metric: container.dataset.metric || 'auction-volume',
            year: parseInt( container.dataset.year, 10 ) || 2025,
            autoRotate: container.dataset.autoRotate === 'true',
            highlightRegions: ( container.dataset.highlightRegions || '' ).split( ',' ).filter( Boolean ),
        };

        // Fetch globe data from REST API
        var restUrl = ( window.mwDataViz && window.mwDataViz.restUrl ) || '/wp-json/mw-academy/v1/';
        var nonce = ( window.mwDataViz && window.mwDataViz.nonce ) || '';

        var url = restUrl + 'globe?metric=' + encodeURIComponent( config.metric ) +
            '&year=' + config.year;

        fetch( url, {
            headers: nonce ? { 'X-WP-Nonce': nonce } : {},
        } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( response ) {
                hideLoading( container );
                if ( ! response || ! response.data ) {
                    showError( container );
                    return;
                }
                createGlobe( container, config, response.data );
            } )
            .catch( function ( err ) {
                console.error( 'MW Globe error:', err );
                hideLoading( container );
                showError( container );
            } );
    }

    function createGlobe( container, config, data ) {
        var canvasEl = container.querySelector( '.mw-dataviz-block__canvas' );
        var legendEl = container.querySelector( '.mw-dataviz-block__legend' );
        canvasEl.innerHTML = '';

        var width = canvasEl.clientWidth || 600;
        var height = 500;

        // --- Scene setup ---
        var scene = new THREE.Scene();
        scene.background = new THREE.Color( BRAND.bg );

        var camera = new THREE.PerspectiveCamera( 45, width / height, 0.1, 1000 );
        camera.position.set( 0, 0, 3 );

        var renderer = new THREE.WebGLRenderer( { antialias: true, alpha: false } );
        renderer.setSize( width, height );
        renderer.setPixelRatio( Math.min( window.devicePixelRatio, 2 ) );
        canvasEl.appendChild( renderer.domElement );

        // --- Lighting ---
        var ambientLight = new THREE.AmbientLight( 0xffffff, 0.5 );
        scene.add( ambientLight );

        var directionalLight = new THREE.DirectionalLight( 0xffffff, 0.8 );
        directionalLight.position.set( 5, 3, 5 );
        scene.add( directionalLight );

        var pointLight = new THREE.PointLight( BRAND.purple, 0.4, 20 );
        pointLight.position.set( -3, 2, 3 );
        scene.add( pointLight );

        // --- Globe sphere ---
        var globeGeometry = new THREE.SphereGeometry( 1, 64, 64 );
        var globeMaterial = new THREE.MeshPhongMaterial( {
            color: BRAND.navy,
            emissive: 0x0a0a1a,
            specular: 0x333366,
            shininess: 25,
            transparent: true,
            opacity: 0.95,
        } );
        var globe = new THREE.Mesh( globeGeometry, globeMaterial );
        scene.add( globe );

        // --- Wireframe overlay ---
        var wireGeometry = new THREE.SphereGeometry( 1.002, 36, 18 );
        var wireMaterial = new THREE.MeshBasicMaterial( {
            color: 0x2D2D5E,
            wireframe: true,
            transparent: true,
            opacity: 0.15,
        } );
        var wireframe = new THREE.Mesh( wireGeometry, wireMaterial );
        scene.add( wireframe );

        // --- Atmosphere glow ---
        var glowGeometry = new THREE.SphereGeometry( 1.08, 64, 64 );
        var glowMaterial = new THREE.MeshBasicMaterial( {
            color: BRAND.purple,
            transparent: true,
            opacity: 0.06,
            side: THREE.BackSide,
        } );
        var glow = new THREE.Mesh( glowGeometry, glowMaterial );
        scene.add( glow );

        // --- Data markers ---
        var regions = data.regions || [];
        var maxValue = Math.max.apply( null, regions.map( function ( r ) { return r.value; } ) );
        var markers = [];

        regions.forEach( function ( region ) {
            var pos = latLngToVector3( region.lat, region.lng, 1.01 );
            var normalizedValue = region.value / maxValue;

            // Marker pillar
            var pillarHeight = 0.05 + normalizedValue * 0.25;
            var pillarGeometry = new THREE.CylinderGeometry( 0.008, 0.015, pillarHeight, 8 );
            var pillarColor = lerpColor( BRAND.purple, BRAND.gold, normalizedValue );
            var pillarMaterial = new THREE.MeshPhongMaterial( {
                color: pillarColor,
                emissive: pillarColor,
                emissiveIntensity: 0.3,
                transparent: true,
                opacity: 0.9,
            } );
            var pillar = new THREE.Mesh( pillarGeometry, pillarMaterial );

            // Position and orient pillar
            pillar.position.copy( pos );
            pillar.lookAt( 0, 0, 0 );
            pillar.rotateX( Math.PI / 2 );
            pillar.translateY( pillarHeight / 2 );

            // Glow dot on top
            var dotGeometry = new THREE.SphereGeometry( 0.012 + normalizedValue * 0.015, 16, 16 );
            var dotMaterial = new THREE.MeshBasicMaterial( {
                color: pillarColor,
                transparent: true,
                opacity: 0.8,
            } );
            var dot = new THREE.Mesh( dotGeometry, dotMaterial );
            var topPos = latLngToVector3( region.lat, region.lng, 1.01 + pillarHeight );
            dot.position.copy( topPos );

            pillar.userData = { region: region };
            dot.userData = { region: region };

            scene.add( pillar );
            scene.add( dot );
            markers.push( { pillar: pillar, dot: dot, region: region } );
        } );

        // --- OrbitControls ---
        // Three.js OrbitControls via global if loaded, otherwise manual rotation
        var controls = null;
        if ( typeof THREE.OrbitControls !== 'undefined' ) {
            controls = new THREE.OrbitControls( camera, renderer.domElement );
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.rotateSpeed = 0.5;
            controls.enableZoom = true;
            controls.minDistance = 1.8;
            controls.maxDistance = 6;
            controls.autoRotate = config.autoRotate;
            controls.autoRotateSpeed = 0.5;
        }

        // --- Tooltip ---
        var tooltip = document.createElement( 'div' );
        tooltip.className = 'mw-dataviz-tooltip mw-dataviz-tooltip--dark';
        tooltip.style.display = 'none';
        container.appendChild( tooltip );

        // --- Raycaster for hover ---
        var raycaster = new THREE.Raycaster();
        var mouse = new THREE.Vector2();

        renderer.domElement.addEventListener( 'mousemove', function ( event ) {
            var rect = renderer.domElement.getBoundingClientRect();
            mouse.x = ( ( event.clientX - rect.left ) / rect.width ) * 2 - 1;
            mouse.y = -( ( event.clientY - rect.top ) / rect.height ) * 2 + 1;

            raycaster.setFromCamera( mouse, camera );
            var intersects = raycaster.intersectObjects(
                markers.map( function ( m ) { return m.dot; } ).concat(
                    markers.map( function ( m ) { return m.pillar; } )
                )
            );

            if ( intersects.length > 0 ) {
                var region = intersects[0].object.userData.region;
                if ( region ) {
                    tooltip.innerHTML = '<strong>' + region.name + '</strong><br>' +
                        formatMetricLabel( config.metric ) + ': ' + formatValue( region.value, config.metric );
                    tooltip.style.display = 'block';
                    tooltip.style.left = ( event.clientX - container.getBoundingClientRect().left + 15 ) + 'px';
                    tooltip.style.top = ( event.clientY - container.getBoundingClientRect().top - 10 ) + 'px';
                    renderer.domElement.style.cursor = 'pointer';
                }
            } else {
                tooltip.style.display = 'none';
                renderer.domElement.style.cursor = 'grab';
            }
        } );

        renderer.domElement.addEventListener( 'mouseleave', function () {
            tooltip.style.display = 'none';
        } );

        // --- Animation loop ---
        var isVisible = true;
        var animationId;

        function animate() {
            animationId = requestAnimationFrame( animate );

            if ( ! isVisible ) return;

            // Slow auto-rotate if no OrbitControls
            if ( ! controls && config.autoRotate ) {
                globe.rotation.y += 0.002;
                wireframe.rotation.y += 0.002;
            }

            if ( controls ) {
                controls.update();
            }

            // Subtle marker pulsing
            var t = Date.now() * 0.001;
            markers.forEach( function ( m, i ) {
                var pulse = 0.8 + 0.2 * Math.sin( t * 2 + i );
                m.dot.material.opacity = pulse;
            } );

            renderer.render( scene, camera );
        }
        animate();

        // --- Visibility observer (pause when off-screen) ---
        var visObserver = new IntersectionObserver( function ( entries ) {
            entries.forEach( function ( entry ) {
                isVisible = entry.isIntersecting;
            } );
        }, { threshold: 0.05 } );
        visObserver.observe( container );

        // --- Responsive resize ---
        var resizeTimer;
        var resizeObserver = new ResizeObserver( function () {
            clearTimeout( resizeTimer );
            resizeTimer = setTimeout( function () {
                var newWidth = canvasEl.clientWidth;
                if ( newWidth > 0 ) {
                    camera.aspect = newWidth / height;
                    camera.updateProjectionMatrix();
                    renderer.setSize( newWidth, height );
                }
            }, 200 );
        } );
        resizeObserver.observe( canvasEl );

        // --- Legend ---
        buildGlobeLegend( legendEl, config );
    }

    /* ------------------------------------------------------------------ */
    /*  Utility functions                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Convert lat/lng to a Three.js Vector3 on a unit sphere.
     */
    function latLngToVector3( lat, lng, radius ) {
        var phi = ( 90 - lat ) * ( Math.PI / 180 );
        var theta = ( lng + 180 ) * ( Math.PI / 180 );
        return new THREE.Vector3(
            -radius * Math.sin( phi ) * Math.cos( theta ),
            radius * Math.cos( phi ),
            radius * Math.sin( phi ) * Math.sin( theta )
        );
    }

    /**
     * Linear interpolation between two hex colors.
     */
    function lerpColor( color1, color2, t ) {
        var r1 = ( color1 >> 16 ) & 0xFF;
        var g1 = ( color1 >> 8 ) & 0xFF;
        var b1 = color1 & 0xFF;
        var r2 = ( color2 >> 16 ) & 0xFF;
        var g2 = ( color2 >> 8 ) & 0xFF;
        var b2 = color2 & 0xFF;
        var r = Math.round( r1 + ( r2 - r1 ) * t );
        var g = Math.round( g1 + ( g2 - g1 ) * t );
        var b = Math.round( b1 + ( b2 - b1 ) * t );
        return ( r << 16 ) | ( g << 8 ) | b;
    }

    function formatMetricLabel( metric ) {
        var labels = {
            'auction-volume': 'Auction Volume',
            'price-growth': 'Price Growth',
            'collector-density': 'Collector Density',
        };
        return labels[ metric ] || metric;
    }

    function formatValue( value, metric ) {
        if ( metric === 'price-growth' ) return value.toFixed( 1 ) + '%';
        if ( metric === 'collector-density' ) return value.toLocaleString() + ' / million';
        return '$' + value.toLocaleString() + 'M';
    }

    function buildGlobeLegend( legendEl, config ) {
        legendEl.innerHTML = '';

        var gradient = document.createElement( 'div' );
        gradient.className = 'mw-dataviz-legend__gradient';
        gradient.style.background = 'linear-gradient(to right, #6B2FA0, #C9A227)';
        gradient.style.height = '8px';
        gradient.style.borderRadius = '4px';
        gradient.style.margin = '8px 0';

        var labels = document.createElement( 'div' );
        labels.className = 'mw-dataviz-legend__gradient-labels';
        labels.style.display = 'flex';
        labels.style.justifyContent = 'space-between';
        labels.style.color = '#9ca3af';
        labels.style.fontSize = '11px';
        labels.innerHTML = '<span>Low</span><span>' + formatMetricLabel( config.metric ) + '</span><span>High</span>';

        legendEl.appendChild( gradient );
        legendEl.appendChild( labels );
    }

    function hideLoading( c ) {
        var el = c.querySelector( '.mw-dataviz-block__loading' );
        if ( el ) el.style.display = 'none';
    }

    function showError( c ) {
        var el = c.querySelector( '.mw-dataviz-block__error' );
        if ( el ) el.style.display = 'flex';
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();
