/**
 * Licence settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	TextControl,
	Button,
} from '@wordpress/components';

export default function LicenceTab( {
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
				title={ __( 'Premium Licence', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<TextControl
						label={ __( 'Licence Key', 'hk-funeral-notices' ) }
						value={ settings.license_key }
						onChange={ ( v ) =>
							updateSetting( 'license_key', v )
						}
						help={ __(
							'Enter your premium licence key to unlock video hosting features.',
							'hk-funeral-notices'
						) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Video Settings', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<TextControl
						label={ __(
							'Max File Size (MB)',
							'hk-funeral-notices'
						) }
						value={ String( settings.max_file_size_mb ) }
						onChange={ ( v ) =>
							updateSetting(
								'max_file_size_mb',
								parseInt( v, 10 ) || 500
							)
						}
						type="number"
					/>
				</PanelRow>
			</PanelBody>

			<Button variant="primary" onClick={ saveSettings } isBusy={ isSaving }>
				{ __( 'Save Settings', 'hk-funeral-notices' ) }
			</Button>
		</>
	);
}
