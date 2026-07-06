/**
 * Funeral Notice Image Crop Tool — Cropper.js UI (Prototype B, iteration 2)
 *
 * Upload-first flow for funeral home staff who are not WordPress-savvy:
 * the "Upload photo" button opens the computer's file picker directly (no
 * media library screen), uploads via the REST media endpoint, sets the
 * photo as the featured image, and drops straight into the crop modal.
 * A persistent preview of the current grid crop shows under the field so
 * staff always see what the grid/list pages will display.
 *
 * Cropper.js reports crop data in natural-image coordinates regardless of
 * zoom, so the server receives final coordinates (zoom_level=100) and the
 * legacy zoom math never runs. The original image stays untouched as the
 * featured image (full photo on the notice page); the crop only produces
 * the hkfn-grid-crop rendition.
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
	let ui = {};

	function getThumbnailId() {
		const el = document.getElementById('_thumbnail_id');
		const id = el ? parseInt(el.value, 10) : 0;
		return id > 0 ? id : 0;
	}

	/* ---------------------------------------------------------------- *
	 * Persistent UI under the person image field
	 * ---------------------------------------------------------------- */

	function setStatus(message, isError) {
		ui.status.textContent = message || '';
		ui.status.style.color = isError ? '#b32d2e' : '#00722e';
	}

	function setPreview(cropUrl) {
		ui.preview.innerHTML = '';
		if (!cropUrl) {
			return;
		}
		const label = document.createElement('p');
		label.textContent = 'Current grid image (shown on grid and list pages):';
		label.style.margin = '10px 0 4px';
		label.style.fontWeight = '600';

		const img = document.createElement('img');
		img.src = cropUrl;
		img.alt = 'Current grid crop';
		img.style.maxWidth = '220px';
		img.style.border = '1px solid #c3c4c7';
		img.style.borderRadius = '4px';
		img.style.display = 'block';

		ui.preview.appendChild(label);
		ui.preview.appendChild(img);
	}

	function refreshButtons() {
		const hasPhoto = getThumbnailId() > 0;
		ui.recropBtn.style.display = hasPhoto ? '' : 'none';
		ui.uploadBtn.textContent = hasPhoto ? 'Replace photo…' : 'Upload photo…';
	}

	function insertUI() {
		const field = document.querySelector('.acf-field[data-key="field_hkfn_person_image"]')
			|| document.getElementById('postimagediv');

		if (!field || document.getElementById('hkfn-crop-ui')) {
			return;
		}

		const wrap = document.createElement('div');
		wrap.id = 'hkfn-crop-ui';
		wrap.style.marginTop = '10px';

		const fileInput = document.createElement('input');
		fileInput.type = 'file';
		fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
		fileInput.style.display = 'none';
		fileInput.addEventListener('change', function() {
			if (fileInput.files && fileInput.files[0]) {
				uploadFile(fileInput.files[0]);
				fileInput.value = '';
			}
		});

		const uploadBtn = document.createElement('button');
		uploadBtn.type = 'button';
		uploadBtn.className = 'button button-primary';
		uploadBtn.textContent = 'Upload photo…';
		uploadBtn.addEventListener('click', function(e) {
			e.preventDefault();
			fileInput.click();
		});

		const recropBtn = document.createElement('button');
		recropBtn.type = 'button';
		recropBtn.className = 'button';
		recropBtn.style.marginLeft = '8px';
		recropBtn.textContent = 'Re-crop photo';
		recropBtn.addEventListener('click', function(e) {
			e.preventDefault();
			const id = getThumbnailId();
			if (id) {
				openCropModal(id);
			}
		});

		const libraryLink = document.createElement('a');
		libraryLink.href = '#';
		libraryLink.textContent = 'choose from media library';
		libraryLink.style.marginLeft = '12px';
		libraryLink.style.fontSize = '12px';
		libraryLink.addEventListener('click', function(e) {
			e.preventDefault();
			openLibraryPicker();
		});

		const status = document.createElement('p');
		status.style.margin = '8px 0 0';
		status.style.fontWeight = '600';

		const preview = document.createElement('div');

		wrap.appendChild(fileInput);
		wrap.appendChild(uploadBtn);
		wrap.appendChild(recropBtn);
		wrap.appendChild(libraryLink);
		wrap.appendChild(status);
		wrap.appendChild(preview);
		field.appendChild(wrap);

		ui = { uploadBtn: uploadBtn, recropBtn: recropBtn, status: status, preview: preview };
		refreshButtons();

		if (cfg.currentCropUrl) {
			setPreview(cfg.currentCropUrl);
		}
	}

	/* ---------------------------------------------------------------- *
	 * Direct upload via the REST media endpoint (no media library UI)
	 * ---------------------------------------------------------------- */

	function uploadFile(file) {
		if (!/^image\//.test(file.type)) {
			window.alert('Please choose an image file (JPG, PNG, WebP or GIF).');
			return;
		}

		ui.uploadBtn.disabled = true;
		setStatus('Uploading photo…', false);

		const body = new FormData();
		body.append('file', file);

		fetch(cfg.restMediaUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': cfg.restNonce },
			body: body
		})
			.then(function(response) {
				return response.json().then(function(json) {
					return { ok: response.ok, json: json };
				});
			})
			.then(function(result) {
				ui.uploadBtn.disabled = false;
				if (!result.ok || !result.json || !result.json.id) {
					const msg = result.json && result.json.message ? result.json.message : 'Upload failed.';
					setStatus(msg, true);
					return;
				}
				setStatus('', false);

				// Make it the featured image (full photo, notice page)
				if (wp.media && wp.media.featuredImage) {
					wp.media.featuredImage.set(result.json.id);
				}
				refreshButtons();

				// Straight into cropping
				openCropModal(result.json.id, result.json.source_url);
			})
			.catch(function() {
				ui.uploadBtn.disabled = false;
				setStatus('Upload failed. Please try again.', true);
			});
	}

	/**
	 * Fallback for the rare case a photo is already in the library.
	 */
	function openLibraryPicker() {
		const frame = wp.media({
			title: "Select the person's photo",
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
			refreshButtons();
			openCropModal(attachment.get('id'), attachment.get('url'));
		});

		frame.open();
	}

	/* ---------------------------------------------------------------- *
	 * Crop modal (Cropper.js)
	 * ---------------------------------------------------------------- */

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
					const url = json.data && json.data.crop_url ? json.data.crop_url : '';
					setStatus('Grid crop saved.', false);
					setPreview(url ? url + '?t=' + Date.now() : '');
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

	function openCropModal(attachmentId, knownUrl) {
		currentAttachmentId = attachmentId;

		if (knownUrl) {
			buildModal(knownUrl);
			return;
		}

		const attachment = wp.media.attachment(attachmentId);
		attachment.fetch().done(function() {
			const fullUrl = attachment.get('url');
			if (!fullUrl) {
				window.alert('Could not load the image.');
				return;
			}
			buildModal(fullUrl);
		}).fail(function() {
			window.alert('Could not load the image.');
		});
	}

	function buildModal(imageUrl) {
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
		img.src = imageUrl;

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
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', insertUI);
	} else {
		insertUI();
	}
})();
