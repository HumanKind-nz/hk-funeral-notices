/**
 * General settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
	TextControl,
	RangeControl,
	Button,
} from '@wordpress/components';

export default function GeneralTab( {
	settings,
	setSettings,
	saveSettings,
	isSaving,
} ) {
	const updateSetting = ( key, value ) => {
		setSettings( { ...settings, [ key ]: value } );
	};

	return (
		<>
			<PanelBody
				title={ __( 'Display Settings', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<SelectControl
						label={ __( 'Default Layout', 'hk-funeral-notices' ) }
						value={ settings.default_layout }
						options={ [
							{ label: 'Modern Memorial Grid', value: 'modern' },
							{ label: 'Elegant Funeral Grid', value: 'elegant' },
							{ label: 'Simple Memorial List', value: 'minimal' },
							{
								label: 'Firehawk Compatible',
								value: 'firehawk',
							},
						] }
						onChange={ ( v ) => updateSetting( 'default_layout', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __( 'Posts Per Page', 'hk-funeral-notices' ) }
						value={ settings.posts_per_page }
						onChange={ ( v ) => updateSetting( 'posts_per_page', v ) }
						min={ 1 }
						max={ 50 }
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __( 'Load More Posts', 'hk-funeral-notices' ) }
						value={ settings.load_more_posts }
						onChange={ ( v ) =>
							updateSetting( 'load_more_posts', v )
						}
						min={ 1 }
						max={ 50 }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Grid Columns', 'hk-funeral-notices' ) }
						value={ String( settings.columns ) }
						options={ [
							{ label: '2 Columns', value: '2' },
							{ label: '3 Columns', value: '3' },
							{ label: '4 Columns', value: '4' },
						] }
						onChange={ ( v ) =>
							updateSetting( 'columns', parseInt( v, 10 ) )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __( 'Show Search Form', 'hk-funeral-notices' ) }
						checked={ settings.show_search }
						onChange={ ( v ) => updateSetting( 'show_search', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Show Pagination',
							'hk-funeral-notices'
						) }
						checked={ settings.show_pagination }
						onChange={ ( v ) =>
							updateSetting( 'show_pagination', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Show Featured Images',
							'hk-funeral-notices'
						) }
						checked={ settings.show_featured_image }
						onChange={ ( v ) =>
							updateSetting( 'show_featured_image', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Image Size', 'hk-funeral-notices' ) }
						value={ settings.image_size }
						options={ [
							{ label: 'Thumbnail', value: 'thumbnail' },
							{ label: 'Medium', value: 'medium' },
							{ label: 'Large', value: 'large' },
							{ label: 'Full Size', value: 'full' },
						] }
						onChange={ ( v ) => updateSetting( 'image_size', v ) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Date & Time', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<SelectControl
						label={ __( 'Date Format', 'hk-funeral-notices' ) }
						value={ settings.date_format }
						options={ [
							{ label: 'January 1, 2025', value: 'F j, Y' },
							{ label: '1 January 2025', value: 'j F Y' },
							{ label: '2025-01-01', value: 'Y-m-d' },
							{ label: '01/01/2025', value: 'd/m/Y' },
						] }
						onChange={ ( v ) => updateSetting( 'date_format', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __( 'Time Format', 'hk-funeral-notices' ) }
						value={ settings.time_format }
						options={ [
							{ label: '2:30 pm', value: 'g:i a' },
							{ label: '14:30', value: 'G:i' },
							{ label: '02:30 PM', value: 'h:i A' },
						] }
						onChange={ ( v ) => updateSetting( 'time_format', v ) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Content', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl
						label={ __(
							'Default Memorial Header',
							'hk-funeral-notices'
						) }
						value={ settings.default_memorial_header }
						onChange={ ( v ) =>
							updateSetting( 'default_memorial_header', v )
						}
						help={ __(
							'Text above the person\'s name on single pages.',
							'hk-funeral-notices'
						) }
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __(
							'Excerpt Length (characters)',
							'hk-funeral-notices'
						) }
						value={ settings.excerpt_length }
						onChange={ ( v ) =>
							updateSetting( 'excerpt_length', v )
						}
						min={ 50 }
						max={ 500 }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Streaming Integration',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_streaming }
						onChange={ ( v ) =>
							updateSetting( 'enable_streaming', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __(
							'Tribute Form URL',
							'hk-funeral-notices'
						) }
						value={ settings.tribute_form_url }
						onChange={ ( v ) =>
							updateSetting( 'tribute_form_url', v )
						}
						help={ __(
							'Use {firstname}, {lastname}, {fullname} placeholders.',
							'hk-funeral-notices'
						) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'SEO', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable SEO Features',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_seo }
						onChange={ ( v ) => updateSetting( 'enable_seo', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Hide from Search Engines (noindex)',
							'hk-funeral-notices'
						) }
						checked={ settings.noindex_funeral_notices }
						onChange={ ( v ) =>
							updateSetting( 'noindex_funeral_notices', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __(
							'SEO Title Suffix',
							'hk-funeral-notices'
						) }
						value={ settings.seo_title_suffix }
						onChange={ ( v ) =>
							updateSetting( 'seo_title_suffix', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __(
							'Location/Business Name',
							'hk-funeral-notices'
						) }
						value={ settings.location_name }
						onChange={ ( v ) =>
							updateSetting( 'location_name', v )
						}
						help={ __(
							'Used in SEO titles. Falls back to site name.',
							'hk-funeral-notices'
						) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Advanced', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<RangeControl
						label={ __(
							'Cache Duration (seconds)',
							'hk-funeral-notices'
						) }
						value={ settings.cache_duration }
						onChange={ ( v ) =>
							updateSetting( 'cache_duration', v )
						}
						min={ 300 }
						max={ 86400 }
						step={ 300 }
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'URL Slug', 'hk-funeral-notices' ) }
						value={ settings.single_slug }
						onChange={ ( v ) => updateSetting( 'single_slug', v ) }
						help={ __(
							'After changing, go to Settings → Permalinks and save.',
							'hk-funeral-notices'
						) }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __(
							'Address Field Mode',
							'hk-funeral-notices'
						) }
						value={ settings.address_field_mode }
						options={ [
							{
								label: 'Auto-detect (Recommended)',
								value: 'auto',
							},
							{
								label: 'Force ACFE Pro',
								value: 'acfe',
							},
							{
								label: 'Force Native Fields',
								value: 'custom',
							},
						] }
						onChange={ ( v ) =>
							updateSetting( 'address_field_mode', v )
						}
					/>
				</PanelRow>
			</PanelBody>

			<Button variant="primary" onClick={ saveSettings } isBusy={ isSaving }>
				{ __( 'Save Settings', 'hk-funeral-notices' ) }
			</Button>
		</>
	);
}
