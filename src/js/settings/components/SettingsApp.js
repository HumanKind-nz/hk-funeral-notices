/**
 * Main settings app component with tabbed navigation.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import {
	TabPanel,
	Notice,
	Spinner,
	ExternalLink,
} from '@wordpress/components';
import useSettings from '../hooks/useSettings';
import GeneralTab from './GeneralTab';
import LayoutsTab from './LayoutsTab';
import SearchTab from './SearchTab';
import StylingTab from './StylingTab';
import VideoTab from './VideoTab';
import AboutTab from './AboutTab';

const TABS = [
	{ name: 'general', title: __( 'General', 'hk-funeral-notices' ) },
	{ name: 'layouts', title: __( 'Layouts', 'hk-funeral-notices' ) },
	{ name: 'search', title: __( 'Search', 'hk-funeral-notices' ) },
	{ name: 'styling', title: __( 'Styling', 'hk-funeral-notices' ) },
	{ name: 'video', title: __( 'Video', 'hk-funeral-notices' ) },
	{ name: 'about', title: __( 'About', 'hk-funeral-notices' ) },
];

export default function SettingsApp() {
	const { settings, setSettings, saveSettings, isSaving, notice, setNotice } =
		useSettings();

	if ( ! settings ) {
		return <Spinner />;
	}

	const tabProps = { settings, setSettings, saveSettings, isSaving };
	const iconUrl = window.hkfnPlugin?.iconUrl || '';

	return (
		<div style={ { maxWidth: '800px' } }>
			<div
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: '12px',
				} }
			>
				{ iconUrl && (
					<img
						src={ iconUrl }
						alt=""
						width={ 40 }
						height={ 40 }
						style={ { borderRadius: '4px' } }
					/>
				) }
				<h1>
					{ __( 'HumanKind Funeral Notices', 'hk-funeral-notices' ) }
				</h1>
			</div>

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<TabPanel tabs={ TABS }>
				{ ( tab ) => {
					switch ( tab.name ) {
						case 'general':
							return <GeneralTab { ...tabProps } />;
						case 'layouts':
							return <LayoutsTab { ...tabProps } />;
						case 'search':
							return <SearchTab { ...tabProps } />;
						case 'styling':
							return <StylingTab { ...tabProps } />;
						case 'video':
							return <VideoTab { ...tabProps } />;
						case 'about':
							return <AboutTab />;
						default:
							return null;
					}
				} }
			</TabPanel>

			<p
				style={ {
					marginTop: '24px',
					color: '#757575',
					fontSize: '13px',
				} }
			>
				{ __(
					'HumanKind is the funeral website and digital brand of',
					'hk-funeral-notices'
				) }{ ' ' }
				<ExternalLink href="https://weave.co.nz">
					Weave Digital Studio
				</ExternalLink>
			</p>
		</div>
	);
}
