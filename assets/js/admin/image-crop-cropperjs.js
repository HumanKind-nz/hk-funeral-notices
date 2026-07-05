/**
 * Funeral Notice Image Crop Tool — Cropper.js UI (Prototype B)
 *
 * Reuses the plugin's server-side crop endpoint from v2.4, but replaces the
 * hand-rolled crop UI (which had zoom coordinate bugs) with Cropper.js.
 * Cropper.js reports crop data in natural-image coordinates regardless of
 * zoom, so the server never has to do zoom math: it receives final
 * coordinates and crops the original with the WordPress image editor.
 *
 * The original image stays untouched as the featured image (full photo on
 * the notice page); the crop only produces the hkfn-grid-crop rendition.
 *
 * @since 3.0.0
 */

(function() {
	'use strict';

	const cfg = window.hkfnCrop || {};
	const GRID_W = parseInt(cfg.gridWidth, 10) || 800;
	const GRID_H = parseInt(cfg.gridHeight, 10) || 600;
	const RATIO = GRID_W / GRID_H;

	let cropper = null;
	let modal = null;
	let currentAttachmentId = 0;

	function getThumbnailId() {
		const el = document.getElementById('_thumbnail_id');
		const id = el ? parseInt(el.value, 10) : 0;
		return id > 0 ? id : 0;
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

	function destroyModal() {
		if (cropper) {
			cropper.destroy();
			cropper = null;
		}
		if (modal) {
			modal.remove();
			modal = null;
		}
		document.removeEventListener('keydown', onKeydown);
	}

	function onKeydown(e) {
		if (e.key === 'Escape') {
			destroyModal();
		}
	}

	function applyCrop() {
		if (!cropper || !currentAttachmentId) {
			return;
		}

		const data = cropper.getData(true); // natural-image coordinates
		const applyBtn = modal.querySelector('.hkfn-cropb-apply');
		applyBtn.disabled = true;
		applyBtn.textContent = 'Cropping…';

		const body = new FormData();
		body.append('action', 'hkfn_save_crop_coordinates');
		body.append('nonce', cfg.nonce);
		body.append('attachment_id', String(currentAttachmentId));
		body.append('src_x', String(Math.max(0, data.x)));
		body.append('src_y', String(Math.max(0, data.y)));
		body.append('src_w', String(data.width));
		body.append('src_h', String(data.height));
		body.append('zoom_level', '100');

		fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function(response) {
				return response.json();
			})
			.then(function(json) {
				if (json && json.success) {
					showStatus('Grid crop saved.', false, json.data && json.data.crop_url ? json.data.crop_url : '');
					destroyModal();
				} else {
					const msg = json && json.data && json.data.message ? json.data.message : 'Crop failed.';
					applyBtn.disabled = false;
					applyBtn.textContent = 'Apply crop';
					window.alert(msg);
				}
			})
			.catch(function() {
				applyBtn.disabled = false;
				applyBtn.textContent = 'Apply crop';
				window.alert('Crop request failed. Please try again.');
			});
	}

	function openCropModal(attachmentId) {
		currentAttachmentId = attachmentId;

		// Fetch the attachment to get the full-size URL.
		const attachment = wp.media.attachment(attachmentId);
		attachment.fetch().done(function() {
			const fullUrl = attachment.get('url');
			if (!fullUrl) {
				window.alert('Could not load the image.');
				return;
			}

			modal = document.createElement('div');
			modal.className = 'hkfn-cropb-overlay';
			modal.innerHTML =
				'<div class="hkfn-cropb-dialog" role="dialog" aria-label="Crop photo for grid">' +
					'<div class="hkfn-cropb-head">' +
						'<strong>Crop photo for grid cards (4:3)</strong>' +
						'<button type="button" class="hkfn-cropb-close" aria-label="Close">&times;</button>' +
					'</div>' +
					'<div class="hkfn-cropb-body"><img class="hkfn-cropb-img" alt="Photo to crop"></div>' +
					'<div class="hkfn-cropb-foot">' +
						'<span class="hkfn-cropb-zoom">' +
							'<button type="button" class="button hkfn-cropb-zoom-out" aria-label="Zoom out">&minus;</button>' +
							'<button type="button" class="button hkfn-cropb-zoom-in" aria-label="Zoom in">+</button>' +
							'<span class="hkfn-cropb-hint">Drag to reposition, scroll or pinch to zoom</span>' +
						'</span>' +
						'<span class="hkfn-cropb-actions">' +
							'<button type="button" class="button hkfn-cropb-cancel">Cancel</button>' +
							'<button type="button" class="button button-primary hkfn-cropb-apply">Apply crop</button>' +
						'</span>' +
					'</div>' +
				'</div>';
			document.body.appendChild(modal);

			const img = modal.querySelector('.hkfn-cropb-img');
			img.addEventListener('load', function() {
				cropper = new Cropper(img, {
					aspectRatio: RATIO,
					viewMode: 1,
					autoCropArea: 1,
					responsive: true,
					background: true,
					zoomOnWheel: true
				});
			});
			img.src = fullUrl;

			modal.querySelector('.hkfn-cropb-close').addEventListener('click', destroyModal);
			modal.querySelector('.hkfn-cropb-cancel').addEventListener('click', destroyModal);
			modal.querySelector('.hkfn-cropb-apply').addEventListener('click', applyCrop);
			modal.querySelector('.hkfn-cropb-zoom-in').addEventListener('click', function() {
				if (cropper) {
					cropper.zoom(0.1);
				}
			});
			modal.querySelector('.hkfn-cropb-zoom-out').addEventListener('click', function() {
				if (cropper) {
					cropper.zoom(-0.1);
				}
			});
			modal.addEventListener('click', function(e) {
				if (e.target === modal) {
					destroyModal();
				}
			});
			document.addEventListener('keydown', onKeydown);
		}).fail(function() {
			window.alert('Could not load the image.');
		});
	}

	/**
	 * If a photo is already set, crop it. Otherwise open the media library
	 * first, set the chosen photo as featured image, then crop it.
	 */
	function startCropFlow() {
		const existing = getThumbnailId();
		if (existing) {
			openCropModal(existing);
			return;
		}

		const frame = wp.media({
			title: "Select or upload the person's photo",
			library: { type: 'image' },
			multiple: false,
			button: { text: 'Use this photo' }
		});

		frame.on('select', function() {
			const attachment = frame.state().get('selection').first();
			if (!attachment) {
				return;
			}
			if (wp.media.featuredImage) {
				wp.media.featuredImage.set(attachment.get('id'));
			}
			openCropModal(attachment.get('id'));
		});

		frame.open();
	}

	function insertButton() {
		const field = document.querySelector('.acf-field[data-key="field_hkfn_person_image"]')
			|| document.getElementById('postimagediv');

		if (!field || document.getElementById('hkfn-crop-b-btn')) {
			return;
		}

		const wrap = document.createElement('div');
		wrap.style.marginTop = '10px';

		const button = document.createElement('button');
		button.type = 'button';
		button.id = 'hkfn-crop-b-btn';
		button.className = 'button button-secondary';
		button.textContent = 'Crop photo for grid…';
		button.addEventListener('click', function(e) {
			e.preventDefault();
			startCropFlow();
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
