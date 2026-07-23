/**
 * Social Share Functionality
 * Lightweight vanilla JavaScript for sharing funeral notices
 * @since 2.4.0
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const shareButtons = document.querySelectorAll('.hkfn-share-button');
        shareButtons.forEach(button => {
            button.addEventListener('click', handleShareClick);
        });

        // Close menu on outside click
        document.addEventListener('click', handleOutsideClick);

        // Close menu on Escape key
        document.addEventListener('keydown', handleEscapeKey);
    }

    /**
     * Handle share button click
     */
    function handleShareClick(e) {
        e.preventDefault();
        e.stopPropagation();

        const button = e.currentTarget;
        const shareData = {
            title: button.dataset.title || '',
            text: button.dataset.message || '',
            url: button.dataset.url || window.location.href
        };

        // Try Web Share API first (mobile devices)
        if (navigator.share) {
            navigator.share(shareData)
                .then(() => console.log('Share successful'))
                .catch((error) => {
                    // User cancelled or error occurred
                    if (error.name !== 'AbortError') {
                        console.log('Web Share failed, showing fallback menu');
                        showShareMenu(button, shareData);
                    }
                });
        } else {
            // Fallback to custom share menu
            showShareMenu(button, shareData);
        }
    }

    /**
     * Show fallback share menu
     */
    function showShareMenu(button, shareData) {
        // Close any existing menus
        closeAllShareMenus();

        // Create share menu
        const menu = document.createElement('div');
        menu.className = 'hkfn-share-menu';
        menu.setAttribute('role', 'menu');
        menu.setAttribute('aria-label', 'Share options');

        // Share options with SVG icons
        const options = [
            {
                name: 'Facebook',
                icon: 'facebook',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L258.2 544L258.2 398.2L205.4 398.2L205.4 320L258.2 320L258.2 286.3C258.2 199.2 297.6 158.8 383.2 158.8C399.4 158.8 427.4 162 438.9 165.2L438.9 236C432.9 235.4 422.4 235 409.3 235C367.3 235 351.1 250.9 351.1 292.2L351.1 320L434.7 320L420.3 398.2L351 398.2L351 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L160 96z"/></svg>',
                handler: () => shareFacebook(shareData)
            },
            {
                name: 'Email',
                icon: 'email',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M64 186.5L64 184L64.1 184C65.3 152.9 91 128 122.5 128L517.5 128C549 128 574.6 152.9 575.9 184L576 184L576 448C576 483.3 547.3 512 512 512L128 512C92.7 512 64 483.3 64 448L64 186.5zM544 239.6L367.3 369.1C339.1 389.7 300.8 389.7 272.7 369.1L96 239.6L96 448C96 465.7 110.3 480 128 480L512 480C529.7 480 544 465.7 544 448L544 239.6zM544 186.5C544 171.9 532.1 160 517.5 160L122.5 160C107.9 160 96 171.9 96 186.5C96 194.9 100 202.9 106.8 207.9L291.6 343.3C308.5 355.7 331.5 355.7 348.4 343.3L533.2 207.8C540 202.8 544 194.9 544 186.4z"/></svg>',
                handler: () => shareEmail(shareData)
            },
            {
                name: 'SMS',
                icon: 'sms',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M243.2 597.6L243.2 597.6L355.2 513.6C356.6 512.6 358.3 512 360 512L480 512C533 512 576 469 576 416L576 192C576 139 533 96 480 96L160 96C107 96 64 139 64 192L64 416C64 469 107 512 160 512L192 512L192 580C192 595.5 204.5 608 220 608C226.1 608 232 606 236.8 602.4L243.2 597.6zM224 512L224 504C224 490.7 213.3 480 200 480L160 480C124.7 480 96 451.3 96 416L96 192C96 156.7 124.7 128 160 128L480 128C515.3 128 544 156.7 544 192L544 416C544 451.3 515.3 480 480 480L360 480C351.3 480 342.9 482.8 336 488L224 572L224 512zM202.9 236.8C181 236.8 163.2 254.6 163.2 276.5C163.2 291.5 171.7 305.3 185.2 312L210.2 324.5C212.8 325.8 214.5 328.5 214.5 331.4C214.5 335.7 211 339.1 206.8 339.1L179.3 339.1C170.5 339.1 163.3 346.3 163.3 355.1C163.3 363.9 170.5 371.1 179.3 371.1L206.8 371.1C228.7 371.1 246.5 353.3 246.5 331.4C246.5 316.3 238 302.6 224.5 295.9L199.5 283.4C196.9 282.1 195.2 279.4 195.2 276.5C195.2 272.2 198.7 268.8 202.9 268.8L224 268.8C232.8 268.8 240 261.6 240 252.8C240 244 232.8 236.8 224 236.8L202.9 236.8zM393.6 276.5C393.6 291.5 402.1 305.3 415.6 312L440.6 324.5C443.2 325.8 444.9 328.5 444.9 331.4C444.9 335.7 441.4 339.1 437.2 339.1L409.7 339.1C400.9 339.1 393.7 346.3 393.7 355.1C393.7 363.9 400.9 371.1 409.7 371.1L437.2 371.1C459.1 371.1 476.9 353.3 476.9 331.4C476.9 316.3 468.4 302.6 454.9 295.9L429.9 283.4C427.3 282.1 425.6 279.4 425.6 276.5C425.6 272.2 429.1 268.8 433.3 268.8L454.4 268.8C463.2 268.8 470.4 261.6 470.4 252.8C470.4 244 463.2 236.8 454.4 236.8L433.3 236.8C411.4 236.8 393.6 254.6 393.6 276.5zM295.3 244.5C291.6 238.3 284.2 235.4 277.3 237.3C270.4 239.2 265.6 245.5 265.6 252.7L265.6 355.1C265.6 363.9 272.8 371.1 281.6 371.1C290.4 371.1 297.6 363.9 297.6 355.1L297.6 310.5L306.3 325C309.2 329.8 314.4 332.8 320 332.8C325.6 332.8 330.8 329.8 333.7 325L342.4 310.5L342.4 355.1C342.4 363.9 349.6 371.1 358.4 371.1C367.2 371.1 374.4 363.9 374.4 355.1L374.4 252.7C374.4 245.5 369.6 239.2 362.7 237.3C355.8 235.4 348.4 238.3 344.7 244.5L320 285.6L295.3 244.5z"/></svg>',
                handler: () => shareSMS(shareData)
            },
            {
                name: 'WhatsApp',
                icon: 'whatsapp',
                svg: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M476.9 161.1C435 119.1 379.2 96 319.9 96C197.5 96 97.9 195.6 97.9 318C97.9 357.1 108.1 395.3 127.5 429L96 544L213.7 513.1C246.1 530.8 282.6 540.1 319.8 540.1L319.9 540.1C442.2 540.1 544 440.5 544 318.1C544 258.8 518.8 203.1 476.9 161.1zM319.9 502.7C286.7 502.7 254.2 493.8 225.9 477L219.2 473L149.4 491.3L168 423.2L163.6 416.2C145.1 386.8 135.4 352.9 135.4 318C135.4 216.3 218.2 133.5 320 133.5C369.3 133.5 415.6 152.7 450.4 187.6C485.2 222.5 506.6 268.8 506.5 318.1C506.5 419.9 421.6 502.7 319.9 502.7zM421.1 364.5C415.6 361.7 388.3 348.3 383.2 346.5C378.1 344.6 374.4 343.7 370.7 349.3C367 354.9 356.4 367.3 353.1 371.1C349.9 374.8 346.6 375.3 341.1 372.5C308.5 356.2 287.1 343.4 265.6 306.5C259.9 296.7 271.3 297.4 281.9 276.2C283.7 272.5 282.8 269.3 281.4 266.5C280 263.7 268.9 236.4 264.3 225.3C259.8 214.5 255.2 216 251.8 215.8C248.6 215.6 244.9 215.6 241.2 215.6C237.5 215.6 231.5 217 226.4 222.5C221.3 228.1 207 241.5 207 268.8C207 296.1 226.9 322.5 229.6 326.2C232.4 329.9 268.7 385.9 324.4 410C359.6 425.2 373.4 426.5 391 423.9C401.7 422.3 423.8 410.5 428.4 397.5C433 384.5 433 373.4 431.6 371.1C430.3 368.6 426.6 367.2 421.1 364.5z"/></svg>',
                handler: () => shareWhatsApp(shareData)
            }
        ];

        // Build menu items
        options.forEach((option, index) => {
            const item = document.createElement('button');
            item.className = 'hkfn-share-menu-item';
            item.setAttribute('role', 'menuitem');
            item.setAttribute('tabindex', index === 0 ? '0' : '-1');

            // Create icon wrapper with SVG
            const iconWrapper = document.createElement('span');
            iconWrapper.className = `hkfn-share-icon hkfn-share-icon-${option.icon}`;
            iconWrapper.innerHTML = option.svg;

            // Create text node
            const textNode = document.createTextNode(option.name);

            // Append icon and text to button
            item.appendChild(iconWrapper);
            item.appendChild(textNode);

            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                option.handler();
                closeAllShareMenus();
            });

            // Keyboard navigation
            item.addEventListener('keydown', (e) => handleMenuKeyboard(e, menu));

            menu.appendChild(item);
        });

        // Position menu relative to button
        button.parentElement.style.position = 'relative';
        button.parentElement.appendChild(menu);

        // Focus first menu item
        setTimeout(() => {
            const firstItem = menu.querySelector('.hkfn-share-menu-item');
            if (firstItem) firstItem.focus();
        }, 10);
    }

    /**
     * Handle keyboard navigation in menu
     */
    function handleMenuKeyboard(e, menu) {
        const items = Array.from(menu.querySelectorAll('.hkfn-share-menu-item'));
        const currentIndex = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const nextIndex = (currentIndex + 1) % items.length;
            items[nextIndex].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prevIndex = (currentIndex - 1 + items.length) % items.length;
            items[prevIndex].focus();
        } else if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.target.click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeAllShareMenus();
        }
    }

    /**
     * Close all share menus
     */
    function closeAllShareMenus() {
        const menus = document.querySelectorAll('.hkfn-share-menu');
        menus.forEach(menu => menu.remove());
    }

    /**
     * Handle outside click to close menu
     */
    function handleOutsideClick(e) {
        if (!e.target.closest('.hkfn-share-button') && !e.target.closest('.hkfn-share-menu')) {
            closeAllShareMenus();
        }
    }

    /**
     * Handle Escape key to close menu
     */
    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closeAllShareMenus();
        }
    }

    /**
     * Share via Facebook
     */
    function shareFacebook(data) {
        const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(data.url)}`;
        window.open(url, '_blank', 'width=600,height=400');
    }

    /**
     * Share via Email
     */
    function shareEmail(data) {
        const subject = encodeURIComponent(`Funeral Service for ${data.title}`);
        const body = encodeURIComponent(`${data.text}\n\n${data.url}`);
        window.location.href = `mailto:?subject=${subject}&body=${body}`;
    }

    /**
     * Share via SMS
     */
    function shareSMS(data) {
        const message = encodeURIComponent(`${data.text} ${data.url}`);
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        const separator = isIOS ? '&' : '?';
        window.location.href = `sms:${separator}body=${message}`;
    }

    /**
     * Share via WhatsApp
     */
    function shareWhatsApp(data) {
        const message = encodeURIComponent(`${data.text} ${data.url}`);
        const url = `https://wa.me/?text=${message}`;
        window.open(url, '_blank');
    }

})();
