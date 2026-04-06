<?php
/**
 * ACF Field Group registrations for Masterworks Academy.
 *
 * @package MW_Content_Types
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all ACF field groups.
 */
function mw_register_acf_field_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// -----------------------------------------------------------------
	// Research Report Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_research_report',
		'title'    => 'Research Report Fields',
		'fields'   => array(
			array(
				'key'           => 'field_rr_report_type',
				'label'         => 'Report Type',
				'name'          => 'report_type',
				'type'          => 'select',
				'choices'       => array(
					'quarterly_index'  => 'Quarterly Index',
					'market_update'    => 'Market Update',
					'portfolio_update' => 'Portfolio Update',
					'annual_report'    => 'Annual Report',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'            => 'field_rr_publication_date',
				'label'          => 'Publication Date',
				'name'           => 'publication_date',
				'type'           => 'date_picker',
				'display_format' => 'F j, Y',
				'return_format'  => 'Y-m-d',
				'first_day'      => 1,
			),
			array(
				'key'        => 'field_rr_key_findings',
				'label'      => 'Key Findings',
				'name'       => 'key_findings',
				'type'       => 'repeater',
				'layout'     => 'table',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_rr_finding_label',
						'label' => 'Finding Label',
						'name'  => 'finding_label',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_rr_finding_value',
						'label' => 'Finding Value',
						'name'  => 'finding_value',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_rr_finding_change_pct',
						'label' => 'Change %',
						'name'  => 'finding_change_pct',
						'type'  => 'number',
						'step'  => '0.01',
					),
				),
			),
			array(
				'key'           => 'field_rr_download_pdf',
				'label'         => 'Download PDF',
				'name'          => 'download_pdf',
				'type'          => 'file',
				'return_format' => 'array',
				'mime_types'    => 'pdf',
			),
			array(
				'key'   => 'field_rr_data_visualization_id',
				'label' => 'Data Visualization ID',
				'name'  => 'data_visualization_id',
				'type'  => 'text',
				'instructions' => 'ID that links to a D3 visualization.',
			),
			array(
				'key'          => 'field_rr_methodology_notes',
				'label'        => 'Methodology Notes',
				'name'         => 'methodology_notes',
				'type'         => 'wysiwyg',
				'tabs'         => 'all',
				'toolbar'      => 'full',
				'media_upload' => 1,
			),
			array(
				'key'           => 'field_rr_related_artists',
				'label'         => 'Related Artists',
				'name'          => 'related_artists',
				'type'          => 'relationship',
				'post_type'     => array( 'artist-dossier' ),
				'filters'       => array( 'search', 'post_type' ),
				'return_format' => 'object',
				'min'           => 0,
				'max'           => 0,
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'research-report',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// Artist Dossier Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_artist_dossier',
		'title'    => 'Artist Dossier Fields',
		'fields'   => array(
			array(
				'key'   => 'field_ad_artist_id',
				'label' => 'Artist ID',
				'name'  => 'artist_id',
				'type'  => 'number',
				'instructions' => 'Internal system ID.',
			),
			array(
				'key'   => 'field_ad_birth_year',
				'label' => 'Birth Year',
				'name'  => 'birth_year',
				'type'  => 'number',
			),
			array(
				'key'      => 'field_ad_death_year',
				'label'    => 'Death Year',
				'name'     => 'death_year',
				'type'     => 'number',
				'required' => 0,
			),
			array(
				'key'   => 'field_ad_nationality',
				'label' => 'Nationality',
				'name'  => 'nationality',
				'type'  => 'text',
			),
			array(
				'key'           => 'field_ad_medium',
				'label'         => 'Medium',
				'name'          => 'medium',
				'type'          => 'select',
				'choices'       => array(
					'painting'    => 'Painting',
					'sculpture'   => 'Sculpture',
					'photography' => 'Photography',
					'mixed_media' => 'Mixed Media',
					'digital'     => 'Digital',
					'print'       => 'Print',
				),
				'default_value' => '',
				'allow_null'    => 1,
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_ad_market_trajectory',
				'label'         => 'Market Trajectory',
				'name'          => 'market_trajectory',
				'type'          => 'select',
				'choices'       => array(
					'rising'   => 'Rising',
					'stable'   => 'Stable',
					'cooling'  => 'Cooling',
					'volatile' => 'Volatile',
				),
				'default_value' => '',
				'allow_null'    => 1,
				'return_format' => 'value',
			),
			array(
				'key'          => 'field_ad_price_range_low',
				'label'        => 'Price Range Low (USD)',
				'name'         => 'price_range_low',
				'type'         => 'number',
				'prepend'      => '$',
				'step'         => 1,
			),
			array(
				'key'          => 'field_ad_price_range_high',
				'label'        => 'Price Range High (USD)',
				'name'         => 'price_range_high',
				'type'         => 'number',
				'prepend'      => '$',
				'step'         => 1,
			),
			array(
				'key'   => 'field_ad_total_auction_lots',
				'label' => 'Total Auction Lots',
				'name'  => 'total_auction_lots',
				'type'  => 'number',
			),
			array(
				'key'     => 'field_ad_avg_annual_return',
				'label'   => 'Avg. Annual Return (%)',
				'name'    => 'avg_annual_return',
				'type'    => 'number',
				'append'  => '%',
				'step'    => '0.01',
			),
			array(
				'key'        => 'field_ad_key_metrics',
				'label'      => 'Key Metrics',
				'name'       => 'key_metrics',
				'type'       => 'repeater',
				'layout'     => 'table',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_ad_metric_name',
						'label' => 'Metric Name',
						'name'  => 'metric_name',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_ad_metric_value',
						'label' => 'Metric Value',
						'name'  => 'metric_value',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_ad_metric_period',
						'label' => 'Metric Period',
						'name'  => 'metric_period',
						'type'  => 'text',
					),
				),
			),
			array(
				'key'        => 'field_ad_exhibition_history',
				'label'      => 'Exhibition History',
				'name'       => 'exhibition_history',
				'type'       => 'repeater',
				'layout'     => 'row',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_ad_exhibition_name',
						'label' => 'Exhibition Name',
						'name'  => 'exhibition_name',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_ad_venue',
						'label' => 'Venue',
						'name'  => 'venue',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_ad_exhibition_year',
						'label' => 'Year',
						'name'  => 'year',
						'type'  => 'number',
					),
					array(
						'key'   => 'field_ad_significance',
						'label' => 'Significance',
						'name'  => 'significance',
						'type'  => 'text',
					),
				),
			),
			array(
				'key'        => 'field_ad_notable_sales',
				'label'      => 'Notable Sales',
				'name'       => 'notable_sales',
				'type'       => 'repeater',
				'layout'     => 'row',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_ad_work_title',
						'label' => 'Work Title',
						'name'  => 'work_title',
						'type'  => 'text',
					),
					array(
						'key'     => 'field_ad_sale_price',
						'label'   => 'Sale Price',
						'name'    => 'sale_price',
						'type'    => 'number',
						'prepend' => '$',
						'step'    => 1,
					),
					array(
						'key'   => 'field_ad_auction_house',
						'label' => 'Auction House',
						'name'  => 'auction_house',
						'type'  => 'text',
					),
					array(
						'key'            => 'field_ad_sale_date',
						'label'          => 'Sale Date',
						'name'           => 'sale_date',
						'type'           => 'date_picker',
						'display_format' => 'F j, Y',
						'return_format'  => 'Y-m-d',
					),
				),
			),
			array(
				'key'           => 'field_ad_masterworks_offerings',
				'label'         => 'Masterworks Offerings',
				'name'          => 'masterworks_offerings',
				'type'          => 'relationship',
				'post_type'     => array(),
				'filters'       => array( 'search', 'post_type' ),
				'return_format' => 'object',
				'min'           => 0,
				'max'           => 0,
			),
			array(
				'key'          => 'field_ad_contentful_hero_image_id',
				'label'        => 'Contentful Hero Image ID',
				'name'         => 'contentful_hero_image_id',
				'type'         => 'text',
				'instructions' => 'Contentful asset ID for the hero image.',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'artist-dossier',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// Market Commentary Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_market_commentary',
		'title'    => 'Market Commentary Fields',
		'fields'   => array(
			array(
				'key'           => 'field_mc_commentary_type',
				'label'         => 'Commentary Type',
				'name'          => 'commentary_type',
				'type'          => 'select',
				'choices'       => array(
					'hot_take'   => 'Hot Take',
					'analysis'   => 'Analysis',
					'prediction' => 'Prediction',
					'reaction'   => 'Reaction',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_mc_sentiment',
				'label'         => 'Sentiment',
				'name'          => 'sentiment',
				'type'          => 'select',
				'choices'       => array(
					'bullish' => 'Bullish',
					'bearish' => 'Bearish',
					'neutral' => 'Neutral',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_mc_related_artists',
				'label'         => 'Related Artists',
				'name'          => 'related_artists',
				'type'          => 'relationship',
				'post_type'     => array( 'artist-dossier' ),
				'filters'       => array( 'search' ),
				'return_format' => 'object',
				'min'           => 0,
				'max'           => 0,
			),
			array(
				'key'   => 'field_mc_key_data_point',
				'label' => 'Key Data Point',
				'name'  => 'key_data_point',
				'type'  => 'text',
			),
			array(
				'key'   => 'field_mc_key_data_value',
				'label' => 'Key Data Value',
				'name'  => 'key_data_value',
				'type'  => 'text',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'market-commentary',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// Explainer Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_explainer',
		'title'    => 'Explainer Fields',
		'fields'   => array(
			array(
				'key'           => 'field_ex_difficulty_level',
				'label'         => 'Difficulty Level',
				'name'          => 'difficulty_level',
				'type'          => 'select',
				'choices'       => array(
					'beginner'     => 'Beginner',
					'intermediate' => 'Intermediate',
					'advanced'     => 'Advanced',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'          => 'field_ex_series_name',
				'label'        => 'Series Name',
				'name'         => 'series_name',
				'type'         => 'text',
				'instructions' => 'e.g. "Art Market Mechanics", "Masterworks Explained"',
			),
			array(
				'key'   => 'field_ex_series_order',
				'label' => 'Series Order',
				'name'  => 'series_order',
				'type'  => 'number',
			),
			array(
				'key'        => 'field_ex_key_takeaways',
				'label'      => 'Key Takeaways',
				'name'       => 'key_takeaways',
				'type'       => 'repeater',
				'layout'     => 'table',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_ex_takeaway_text',
						'label' => 'Takeaway',
						'name'  => 'takeaway_text',
						'type'  => 'text',
					),
				),
			),
			array(
				'key'           => 'field_ex_related_explainers',
				'label'         => 'Related Explainers',
				'name'          => 'related_explainers',
				'type'          => 'relationship',
				'post_type'     => array( 'explainer' ),
				'filters'       => array( 'search' ),
				'return_format' => 'object',
				'min'           => 0,
				'max'           => 0,
			),
			array(
				'key'        => 'field_ex_faq_items',
				'label'      => 'FAQ Items',
				'name'       => 'faq_items',
				'type'       => 'repeater',
				'layout'     => 'row',
				'min'        => 0,
				'max'        => 0,
				'instructions' => 'Used to generate FAQ schema markup.',
				'sub_fields' => array(
					array(
						'key'   => 'field_ex_faq_question',
						'label' => 'Question',
						'name'  => 'question',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_ex_faq_answer',
						'label' => 'Answer',
						'name'  => 'answer',
						'type'  => 'textarea',
						'rows'  => 4,
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'explainer',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// Daily News Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_daily_news',
		'title'    => 'Daily News Fields',
		'fields'   => array(
			array(
				'key'        => 'field_dn_news_items',
				'label'      => 'News Items',
				'name'       => 'news_items',
				'type'       => 'repeater',
				'layout'     => 'row',
				'min'        => 1,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_dn_headline',
						'label' => 'Headline',
						'name'  => 'headline',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_dn_source',
						'label' => 'Source',
						'name'  => 'source',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_dn_source_url',
						'label' => 'Source URL',
						'name'  => 'source_url',
						'type'  => 'url',
					),
					array(
						'key'           => 'field_dn_category',
						'label'         => 'Category',
						'name'          => 'category',
						'type'          => 'select',
						'choices'       => array(
							'art_market'   => 'Art Market',
							'financial'    => 'Financial & Economic',
							'regulatory'   => 'Regulatory',
							'masterworks'  => 'Masterworks',
						),
						'default_value' => '',
						'allow_null'    => 0,
						'return_format' => 'value',
					),
					array(
						'key'   => 'field_dn_why_it_matters',
						'label' => 'Why It Matters',
						'name'  => 'why_it_matters',
						'type'  => 'textarea',
						'rows'  => 3,
					),
				),
			),
			array(
				'key'           => 'field_dn_market_mood',
				'label'         => 'Market Mood',
				'name'          => 'market_mood',
				'type'          => 'select',
				'choices'       => array(
					'risk_on'      => 'Risk-On',
					'risk_off'     => 'Risk-Off',
					'mixed'        => 'Mixed',
					'wait_and_see' => 'Wait-and-See',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'daily-news',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// White Paper Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_white_paper',
		'title'    => 'White Paper Fields',
		'fields'   => array(
			array(
				'key'           => 'field_wp_is_gated',
				'label'         => 'Is Gated',
				'name'          => 'is_gated',
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
			),
			array(
				'key'               => 'field_wp_gate_form_id',
				'label'             => 'Gate Form ID',
				'name'              => 'gate_form_id',
				'type'              => 'text',
				'instructions'      => 'Form integration ID for the lead capture gate.',
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_wp_is_gated',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			),
			array(
				'key'           => 'field_wp_download_pdf',
				'label'         => 'Download PDF',
				'name'          => 'download_pdf',
				'type'          => 'file',
				'return_format' => 'array',
				'mime_types'    => 'pdf',
			),
			array(
				'key'   => 'field_wp_page_count',
				'label' => 'Page Count',
				'name'  => 'page_count',
				'type'  => 'number',
			),
			array(
				'key'           => 'field_wp_target_audience',
				'label'         => 'Target Audience',
				'name'          => 'target_audience',
				'type'          => 'select',
				'choices'       => array(
					'financial_advisors' => 'Financial Advisors',
					'rias'               => 'RIAs',
					'institutional'      => 'Institutional',
					'all'                => 'All',
				),
				'default_value' => 'all',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_wp_ce_credit_eligible',
				'label'         => 'CE Credit Eligible',
				'name'          => 'ce_credit_eligible',
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
			),
			array(
				'key'               => 'field_wp_ce_credit_hours',
				'label'             => 'CE Credit Hours',
				'name'              => 'ce_credit_hours',
				'type'              => 'number',
				'step'              => '0.5',
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_wp_ce_credit_eligible',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			),
			array(
				'key'   => 'field_wp_abstract',
				'label' => 'Abstract',
				'name'  => 'abstract',
				'type'  => 'textarea',
				'rows'  => 6,
			),
			array(
				'key'        => 'field_wp_table_of_contents',
				'label'      => 'Table of Contents',
				'name'       => 'table_of_contents',
				'type'       => 'repeater',
				'layout'     => 'table',
				'min'        => 0,
				'max'        => 0,
				'sub_fields' => array(
					array(
						'key'   => 'field_wp_toc_section_title',
						'label' => 'Section Title',
						'name'  => 'section_title',
						'type'  => 'text',
					),
					array(
						'key'   => 'field_wp_toc_page_number',
						'label' => 'Page Number',
						'name'  => 'page_number',
						'type'  => 'number',
					),
				),
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'white-paper',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );

	// -----------------------------------------------------------------
	// Cultural Update Fields.
	// -----------------------------------------------------------------
	acf_add_local_field_group( array(
		'key'      => 'group_mw_cultural_update',
		'title'    => 'Cultural Update Fields',
		'fields'   => array(
			array(
				'key'           => 'field_cu_update_type',
				'label'         => 'Update Type',
				'name'          => 'update_type',
				'type'          => 'select',
				'choices'       => array(
					'art_world_news'  => 'Art World News',
					'company_news'    => 'Company News',
					'artist_spotlight' => 'Artist Spotlight',
					'event_coverage'  => 'Event Coverage',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'            => 'field_cu_event_date',
				'label'          => 'Event Date',
				'name'           => 'event_date',
				'type'           => 'date_picker',
				'display_format' => 'F j, Y',
				'return_format'  => 'Y-m-d',
				'required'       => 0,
			),
			array(
				'key'      => 'field_cu_event_location',
				'label'    => 'Event Location',
				'name'     => 'event_location',
				'type'     => 'text',
				'required' => 0,
			),
			array(
				'key'           => 'field_cu_related_artists',
				'label'         => 'Related Artists',
				'name'          => 'related_artists',
				'type'          => 'relationship',
				'post_type'     => array( 'artist-dossier' ),
				'filters'       => array( 'search' ),
				'return_format' => 'object',
				'min'           => 0,
				'max'           => 0,
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'cultural-update',
				),
			),
		),
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
		'show_in_rest'          => 1,
	) );
}
add_action( 'acf/init', 'mw_register_acf_field_groups' );
