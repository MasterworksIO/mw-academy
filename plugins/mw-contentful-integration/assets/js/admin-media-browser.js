/**
 * Contentful Media Browser - Admin JavaScript.
 *
 * Handles the admin media browser page: AJAX search with debounce, grid layout,
 * pagination, preview modal, copy-to-clipboard, and ACF field integration.
 *
 * @package MW_Contentful_Integration
 */

/* global jQuery, mwContentful */

( function( $ ) {
	'use strict';

	/**
	 * Media Browser controller for the admin page.
	 */
	var MWContentfulBrowser = {
		currentPage: 0,
		perPage: 20,
		totalItems: 0,
		searchTimer: null,
		currentQuery: '',
		currentFilter: '',
		isLoading: false,
		selectedAsset: null,
		acfCallback: null,

		/**
		 * Initialize the media browser.
		 */
		init: function() {
			this.bindEvents();
			this.loadAssets();
		},

		/**
		 * Bind all event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Search input with debounce.
			$( '#mw-contentful-search' ).on( 'input', function() {
				clearTimeout( self.searchTimer );
				self.searchTimer = setTimeout( function() {
					self.currentQuery = $( '#mw-contentful-search' ).val();
					self.currentPage = 0;
					self.loadAssets();
				}, 400 );
			} );

			// Content type filter.
			$( '#mw-contentful-filter-type' ).on( 'change', function() {
				self.currentFilter = $( this ).val();
				self.currentPage = 0;
				self.loadAssets();
			} );

			// Refresh button.
			$( '#mw-contentful-refresh' ).on( 'click', function() {
				self.clearCache( function() {
					self.currentPage = 0;
					self.loadAssets();
				} );
			} );

			// Pagination.
			$( '#mw-contentful-prev' ).on( 'click', function() {
				if ( self.currentPage > 0 ) {
					self.currentPage--;
					self.loadAssets();
				}
			} );

			$( '#mw-contentful-next' ).on( 'click', function() {
				var maxPage = Math.ceil( self.totalItems / self.perPage ) - 1;
				if ( self.currentPage < maxPage ) {
					self.currentPage++;
					self.loadAssets();
				}
			} );

			// Modal close.
			$( document ).on( 'click', '.mw-contentful-modal-close, .mw-contentful-modal-overlay', function() {
				self.closeModal();
			} );

			// Escape key closes modal.
			$( document ).on( 'keydown', function( e ) {
				if ( e.key === 'Escape' ) {
					self.closeModal();
				}
			} );

			// Grid item click (opens preview).
			$( document ).on( 'click', '.mw-contentful-grid-item', function() {
				var data = $( this ).data( 'asset' );
				if ( data ) {
					self.openModal( data );
				}
			} );

			// Copy ID button in grid.
			$( document ).on( 'click', '.mw-contentful-copy-btn', function( e ) {
				e.stopPropagation();
				var id = $( this ).data( 'id' );
				self.copyToClipboard( id );
			} );

			// Copy ID button in modal.
			$( document ).on( 'click', '.mw-contentful-copy-id', function( e ) {
				e.preventDefault();
				var targetId = $( this ).data( 'copy-target' );
				var text = $( '#' + targetId ).text();
				self.copyToClipboard( text );
			} );

			// Copy CDN URL in modal.
			$( document ).on( 'click', '.mw-contentful-modal-copy-url', function( e ) {
				e.preventDefault();
				if ( self.selectedAsset && self.selectedAsset.url ) {
					var url = self.selectedAsset.url;
					if ( url.indexOf( '//' ) === 0 ) {
						url = 'https:' + url;
					}
					self.copyToClipboard( url );
				}
			} );

			// Select button in modal (for ACF integration).
			$( document ).on( 'click', '.mw-contentful-modal-select', function( e ) {
				e.preventDefault();
				if ( self.selectedAsset && typeof self.acfCallback === 'function' ) {
					self.acfCallback( self.selectedAsset );
					self.closeModal();
				} else if ( self.selectedAsset ) {
					self.copyToClipboard( self.selectedAsset.id );
				}
			} );
		},

		/**
		 * Load assets from the server via AJAX.
		 */
		loadAssets: function() {
			var self = this;

			if ( self.isLoading ) {
				return;
			}

			self.isLoading = true;
			self.showLoading( true );
			self.hideError();

			$.ajax( {
				url: mwContentful.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mw_contentful_get_assets',
					nonce: mwContentful.nonce,
					query: self.currentQuery,
					limit: self.perPage,
					skip: self.currentPage * self.perPage,
					content_type: self.currentFilter
				},
				success: function( response ) {
					self.isLoading = false;
					self.showLoading( false );

					if ( response.success && response.data ) {
						self.totalItems = response.data.total || 0;
						self.renderGrid( response.data.items || [] );
						self.updatePagination();
					} else {
						var msg = ( response.data && response.data.message ) ? response.data.message : 'Failed to load assets.';
						self.showError( msg );
					}
				},
				error: function( xhr, status, error ) {
					self.isLoading = false;
					self.showLoading( false );
					self.showError( 'Request failed: ' + error );
				}
			} );
		},

		/**
		 * Render the asset grid.
		 *
		 * @param {Array} items Array of asset objects.
		 */
		renderGrid: function( items ) {
			var $grid = $( '#mw-contentful-grid' );
			$grid.empty();

			if ( items.length === 0 ) {
				$grid.html(
					'<div class="mw-contentful-empty">' +
					'<span class="dashicons dashicons-format-image"></span>' +
					'<p>No assets found.</p>' +
					'</div>'
				);
				return;
			}

			$.each( items, function( index, asset ) {
				var thumbUrl = '';
				if ( asset.url ) {
					var base = asset.url;
					if ( base.indexOf( '//' ) === 0 ) {
						base = 'https:' + base;
					}

					// SVGs do not support Contentful image transforms.
					if ( asset.content_type === 'image/svg+xml' ) {
						thumbUrl = base;
					} else {
						thumbUrl = base + '?w=300&h=200&fit=fill&fm=webp&q=70';
					}
				}

				var dimensions = '';
				if ( asset.width && asset.height ) {
					dimensions = asset.width + ' x ' + asset.height;
				}

				var $item = $(
					'<div class="mw-contentful-grid-item" title="' + self.escHtml( asset.title || asset.filename ) + '">' +
						'<div class="mw-contentful-grid-thumb">' +
							( thumbUrl
								? '<img src="' + self.escHtml( thumbUrl ) + '" alt="' + self.escHtml( asset.title || '' ) + '" loading="lazy" />'
								: '<span class="dashicons dashicons-format-image"></span>'
							) +
						'</div>' +
						'<div class="mw-contentful-grid-info">' +
							'<span class="mw-contentful-grid-title">' + self.escHtml( asset.title || asset.filename || 'Untitled' ) + '</span>' +
							'<span class="mw-contentful-grid-meta">' + self.escHtml( dimensions ) + '</span>' +
						'</div>' +
						'<button type="button" class="mw-contentful-copy-btn" data-id="' + self.escHtml( asset.id ) + '" title="Copy Asset ID">' +
							'<span class="dashicons dashicons-clipboard"></span>' +
						'</button>' +
					'</div>'
				);

				$item.data( 'asset', asset );
				$grid.append( $item );
			} );

			// Reference self for escHtml.
			var self = this;
		},

		/**
		 * Update pagination controls.
		 */
		updatePagination: function() {
			var $pagination = $( '#mw-contentful-pagination' );
			var totalPages = Math.ceil( this.totalItems / this.perPage );

			if ( totalPages <= 1 ) {
				$pagination.hide();
				return;
			}

			$pagination.show();
			$( '#mw-contentful-prev' ).prop( 'disabled', this.currentPage === 0 );
			$( '#mw-contentful-next' ).prop( 'disabled', this.currentPage >= totalPages - 1 );
			$( '#mw-contentful-page-info' ).text(
				'Page ' + ( this.currentPage + 1 ) + ' of ' + totalPages + ' (' + this.totalItems + ' assets)'
			);
		},

		/**
		 * Open the preview modal with asset details.
		 *
		 * @param {Object} asset The asset data object.
		 */
		openModal: function( asset ) {
			this.selectedAsset = asset;

			var imgUrl = '';
			if ( asset.url ) {
				var base = asset.url;
				if ( base.indexOf( '//' ) === 0 ) {
					base = 'https:' + base;
				}
				if ( asset.content_type === 'image/svg+xml' ) {
					imgUrl = base;
				} else {
					imgUrl = base + '?w=800&fm=webp&q=80';
				}
			}

			$( '#mw-contentful-modal-img' ).attr( 'src', imgUrl ).attr( 'alt', asset.title || '' );
			$( '#mw-contentful-modal-title' ).text( asset.title || asset.filename || 'Untitled' );
			$( '#mw-contentful-modal-id' ).text( asset.id );
			$( '#mw-contentful-modal-filename' ).text( asset.filename || '-' );
			$( '#mw-contentful-modal-type' ).text( asset.content_type || '-' );
			$( '#mw-contentful-modal-size' ).text( asset.size ? this.formatBytes( asset.size ) : '-' );
			$( '#mw-contentful-modal-description' ).text( asset.description || '-' );

			var dimensions = '-';
			if ( asset.width && asset.height ) {
				dimensions = asset.width + ' x ' + asset.height + 'px';
			}
			$( '#mw-contentful-modal-dimensions' ).text( dimensions );

			var created = '-';
			if ( asset.created_at ) {
				try {
					created = new Date( asset.created_at ).toLocaleDateString();
				} catch ( e ) {
					created = asset.created_at;
				}
			}
			$( '#mw-contentful-modal-created' ).text( created );

			$( '#mw-contentful-modal' ).fadeIn( 200 );
			$( 'body' ).addClass( 'mw-contentful-modal-open' );
		},

		/**
		 * Close the preview modal.
		 */
		closeModal: function() {
			$( '#mw-contentful-modal' ).fadeOut( 200 );
			$( 'body' ).removeClass( 'mw-contentful-modal-open' );
			this.selectedAsset = null;
		},

		/**
		 * Copy text to the clipboard and show a toast notification.
		 *
		 * @param {string} text The text to copy.
		 */
		copyToClipboard: function( text ) {
			if ( ! text ) {
				return;
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( text ).then( function() {
					MWContentfulBrowser.showToast();
				} );
			} else {
				// Fallback for older browsers.
				var $temp = $( '<textarea>' );
				$( 'body' ).append( $temp );
				$temp.val( text ).select();
				document.execCommand( 'copy' );
				$temp.remove();
				this.showToast();
			}
		},

		/**
		 * Show the "Copied!" toast notification.
		 */
		showToast: function() {
			var $toast = $( '#mw-contentful-toast' );
			$toast.stop( true ).fadeIn( 200 ).delay( 1500 ).fadeOut( 400 );
		},

		/**
		 * Show or hide the loading spinner.
		 *
		 * @param {boolean} show Whether to show the loader.
		 */
		showLoading: function( show ) {
			if ( show ) {
				$( '#mw-contentful-loading' ).show();
				$( '#mw-contentful-grid' ).css( 'opacity', '0.5' );
			} else {
				$( '#mw-contentful-loading' ).hide();
				$( '#mw-contentful-grid' ).css( 'opacity', '1' );
			}
		},

		/**
		 * Show an error message.
		 *
		 * @param {string} message The error message.
		 */
		showError: function( message ) {
			$( '#mw-contentful-error-message' ).text( message );
			$( '#mw-contentful-error' ).show();
		},

		/**
		 * Hide the error message.
		 */
		hideError: function() {
			$( '#mw-contentful-error' ).hide();
		},

		/**
		 * Clear the Contentful transient cache.
		 *
		 * @param {Function} callback Called after cache is cleared.
		 */
		clearCache: function( callback ) {
			$.ajax( {
				url: mwContentful.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mw_contentful_clear_cache',
					nonce: mwContentful.nonce
				},
				complete: function() {
					if ( typeof callback === 'function' ) {
						callback();
					}
				}
			} );
		},

		/**
		 * Format bytes into a human-readable string.
		 *
		 * @param {number} bytes The file size in bytes.
		 * @return {string} Formatted string (e.g., "1.5 MB").
		 */
		formatBytes: function( bytes ) {
			if ( bytes === 0 ) {
				return '0 B';
			}
			var units = [ 'B', 'KB', 'MB', 'GB' ];
			var i = Math.floor( Math.log( bytes ) / Math.log( 1024 ) );
			return ( bytes / Math.pow( 1024, i ) ).toFixed( 1 ) + ' ' + units[ i ];
		},

		/**
		 * Escape HTML entities in a string.
		 *
		 * @param {string} str The string to escape.
		 * @return {string} Escaped string.
		 */
		escHtml: function( str ) {
			if ( ! str ) {
				return '';
			}
			var div = document.createElement( 'div' );
			div.appendChild( document.createTextNode( str ) );
			return div.innerHTML;
		}
	};

	/**
	 * ACF Field Integration.
	 *
	 * Handles the "Browse Contentful" button in ACF fields by opening the media
	 * browser in a modal context.
	 */
	var MWContentfulACF = {

		/**
		 * Initialize ACF field integration.
		 */
		init: function() {
			this.bindEvents();
		},

		/**
		 * Bind ACF field events.
		 */
		bindEvents: function() {
			var self = this;

			// Browse button opens the browser modal.
			$( document ).on( 'click', '.mw-contentful-browse-btn', function( e ) {
				e.preventDefault();
				var $field = $( this ).closest( '.mw-contentful-acf-field' );
				self.openBrowser( $field );
			} );

			// Remove button clears the field value.
			$( document ).on( 'click', '.mw-contentful-remove-btn', function( e ) {
				e.preventDefault();
				var $field = $( this ).closest( '.mw-contentful-acf-field' );
				self.clearField( $field );
			} );
		},

		/**
		 * Open the media browser for an ACF field.
		 *
		 * @param {jQuery} $field The ACF field container.
		 */
		openBrowser: function( $field ) {
			// Set the callback so when an asset is selected in the modal,
			// it populates this ACF field.
			MWContentfulBrowser.acfCallback = function( asset ) {
				MWContentfulACF.selectAsset( $field, asset );
			};

			// If the admin page grid is not present, we create a temporary modal.
			if ( $( '#mw-contentful-grid' ).length === 0 ) {
				this.createInlineModal( $field );
			} else {
				$( '#mw-contentful-modal' ).fadeIn( 200 );
			}
		},

		/**
		 * Create an inline media browser modal for ACF fields.
		 *
		 * @param {jQuery} $field The ACF field container.
		 */
		createInlineModal: function( $field ) {
			// Create a simple inline browser.
			var $modal = $( '<div class="mw-contentful-acf-modal-wrap"></div>' );
			var $overlay = $( '<div class="mw-contentful-modal-overlay"></div>' );
			var $content = $(
				'<div class="mw-contentful-acf-inline-browser">' +
					'<div class="mw-contentful-acf-browser-header">' +
						'<h2>Browse Contentful Images</h2>' +
						'<button type="button" class="mw-contentful-acf-browser-close">&times;</button>' +
					'</div>' +
					'<div class="mw-contentful-acf-browser-search">' +
						'<input type="text" class="mw-contentful-acf-search-input" placeholder="Search assets..." />' +
					'</div>' +
					'<div class="mw-contentful-acf-browser-grid"></div>' +
					'<div class="mw-contentful-acf-browser-loading" style="display:none;">' +
						'<span class="spinner is-active"></span> Loading...' +
					'</div>' +
				'</div>'
			);

			$modal.append( $overlay ).append( $content );
			$( 'body' ).append( $modal );

			var searchTimer = null;
			var self = this;

			// Search with debounce.
			$content.find( '.mw-contentful-acf-search-input' ).on( 'input', function() {
				clearTimeout( searchTimer );
				var query = $( this ).val();
				searchTimer = setTimeout( function() {
					self.loadInlineAssets( $content, query, $field );
				}, 400 );
			} );

			// Close.
			$overlay.add( $content.find( '.mw-contentful-acf-browser-close' ) ).on( 'click', function() {
				$modal.remove();
				MWContentfulBrowser.acfCallback = null;
			} );

			// Load initial assets.
			self.loadInlineAssets( $content, '', $field );
		},

		/**
		 * Load assets into the inline ACF browser.
		 *
		 * @param {jQuery} $content The browser content container.
		 * @param {string} query    Search query.
		 * @param {jQuery} $field   The ACF field container.
		 */
		loadInlineAssets: function( $content, query, $field ) {
			var self = this;
			var $grid = $content.find( '.mw-contentful-acf-browser-grid' );
			var $loading = $content.find( '.mw-contentful-acf-browser-loading' );

			$loading.show();
			$grid.css( 'opacity', '0.5' );

			$.ajax( {
				url: mwContentful.ajaxUrl,
				type: 'POST',
				data: {
					action: 'mw_contentful_get_assets',
					nonce: mwContentful.nonce,
					query: query,
					limit: 20,
					skip: 0
				},
				success: function( response ) {
					$loading.hide();
					$grid.css( 'opacity', '1' ).empty();

					if ( response.success && response.data && response.data.items ) {
						$.each( response.data.items, function( i, asset ) {
							var thumbUrl = '';
							if ( asset.url ) {
								var base = asset.url;
								if ( base.indexOf( '//' ) === 0 ) {
									base = 'https:' + base;
								}
								if ( asset.content_type === 'image/svg+xml' ) {
									thumbUrl = base;
								} else {
									thumbUrl = base + '?w=150&h=150&fit=fill&fm=webp&q=60';
								}
							}

							var $item = $(
								'<div class="mw-contentful-acf-browser-item" title="' + MWContentfulBrowser.escHtml( asset.title || asset.filename ) + '">' +
									'<img src="' + MWContentfulBrowser.escHtml( thumbUrl ) + '" alt="" loading="lazy" />' +
									'<span>' + MWContentfulBrowser.escHtml( asset.title || asset.filename || 'Untitled' ) + '</span>' +
								'</div>'
							);

							$item.on( 'click', function() {
								self.selectAsset( $field, asset );
								$content.closest( '.mw-contentful-acf-modal-wrap' ).remove();
								MWContentfulBrowser.acfCallback = null;
							} );

							$grid.append( $item );
						} );

						if ( response.data.items.length === 0 ) {
							$grid.html( '<p class="mw-contentful-acf-no-results">No images found.</p>' );
						}
					}
				},
				error: function() {
					$loading.hide();
					$grid.html( '<p class="mw-contentful-acf-no-results">Failed to load assets.</p>' );
				}
			} );
		},

		/**
		 * Set the selected asset data on an ACF field.
		 *
		 * @param {jQuery} $field The ACF field container.
		 * @param {Object} asset  The selected asset data.
		 */
		selectAsset: function( $field, asset ) {
			var data = {
				asset_id: asset.id,
				title: asset.title || '',
				filename: asset.filename || '',
				content_type: asset.content_type || '',
				url: asset.url || '',
				width: asset.width || 0,
				height: asset.height || 0
			};

			$field.find( '.mw-contentful-acf-value' ).val( JSON.stringify( data ) ).trigger( 'change' );

			// Update preview.
			var $preview = $field.find( '.mw-contentful-acf-preview' );
			var thumbUrl = '';
			if ( data.url ) {
				var base = data.url;
				if ( base.indexOf( '//' ) === 0 ) {
					base = 'https:' + base;
				}
				thumbUrl = base + '?w=300&h=200&fit=fill&fm=webp&q=70';
			}

			$preview.find( '.mw-contentful-acf-thumb' ).html(
				thumbUrl ? '<img src="' + MWContentfulBrowser.escHtml( thumbUrl ) + '" alt="" />' : ''
			);
			$preview.find( '.mw-contentful-acf-title' ).text( data.title || data.filename );
			$preview.find( '.mw-contentful-acf-filename' ).text( data.filename );
			$preview.find( '.mw-contentful-acf-dimensions' ).text(
				data.width && data.height ? data.width + ' x ' + data.height + 'px' : ''
			);
			$preview.find( '.mw-contentful-acf-id' ).text( 'ID: ' + data.asset_id );
			$preview.show();

			// Update button labels.
			$field.find( '.mw-contentful-browse-btn' ).text( 'Replace Image' );
			$field.find( '.mw-contentful-remove-btn' ).show();
		},

		/**
		 * Clear an ACF field value.
		 *
		 * @param {jQuery} $field The ACF field container.
		 */
		clearField: function( $field ) {
			$field.find( '.mw-contentful-acf-value' ).val( '' ).trigger( 'change' );
			$field.find( '.mw-contentful-acf-preview' ).hide();
			$field.find( '.mw-contentful-browse-btn' ).text( 'Browse Contentful' );
			$field.find( '.mw-contentful-remove-btn' ).hide();
		}
	};

	/**
	 * Initialize on DOM ready.
	 */
	$( document ).ready( function() {
		// Initialize admin media browser page.
		if ( $( '#mw-contentful-grid' ).length > 0 ) {
			MWContentfulBrowser.init();
		}

		// Initialize ACF field integration.
		if ( $( '.mw-contentful-acf-field' ).length > 0 || $( '.acf-fields' ).length > 0 ) {
			MWContentfulACF.init();
		}
	} );

	// Expose for external use.
	window.MWContentfulBrowser = MWContentfulBrowser;
	window.MWContentfulACF = MWContentfulACF;

} )( jQuery );
