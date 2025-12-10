/**
 * Page Transitions Module
 * Handles Barba.js page transitions and animations
 */

// This function helps add and remove js and css files during a page transition
const elementExistsInArray = (element, array) =>
	array.some((el) => el.isEqualNode(element));

// Re-execute all inline scripts
function executeInlineScripts(container) {
	const elementorElements = [
		"script#elementor-js",
		"script#elementor-pro-js",
		"script#elementor-frontend-js",
		"script#elementor-frontend-js-before",
		"script#elementor-frontend-js-after",
		"script#elementor-frontend-js-before-legacy",
		"script#elementor-frontend-js-after-legacy",
		"script#elementor-frontend-js-before-vendors",
		"script#elementor-frontend-js-after-vendors",
		"script#elementor-frontend-js-before-legacy",
		"script#elementor-frontend-js-after-legacy",
		"script#imagesloaded-js",
		"script#swiper-js",
		"script#elementor-gallery-js",
	];
	const scripts = container.body.querySelectorAll(elementorElements.join(","));
	scripts.forEach((script) => {
		const newScript = document.createElement("script");
		if (script.src) {
			newScript.src = script.src;
			newScript.async = true;
		} else {
			newScript.textContent = script.textContent;
		}

		document.body.appendChild(newScript);
		script.remove(); // Remove old script to avoid duplication
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

			// Re-execute Elementor inline scripts if Elementor page
			if (bodyClasses.includes("elementor-page")) {
				executeInlineScripts(nextElement);
			}
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
