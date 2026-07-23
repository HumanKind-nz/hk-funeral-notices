/**
 * Custom hook for managing plugin settings via the WordPress REST API.
 *
 * Reads and writes the hkfn_settings option at /wp/v2/settings.
 *
 * @package hk-funeral-notices
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const OPTION_KEY = 'hkfn_settings';

export default function useSettings() {
	const [ settings, setSettings ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Load settings on mount.
	useEffect( () => {
		apiFetch( { path: '/wp/v2/settings' } ).then( ( response ) => {
			setSettings( response[ OPTION_KEY ] || {} );
		} );
	}, [] );

	/**
	 * Persist current settings to the database.
	 */
	const saveSettings = async () => {
		setIsSaving( true );
		setNotice( null );

		try {
			const response = await apiFetch( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: { [ OPTION_KEY ]: settings },
			} );

			setSettings( response[ OPTION_KEY ] || settings );
			setNotice( {
				status: 'success',
				message: 'Settings saved successfully.',
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message: error.message || 'Failed to save settings.',
			} );
		}

		setIsSaving( false );
	};

	return { settings, setSettings, saveSettings, isSaving, notice, setNotice };
}
