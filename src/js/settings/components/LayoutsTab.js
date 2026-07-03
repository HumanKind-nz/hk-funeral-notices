/**
 * Layouts settings tab.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
	RangeControl,
	Button,
} from '@wordpress/components';

export default function LayoutsTab( {
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
				title={ __( 'Layout Defaults', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<SelectControl
						label={ __(
							'Default Archive Layout',
							'hk-funeral-notices'
						) }
						value={ settings.default_archive_layout }
						options={ [
							{ label: 'Modern', value: 'modern' },
							{ label: 'Elegant', value: 'elegant' },
							{ label: 'Minimal', value: 'minimal' },
							{ label: 'Firehawk', value: 'firehawk' },
							{ label: 'Current (Legacy)', value: 'current' },
						] }
						onChange={ ( v ) =>
							updateSetting( 'default_archive_layout', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __(
							'Default Single Layout',
							'hk-funeral-notices'
						) }
						value={ settings.default_single_layout }
						options={ [
							{ label: 'Modern', value: 'modern' },
							{ label: 'Elegant', value: 'elegant' },
							{ label: 'Minimal', value: 'minimal' },
							{ label: 'Firehawk', value: 'firehawk' },
							{ label: 'Current (Legacy)', value: 'current' },
						] }
						onChange={ ( v ) =>
							updateSetting( 'default_single_layout', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __(
							'Default Card Style',
							'hk-funeral-notices'
						) }
						value={ settings.default_card_style }
						options={ [
							{ label: 'Standard', value: 'standard' },
							{ label: 'Elevated', value: 'elevated' },
							{ label: 'Outlined', value: 'outlined' },
							{ label: 'Minimal', value: 'minimal' },
						] }
						onChange={ ( v ) =>
							updateSetting( 'default_card_style', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						label={ __(
							'Image Aspect Ratio',
							'hk-funeral-notices'
						) }
						value={ settings.image_aspect_ratio }
						options={ [
							{ label: '4:3', value: '4:3' },
							{ label: '16:9', value: '16:9' },
							{ label: '1:1 (Square)', value: '1:1' },
							{ label: '3:2', value: '3:2' },
						] }
						onChange={ ( v ) =>
							updateSetting( 'image_aspect_ratio', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<RangeControl
						label={ __(
							'Card Spacing (px)',
							'hk-funeral-notices'
						) }
						value={ settings.card_spacing }
						onChange={ ( v ) =>
							updateSetting( 'card_spacing', v )
						}
						min={ 0 }
						max={ 60 }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Effects', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Archive Templates',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_archive_templates }
						onChange={ ( v ) =>
							updateSetting( 'enable_archive_templates', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Hover Effects',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_hover_effects }
						onChange={ ( v ) =>
							updateSetting( 'enable_hover_effects', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Enable Animations',
							'hk-funeral-notices'
						) }
						checked={ settings.enable_animations }
						onChange={ ( v ) =>
							updateSetting( 'enable_animations', v )
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
