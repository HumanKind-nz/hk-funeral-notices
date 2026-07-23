/**
 * Settings page entry point.
 *
 * Mounts the React settings app into the #hkfn-settings container
 * rendered by inc/settings-page.php (via Dashboard.php).
 *
 * @package hk-funeral-notices
 */

import { createRoot } from '@wordpress/element';
import SettingsApp from './components/SettingsApp';

const container = document.getElementById( 'hkfn-settings' );

if ( container ) {
	const root = createRoot( container );
	root.render( <SettingsApp /> );
}
