/**
 * Main settings app component with tabbed navigation.
 *
 * @package hk-funeral-notices
 */

import { __ } from '@wordpress/i18n';
import { TabPanel, Notice, Spinner } from '@wordpress/components';
import useSettings from '../hooks/useSettings';
import GeneralTab from './GeneralTab';
import LayoutsTab from './LayoutsTab';
import SearchTab from './SearchTab';
import StylingTab from './StylingTab';
import LicenceTab from './LicenceTab';
import AboutTab from './AboutTab';

const TABS = [
	{ name: 'general', title: __( 'General', 'hk-funeral-notices' ) },
	{ name: 'layouts', title: __( 'Layouts', 'hk-funeral-notices' ) },
	{ name: 'search', title: __( 'Search', 'hk-funeral-notices' ) },
	{ name: 'styling', title: __( 'Styling', 'hk-funeral-notices' ) },
	{ name: 'licence', title: __( 'Licence', 'hk-funeral-notices' ) },
	{ name: 'about', title: __( 'About', 'hk-funeral-notices' ) },
];

export default function SettingsApp() {
	const { settings, setSettings, saveSettings, isSaving, notice, setNotice } =
		useSettings();

	if ( ! settings ) {
		return <Spinner />;
	}

	const tabProps = { settings, setSettings, saveSettings, isSaving };

	return (
		<>
			<h1>{ __( 'HumanKind Funeral Notices', 'hk-funeral-notices' ) }</h1>

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
						case 'licence':
							return <LicenceTab { ...tabProps } />;
						case 'about':
							return <AboutTab />;
						default:
							return null;
					}
				} }
			</TabPanel>
		</>
	);
}
