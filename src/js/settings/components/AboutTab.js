/**
 * About tab — read-only plugin information.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	ExternalLink,
} from '@wordpress/components';

export default function AboutTab() {
	/* global hkfnPlugin */
	const version = window.hkfnPlugin?.version || '3.0.0';
	const iconUrl = window.hkfnPlugin?.iconUrl || '';

	return (
		<Card>
			<CardHeader>
				<div style={ { display: 'flex', alignItems: 'center', gap: '12px' } }>
					{ iconUrl && (
						<img
							src={ iconUrl }
							alt="HumanKind Funeral Notices"
							style={ { width: '48px', height: '48px' } }
						/>
					) }
					<div>
						<h2 style={ { margin: 0 } }>
							{ __(
								'HumanKind Funeral Notices',
								'hk-funeral-notices'
							) }
						</h2>
						<p style={ { margin: 0, color: '#757575' } }>
							{ __( 'Version', 'hk-funeral-notices' ) }{ ' ' }
							{ version }
						</p>
					</div>
				</div>
			</CardHeader>
			<CardBody>
				<p>
					{ __(
						'Professional funeral notice management with modern responsive layouts, advanced search, and comprehensive styling controls for funeral homes.',
						'hk-funeral-notices'
					) }
				</p>
				<p>
					<ExternalLink href="https://github.com/HumanKind-nz/hk-funeral-notices">
						{ __( 'GitHub Repository', 'hk-funeral-notices' ) }
					</ExternalLink>
				</p>
				<p>
					<ExternalLink href="https://humankindwebsites.com/plugins/funeral-notices/">
						{ __( 'Plugin Documentation', 'hk-funeral-notices' ) }
					</ExternalLink>
				</p>
				<p>
					{ __( 'Support:', 'hk-funeral-notices' ) }{ ' ' }
					<ExternalLink href="mailto:support@weave.co.nz">
						support@weave.co.nz
					</ExternalLink>
				</p>
			</CardBody>
		</Card>
	);
}
