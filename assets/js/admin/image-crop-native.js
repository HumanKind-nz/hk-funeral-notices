/**
 * Funeral Notice Image Crop Tool — native WordPress media cropper (Prototype A)
 *
 * Uses the core wp.media Cropper state (the same flow as setting a Site Icon):
 * pick or upload the photo, then crop it to 4:3 inside the media modal.
 * Crop coordinates are posted to the plugin's own AJAX endpoint, which
 * generates the hkfn-grid-crop rendition. The original image is untouched
 * and stays as the featured image (full photo shows on the notice page).
 *
 * @since 3.0.0
 */

(function() {
	'use strict';

	if (typeof wp === 'undefined' || !wp.media) {
		return;
	}

	const cfg = window.hkfnCrop || {};
	const GRID_W = parseInt(cfg.gridWidth, 10) || 800;
	const GRID_H = parseInt(cfg.gridHeight, 10) || 600;
	const RATIO = GRID_W / GRID_H;

	/**
	 * Largest centred 4:3 selection for an image, in natural coordinates.
	 */
	function defaultSelection(width, height) {
		let selW = width;
		let selH = Math.round(width / RATIO);
		if (selH > height) {
			selH = height;
			selW = Math.round(height * RATIO);
		}
		const x1 = Math.round((width - selW) / 2);
		const y1 = Math.round((height - selH) / 2);
		return { x1: x1, y1: y1, x2: x1 + selW, y2: y1 + selH, width: selW, height: selH };
	}

	/**
	 * imgAreaSelect options for the core cropper state.
	 * imageWidth/imageHeight make the returned coordinates natural-image based.
	 */
	function imgSelectOptions(attachment, controller) {
		const realW = attachment.get('width');
		const realH = attachment.get('height');
		const sel = defaultSelection(realW, realH);

		controller.set('canSkipCrop', false);

		return {
			handles: true,
			keys: true,
			instance: true,
			persistent: true,
			imageWidth: realW,
			imageHeight: realH,
			minWidth: Math.min(GRID_W, realW) / 4,
			minHeight: Math.min(GRID_H, realH) / 4,
			aspectRatio: GRID_W + ':' + GRID_H,
			x1: sel.x1,
			y1: sel.y1,
			x2: sel.x2,
			y2: sel.y2
		};
	}

	function showStatus(message, isError, cropUrl) {
		const holder = document.getElementById('hkfn-crop-status');
		if (!holder) {
			return;
		}
		holder.innerHTML = '';

		const note = document.createElement('p');
		note.textContent = message;
		note.style.margin = '8px 0 4px';
		note.style.fontWeight = '600';
		note.style.color = isError ? '#b32d2e' : '#00722e';
		holder.appendChild(note);

		if (cropUrl) {
			const img = document.createElement('img');
			img.src = cropUrl + '?t=' + Date.now();
			img.alt = 'Grid crop preview';
			img.style.maxWidth = '200px';
			img.style.border = '1px solid #c3c4c7';
			img.style.borderRadius = '4px';
			holder.appendChild(img);
		}
	}

	function openCropFrame() {
		const frame = wp.media({
			button: {
				text: 'Select and crop',
				close: false
			},
			states: [
				new wp.media.controller.Library({
					title: "Select or upload the person's photo",
					library: wp.media.query({ type: 'image' }),
					multiple: false,
					date: false,
					priority: 20,
					suggestedWidth: GRID_W,
					suggestedHeight: GRID_H
				}),
				new wp.media.controller.Cropper({
					imgSelectOptions: imgSelectOptions
				})
			]
		});

		frame.on('select', function() {
			frame.setState('cropper');
		});

		// Replace the core crop action (which creates a new attachment) with a
		// call to the plugin endpoint that generates the grid rendition instead.
		frame.states.get('cropper').doCrop = function(attachment) {
			let cd = attachment.get('cropDetails');
			if (!cd || !cd.width) {
				cd = defaultSelection(attachment.get('width'), attachment.get('height'));
			}

			// Make the chosen photo the featured image (full version, notice page).
			if (wp.media.featuredImage) {
				const current = parseInt(wp.media.featuredImage.get(), 10);
				if (current !== attachment.get('id')) {
					wp.media.featuredImage.set(attachment.get('id'));
				}
			}

			return wp.ajax.post('hkfn_save_crop_coordinates', {
				nonce: cfg.nonce,
				attachment_id: attachment.get('id'),
				src_x: Math.round(cd.x1),
				src_y: Math.round(cd.y1),
				src_w: Math.round(cd.width),
				src_h: Math.round(cd.height),
				zoom_level: 100
			});
		};

		frame.on('cropped', function(result) {
			showStatus('Grid crop saved.', false, result && result.crop_url ? result.crop_url : '');
			frame.close();
		});

		frame.on('skippedcrop', function() {
			frame.close();
		});

		frame.open();
	}

	function insertButton() {
		const field = document.querySelector('.acf-field[data-key="field_hkfn_person_image"]')
			|| document.getElementById('postimagediv');

		if (!field || document.getElementById('hkfn-crop-native-btn')) {
			return;
		}

		const wrap = document.createElement('div');
		wrap.style.marginTop = '10px';

		const button = document.createElement('button');
		button.type = 'button';
		button.id = 'hkfn-crop-native-btn';
		button.className = 'button button-secondary';
		button.textContent = 'Select & crop photo…';
		button.addEventListener('click', function(e) {
			e.preventDefault();
			openCropFrame();
		});

		const status = document.createElement('div');
		status.id = 'hkfn-crop-status';

		wrap.appendChild(button);
		wrap.appendChild(status);
		field.appendChild(wrap);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', insertButton);
	} else {
		insertButton();
	}
})();
