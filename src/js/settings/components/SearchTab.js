/**
 * Search settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	RangeControl,
	Button,
} from '@wordpress/components';

export default function SearchTab( {
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
				title={ __( 'Search Features', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Advanced Search',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_advanced_search }
						onChange={ ( v ) =>
							updateSetting( 'enable_advanced_search', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable AJAX Search',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_ajax_search }
						onChange={ ( v ) =>
							updateSetting( 'enable_ajax_search', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Date Range Filter',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_date_range }
						onChange={ ( v ) =>
							updateSetting( 'enable_date_range', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Location Filter',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_location_filter }
						onChange={ ( v ) =>
							updateSetting( 'enable_location_filter', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Show Result Count',
							'hk-funeral-notices'
						) }
						checked={ settings.show_search_count }
						onChange={ ( v ) =>
							updateSetting( 'show_search_count', v )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Search Behaviour', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl
						label={ __(
							'Search Placeholder',
							'hk-funeral-notices'
						) }
						value={ settings.search_placeholder }
						onChange={ ( v ) =>
							updateSetting( 'search_placeholder', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __(
							'Minimum Search Length',
							'hk-funeral-notices'
						) }
						value={ settings.min_search_length }
						onChange={ ( v ) =>
							updateSetting( 'min_search_length', v )
						}
						min={ 1 }
						max={ 10 }
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __(
							'Search Delay (ms)',
							'hk-funeral-notices'
						) }
						value={ settings.search_delay }
						onChange={ ( v ) =>
							updateSetting( 'search_delay', v )
						}
						min={ 0 }
						max={ 2000 }
						step={ 100 }
					/>
				</PanelRow>
			</PanelBody>

			<Button variant="primary" onClick={ saveSettings } isBusy={ isSaving }>
				{ __( 'Save Settings', 'hk-funeral-notices' ) }
			</Button>
		</>
	);
}
