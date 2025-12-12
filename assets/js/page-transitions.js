/**
 * Page Transitions Module
 * Handles Barba.js page transitions and animations
 */

// This function helps add and remove js and css files during a page transition
const elementExistsInArray = (element, array) =>
	array.some((el) => el.isEqualNode(element));

// Re-execute inline scripts from the new page
function executeInlineScripts(container) {
	// Get all script tags from the incoming container
	const scripts = container.querySelectorAll('script');

	scripts.forEach((oldScript) => {
		// Skip non-executable script types (JSON data, etc.)
		const scriptType = oldScript.getAttribute('type');
		if (scriptType && scriptType !== 'text/javascript' && scriptType !== 'module') {
			// Skip JSON, speculationrules, etc.
			return;
		}

		// Skip scripts that shouldn't be re-executed
		const skipPatterns = [
			'wpData', // Skip wpData - it's in the head
			'window.backend_data', // Skip backend_data - it's in the head
			'gtag', // Skip Google Analytics
			'google-analytics',
			'facebook',
			'fbevents',
			'updateLoginLogoutWidgets', // Skip our login/logout widget - already handled
			'updateDynamicMenus', // Skip our dynamic menu widget - already handled
		];

		const shouldSkip = skipPatterns.some(pattern =>
			oldScript.textContent.includes(pattern) ||
			(oldScript.src && oldScript.src.includes(pattern))
		);

		if (shouldSkip) {
			return;
		}

		// For external scripts with src
		if (oldScript.src) {
			// Skip external scripts - they're already loaded
			return;
		}

		// For inline scripts, execute in an isolated scope to avoid redeclaration errors
		try {
			let scriptContent = oldScript.textContent;

			// Unwrap DOM ready listeners since DOM is already loaded during transitions
			// This handles many common patterns used by WordPress plugins and themes

			const patterns = [
				// document.addEventListener('DOMContentLoaded', function() { ... });
				/document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// document.addEventListener('DOMContentLoaded', () => { ... });
				/document\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*\([^)]*\)\s*=>\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// window.addEventListener('DOMContentLoaded', function() { ... });
				/window\.addEventListener\s*\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// window.addEventListener('load', function() { ... });
				/window\.addEventListener\s*\(\s*['"]load['"]\s*,\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// window.onload = function() { ... };
				/window\.onload\s*=\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*;?/,

				// jQuery(document).ready(function() { ... });
				/jQuery\s*\(\s*document\s*\)\.ready\s*\(\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// $(document).ready(function() { ... });
				/\$\s*\(\s*document\s*\)\.ready\s*\(\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// jQuery(function() { ... });
				/jQuery\s*\(\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?/,

				// $(function() { ... });
				/^\s*\$\s*\(\s*function\s*\([^)]*\)\s*\{([\s\S]*)\}\s*\)\s*;?\s*$/,

				// if (document.readyState === 'loading') { ... } else { ... }
				/if\s*\(\s*document\.readyState\s*===\s*['"]loading['"]\s*\)\s*\{[^}]*\}\s*else\s*\{([\s\S]*?)\}/,

				// if (document.readyState !== 'loading') { ... }
				/if\s*\(\s*document\.readyState\s*!==\s*['"]loading['"]\s*\)\s*\{([\s\S]*?)\}\s*(?:else\s*\{[^}]*\}\s*)?;?/,
			];

			// Try each pattern until we find a match
			let match = null;
			for (const pattern of patterns) {
				match = scriptContent.match(pattern);
				if (match && match[1]) {
					// Found a DOM ready wrapper - extract and execute the inner code
					scriptContent = match[1].trim();
					break;
				}
			}

			// Use Function constructor to execute in a new scope
			// This prevents "identifier already declared" errors
			const scriptFunction = new Function(scriptContent);
			scriptFunction();
		} catch {
			// Silent fail for inline script execution errors
		}
	});
}

function enterAnimation(e) {
	return new Promise((resolve) => {
		jQuery("a").each(function () {
			const href = jQuery(this).attr("href");
			if (!href) return;

			// Normalize URLs for comparison
			const currentURL = window.location.href.replace(/\/$/, ""); // Remove trailing slash
			const currentPath = window.location.pathname.replace(/\/$/, "");
			const linkURL = href.replace(/\/$/, "");
			
			// Parse link URL
			let linkPath;
			try {
				const url = new URL(linkURL, window.location.origin);
				linkPath = url.pathname.replace(/\/$/, "");
			} catch {
				// Relative URL
				linkPath = linkURL.replace(/\/$/, "");
			}

			// Check if link points to current page
			const isCurrentPage = 
				linkURL === currentURL || // Exact match
				linkURL === currentPath || // Path match
				linkPath === currentPath || // Normalized path match
				(currentPath === "" && (linkPath === "" || linkPath === "/")); // Home page

			if (isCurrentPage) {
				jQuery(this).addClass("elementor-item-active");
				jQuery(this).addClass("current-menu-item");
				jQuery(this).addClass("current_page_item");
				jQuery(this).off("click").on("click", (event) => {
					event.preventDefault();
				});
			}
		});

		// Slide menu down if header exists
		const header = document.querySelector("#site-header");
		if (header) {
			gsap.to("#site-header", {
				duration: 0.5,
				y: "0",
				opacity: 1,
				ease: "power3.inOut",
			});
		}

		// fade in content
		gsap.set(e, { opacity: 0, display: "block" });
		
		animator(e);
		
		gsap
			.to(e, {
				opacity: 1,
				duration: 1,
				ease: "power3.inOut",
				onStart: () => {
					ScrollTrigger.refresh();
				},
			})
			.then(() => {
				resolve();
			});
	});
}

function leaveAnimation(e) {
	function remove_all_active_menu_items() {
		jQuery("li").each(function () {
			jQuery(this).removeClass("elementor-item-active");
			jQuery(this).removeClass("current-menu-item");
			jQuery(this).removeClass("current_page_item");
		});
		jQuery("li > a").each(function () {
			jQuery(this).removeClass("elementor-item-active");
			jQuery(this).removeClass("current-menu-item");
			jQuery(this).removeClass("current_page_item");
		});
	}

	return new Promise((resolve) => {
		remove_all_active_menu_items();
		
		// Slide menu up if header exists
		const header = document.querySelector("#site-header");
		if (header) {
			gsap.to("#site-header", {
				duration: 0.5,
				y: "-100%",
				opacity: 0,
				ease: "power3.inOut",
			});
		}
		
		gsap
			.fromTo(
				e,
				{
					opacity: 1,
					display: "block",
				},
				{
					opacity: 0,
					display: "none",
					duration: 1,
					ease: "power3.inOut",
				},
			)
			.then(() => {
				resolve();
			});
	});
}

/**
 * Initialize Barba.js page transitions
 * @param {Object} barba - Barba instance
 * @param {Object} gsap - GSAP instance
 * @param {Function} loaderAnimation - Preloader animation function
 * @param {Function} animator - Animation function from page-functions
 * @param {Function} removeParallax - Function to remove parallax effects
 * @param {Function} removeResizeObservers - Function to remove resize observers
 */
export function initPageTransitions(
	barba,
	gsap,
	loaderAnimation,
	removeParallax,
	removeResizeObservers,
) {
	// Set up Barba hooks
	barba.hooks.beforeEnter(({ current, next }) => {
		// Set <body> classes for the 'next' page
		if (current.container) {
			// // only run during a page transition - not initial load
			const nextHtml = next.html;
			const response = nextHtml.replace(
				/(<\/?)body( .+?)?>/gi,
				"$1notbody$2>",
				nextHtml,
			);

			const bodyClasses = jQuery(response).filter("notbody").attr("class");
			jQuery("body").attr("class", bodyClasses);

			// get the next elements
			const nextElement = new DOMParser().parseFromString(
				next.html,
				"text/html",
			);

			const newHeadElements = [...nextElement.head.children];
			const currentHeadElements = [...document.head.children];

			// Add new elements that are not in the current head
			// This includes CSS links, which are critical for Vite dev server
			newHeadElements.forEach((newEl) => {
				if (!elementExistsInArray(newEl, currentHeadElements)) {
					document.head.appendChild(newEl.cloneNode(true));
				}
			});

			// Remove old elements that are not in the new head
			// BUT preserve Vite dev server injected styles
			currentHeadElements.forEach((currentEl) => {
				// Don't remove Vite-injected style tags or main.css link
				const isViteStyle = currentEl.tagName === 'STYLE' && 
					(currentEl.getAttribute('data-vite-dev-id') || 
				currentEl.textContent.includes('@import'));
				const isMainCSS = currentEl.tagName === 'LINK' && 
					currentEl.href && currentEl.href.includes('main.css');
				
				if (!elementExistsInArray(currentEl, newHeadElements) && 
						!isViteStyle && !isMainCSS) {
					document.head.removeChild(currentEl);
				}
			});

			// Re-execute ALL inline scripts from the new page
			executeInlineScripts(nextElement.body);
		}

		// ALLOW ELEMENTOR VIDEOS TO AUTOPLAY AFTER TRANSITION
		const elementorVideos = document.querySelectorAll(".elementor-video");
		if (typeof elementorVideos !== "undefined" && elementorVideos != null) {
			elementorVideos.forEach((video) => {
				video.play();
			});
		}

		// Reinitialize Elementor and menu widget
		if (current.container) {
			elementorFrontend.init();
			jQuery(
				'[data-widget_type="nav-menu.default"], [data-widget_type="gallery.default"]',
			).each(function () {
				elementorFrontend.elementsHandler.runReadyTrigger(jQuery(this));
			});
			// Check if the function lazyloadRunObserver is defined
			if (typeof lazyloadRunObserver !== "undefined") {
				lazyloadRunObserver();
			}
		}

		// Reinitialize Remember Me auto-fill for login page
		if (typeof window.initRememberMe !== "undefined") {
			window.initRememberMe();
		}
	});

	barba.hooks.beforeLeave(() => {
		// Remove all the ScrollTriggers
		removeParallax();

		// Remove all ResizeObservers
		removeResizeObservers();
	});

	// Initialize Barba
	barba.init({
		debug: false, // turn this to "true" to debug
		timeout: 5000,
		transitions: [
			{
				sync: false,
				once: async ({ next }) => {
					// Set initial header position if it exists
					const header = document.querySelector("#site-header");
					if (header) {
						gsap.set("#site-header", {
							opacity: 0,
							y: "-100%",
						});
					}
					await loaderAnimation();
					return enterAnimation(next.container);
				},
				leave: ({ current }) => {
					// Leave animation
					return leaveAnimation(current.container);
				},
				enter: ({ next }) => {
					// Enter animation
					return enterAnimation(next.container);
				},
			},
		],
	});
}
