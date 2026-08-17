/**
 * Video settings tab.
 *
 * Upload limits and encoding options. Bunny credentials are deliberately not
 * editable here: they come from wp-config constants so keys stay out of the
 * database and out of anything exported with the site. This tab reports
 * whether they resolved, which is the question people actually need answered.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	RangeControl,
	SelectControl,
	Notice,
	ExternalLink,
} from '@wordpress/components';

export default function VideoTab( { settings, setSettings } ) {
	const updateSetting = ( key, value ) => {
		setSettings( { ...settings, [ key ]: value } );
	};

	const video = ( window.hkfnPlugin && window.hkfnPlugin.video ) || {};
	const configured = !! video.configured;
	const missing = video.missing || [];

	return (
		<>
			<PanelBody
				title={ __( 'Bunny Stream Status', 'hk-funeral-notices' ) }
				initialOpen
			>
				{ configured ? (
					<Notice status="success" isDismissible={ false }>
						{ __(
							'Video hosting is active.',
							'hk-funeral-notices'
						) }
						{ video.libraryId
							? ` ${ __( 'Library', 'hk-funeral-notices' ) }: ${
									video.libraryId
							  }`
							: '' }
					</Notice>
				) : (
					<Notice status="warning" isDismissible={ false }>
						<p>
							{ __(
								'Video hosting is not set up.',
								'hk-funeral-notices'
							) }
							{ missing.length
								? ` ${ __(
										'Missing:',
										'hk-funeral-notices'
								  ) } ${ missing.join( ', ' ) }.`
								: '' }
						</p>
						<p>
							{ __(
								'Memorial videos are hosted on your own Bunny Stream account, billed to you by Bunny. Add your credentials to wp-config.php:',
								'hk-funeral-notices'
							) }
						</p>
						<pre style={ { whiteSpace: 'pre-wrap' } }>
							{
								"define('HKFN_VIDEO_LIBRARY_ID', 'your-library-id');\ndefine('HKFN_VIDEO_API_KEY', 'your-api-key');\ndefine('HKFN_VIDEO_CDN_HOSTNAME', 'your-zone.b-cdn.net');"
							}
						</pre>
						<p>
							<ExternalLink href="https://dash.bunny.net/stream">
								{ __(
									'Open the Bunny Stream dashboard',
									'hk-funeral-notices'
								) }
							</ExternalLink>
						</p>
					</Notice>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Upload Limits', 'hk-funeral-notices' ) }
				initialOpen
			>
				<PanelRow>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __(
							'Maximum file size (MB)',
							'hk-funeral-notices'
						) }
						help={ __(
							'Larger files take longer to encode. Uploads typically take up to 10 minutes to appear on the notice.',
							'hk-funeral-notices'
						) }
						value={ settings.max_file_size_mb }
						onChange={ ( v ) =>
							updateSetting( 'max_file_size_mb', v )
						}
						min={ 50 }
						max={ 2000 }
						step={ 50 }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody
				title={ __( 'Encoding', 'hk-funeral-notices' ) }
				initialOpen={ false }
			>
				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Quality preset', 'hk-funeral-notices' ) }
						value={ settings.quality_preset }
						options={ [
							{
								label: __( 'Fast', 'hk-funeral-notices' ),
								value: 'fast',
							},
							{
								label: __( 'Balanced', 'hk-funeral-notices' ),
								value: 'balanced',
							},
							{
								label: __(
									'High quality',
									'hk-funeral-notices'
								),
								value: 'high_quality',
							},
						] }
						onChange={ ( v ) =>
							updateSetting( 'quality_preset', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Generate thumbnails',
							'hk-funeral-notices'
						) }
						checked={ !! settings.enable_thumbnails }
						onChange={ ( v ) =>
							updateSetting( 'enable_thumbnails', v )
						}
					/>
				</PanelRow>
				<PanelRow>
					<ToggleControl
						label={ __(
							'Show upload progress',
							'hk-funeral-notices'
						) }
						checked={ !! settings.enable_progress_tracking }
						onChange={ ( v ) =>
							updateSetting( 'enable_progress_tracking', v )
						}
					/>
				</PanelRow>
			</PanelBody>
		</>
	);
}
