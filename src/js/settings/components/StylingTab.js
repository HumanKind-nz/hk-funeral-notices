/**
 * Styling settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextareaControl,
	Button,
} from '@wordpress/components';

export default function StylingTab( {
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
				title={ __( 'Accessibility', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Dark Mode',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_dark_mode }
						onChange={ ( v ) =>
							updateSetting( 'enable_dark_mode', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable High Contrast',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_high_contrast }
						onChange={ ( v ) =>
							updateSetting( 'enable_high_contrast', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Reduced Motion',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_reduced_motion }
						onChange={ ( v ) =>
							updateSetting( 'enable_reduced_motion', v )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Performance', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'CSS Optimisation',
							'hk-funeral-notices'
						) }
						checked={ settings.css_optimization }
						onChange={ ( v ) =>
							updateSetting( 'css_optimization', v )
						}
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Custom CSS', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Custom CSS',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_custom_css }
						onChange={ ( v ) =>
							updateSetting( 'enable_custom_css', v )
						}
					/>
				</PanelRow>
				{ settings.enable_custom_css && (
					<PanelRow>
						<TextareaControl
							label={ __( 'Custom CSS', 'hk-funeral-notices' ) }
							value={ settings.custom_css }
							onChange={ ( v ) =>
								updateSetting( 'custom_css', v )
							}
							rows={ 10 }
							help={ __(
								'Add custom CSS rules for funeral notice styling.',
								'hk-funeral-notices'
							) }
						/>
					</PanelRow>
				) }
			</PanelBody>

			<Button variant="primary" onClick={ saveSettings } isBusy={ isSaving }>
				{ __( 'Save Settings', 'hk-funeral-notices' ) }
			</Button>
		</>
	);
}
