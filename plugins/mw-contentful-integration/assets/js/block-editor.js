/**
 * Gutenberg Block: Contentful Image (mw/contentful-image).
 *
 * Registers a block that allows inserting Contentful CDN images.
 * In editor: shows asset ID input and preview.
 * On frontend: server-side rendered responsive image.
 *
 * @package MW_Contentful_Integration
 */

/* global wp, mwContentfulBlock */

( function( blocks, element, components, blockEditor, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var TextControl = components.TextControl;
	var RangeControl = components.RangeControl;
	var PanelBody = components.PanelBody;
	var Button = components.Button;
	var InspectorControls = blockEditor.InspectorControls;
	var __ = i18n.__;

	blocks.registerBlockType( 'mw/contentful-image', {
		title: __( 'Contentful Image', 'mw-contentful-integration' ),
		description: __( 'Display an optimized image from Contentful CDN with responsive srcset.', 'mw-contentful-integration' ),
		icon: 'format-image',
		category: 'media',
		keywords: [
			__( 'contentful', 'mw-contentful-integration' ),
			__( 'image', 'mw-contentful-integration' ),
			__( 'cdn', 'mw-contentful-integration' ),
			__( 'masterworks', 'mw-contentful-integration' )
		],
		supports: {
			align: [ 'wide', 'full' ],
			html: false
		},
		attributes: {
			assetId: {
				type: 'string',
				default: ''
			},
			width: {
				type: 'number',
				default: 800
			},
			height: {
				type: 'number',
				default: 0
			},
			alt: {
				type: 'string',
				default: ''
			},
			caption: {
				type: 'string',
				default: ''
			},
			className: {
				type: 'string',
				default: ''
			}
		},

		edit: function( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var assetId = attributes.assetId;

			// Build preview URL from asset ID using the images CDN base.
			var previewUrl = '';
			if ( assetId && typeof mwContentfulBlock !== 'undefined' && mwContentfulBlock.imgBase ) {
				previewUrl = mwContentfulBlock.imgBase + '/' + assetId + '?w=600&fm=webp&q=70';
			}

			return el( Fragment, {},
				// Inspector (sidebar) controls.
				el( InspectorControls, {},
					el( PanelBody, {
						title: __( 'Image Settings', 'mw-contentful-integration' ),
						initialOpen: true
					},
						el( TextControl, {
							label: __( 'Asset ID', 'mw-contentful-integration' ),
							value: assetId,
							onChange: function( val ) {
								setAttributes( { assetId: val.trim() } );
							},
							help: __( 'The Contentful asset ID. Find it in the Contentful Media browser.', 'mw-contentful-integration' )
						} ),
						el( RangeControl, {
							label: __( 'Width (px)', 'mw-contentful-integration' ),
							value: attributes.width,
							onChange: function( val ) {
								setAttributes( { width: val } );
							},
							min: 100,
							max: 2400,
							step: 50
						} ),
						el( RangeControl, {
							label: __( 'Height (px)', 'mw-contentful-integration' ),
							value: attributes.height,
							onChange: function( val ) {
								setAttributes( { height: val } );
							},
							min: 0,
							max: 2400,
							step: 50,
							help: __( 'Set to 0 for automatic height based on aspect ratio.', 'mw-contentful-integration' )
						} ),
						el( TextControl, {
							label: __( 'Alt Text', 'mw-contentful-integration' ),
							value: attributes.alt,
							onChange: function( val ) {
								setAttributes( { alt: val } );
							}
						} ),
						el( TextControl, {
							label: __( 'Caption', 'mw-contentful-integration' ),
							value: attributes.caption,
							onChange: function( val ) {
								setAttributes( { caption: val } );
							}
						} )
					)
				),

				// Block content in the editor.
				el( 'div', {
					className: 'mw-contentful-block-editor'
				},
					! assetId
						// Placeholder state: no asset ID entered yet.
						? el( 'div', {
							className: 'mw-contentful-block-placeholder'
						},
							el( 'span', {
								className: 'dashicons dashicons-format-image',
								style: {
									fontSize: '48px',
									width: '48px',
									height: '48px',
									color: '#c3c4c7',
									display: 'block',
									margin: '0 auto 16px'
								}
							} ),
							el( 'p', {
								style: { color: '#646970', marginBottom: '12px' }
							}, __( 'Enter a Contentful Asset ID to display an image.', 'mw-contentful-integration' ) ),
							el( TextControl, {
								value: assetId,
								onChange: function( val ) {
									setAttributes( { assetId: val.trim() } );
								},
								placeholder: __( 'Paste asset ID here...', 'mw-contentful-integration' )
							} )
						)
						// Preview state: asset ID is set.
						: el( 'div', {
							className: 'mw-contentful-block-preview'
						},
							el( 'img', {
								src: previewUrl,
								alt: attributes.alt || __( 'Contentful image preview', 'mw-contentful-integration' ),
								style: {
									maxWidth: '100%',
									height: 'auto',
									display: 'block'
								},
								onError: function( e ) {
									e.target.style.display = 'none';
								}
							} ),
							attributes.caption
								? el( 'figcaption', {
									style: {
										padding: '8px 12px',
										fontSize: '13px',
										color: '#646970',
										textAlign: 'center',
										background: '#f6f7f7'
									}
								}, attributes.caption )
								: null,
							el( 'div', {
								className: 'mw-contentful-block-id-bar',
								style: {
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'space-between',
									padding: '8px 12px',
									background: '#f0f0f1',
									borderTop: '1px solid #dcdcde'
								}
							},
								el( 'code', {
									style: { fontSize: '12px', color: '#50575e' }
								}, assetId ),
								el( Button, {
									isSmall: true,
									variant: 'secondary',
									onClick: function() {
										setAttributes( { assetId: '' } );
									}
								}, __( 'Change', 'mw-contentful-integration' ) )
							)
						)
				)
			);
		},

		save: function() {
			// Dynamic block: rendered on the server via render_callback.
			return null;
		}
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.components,
	window.wp.blockEditor,
	window.wp.serverSideRender,
	window.wp.i18n
);
