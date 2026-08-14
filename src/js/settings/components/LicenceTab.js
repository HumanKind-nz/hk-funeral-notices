/**
 * Licence settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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

	// The key as it was when the tab opened — a saved key is shown masked
	// and read-only until "Change licence key" is clicked, so it reads as
	// stored rather than as something waiting to be typed over.
	const [ initialKey ] = useState( settings.license_key || '' );
	const [ replacing, setReplacing ] = useState( false );
	const showMasked = initialKey !== '' && ! replacing;
	const maskedKey =
		initialKey.length > 8
			? initialKey.slice( 0, 4 ) +
			  '••••••••••••••••' +
			  initialKey.slice( -4 )
			: '••••••••';

	return (
		<>
			<PanelBody
				title={ __( 'Premium Licence', 'hk-funeral-notices' ) }
				initialOpen
			>
				{ showMasked ? (
					<>
						<PanelRow>
							<TextControl
								label={ __(
									'Licence Key',
									'hk-funeral-notices'
								) }
								value={ maskedKey }
								onChange={ () => {} }
								disabled
								help={ __(
									'Licence key saved. Video hosting features are unlocked.',
									'hk-funeral-notices'
								) }
							/>
						</PanelRow>
						<PanelRow>
							<Button
								variant="secondary"
								onClick={ () => {
									updateSetting( 'license_key', '' );
									setReplacing( true );
								} }
							>
								{ __(
									'Change licence key',
									'hk-funeral-notices'
								) }
							</Button>
						</PanelRow>
					</>
				) : (
					<>
						<PanelRow>
							<TextControl
								label={ __(
									'Licence Key',
									'hk-funeral-notices'
								) }
								value={ settings.license_key }
								onChange={ ( v ) =>
									updateSetting( 'license_key', v )
								}
								placeholder={
									replacing
										? __(
												'Paste the new licence key',
												'hk-funeral-notices'
										  )
										: undefined
								}
								help={ __(
									'Enter your premium licence key, then Save Settings. It activates automatically.',
									'hk-funeral-notices'
								) }
							/>
						</PanelRow>
						{ replacing && (
							<PanelRow>
								<Button
									variant="tertiary"
									onClick={ () => {
										updateSetting(
											'license_key',
											initialKey
										);
										setReplacing( false );
									} }
								>
									{ __(
										'Keep existing key',
										'hk-funeral-notices'
									) }
								</Button>
							</PanelRow>
						) }
					</>
				) }
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

			<div style={ { marginTop: '24px', marginBottom: '16px' } }>
				<Button
					variant="primary"
					onClick={ saveSettings }
					isBusy={ isSaving }
					disabled={ isSaving }
				>
					{ __( 'Save Settings', 'hk-funeral-notices' ) }
				</Button>
			</div>
		</>
	);
}
