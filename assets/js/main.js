/**
 * Main JavaScript Entry Point
 * Elite Dental Enterprise Theme
 *
 * This file imports all necessary dependencies and custom scripts
 * for the theme functionality including Barba.js page transitions,
 * GSAP animations, Lenis smooth scroll, and Lottie animations.
 */

// ===========================
// Styles
// ===========================

// Import main CSS (includes Tailwind CSS 5)
import "../css/main.css";
import 'basecoat-css/all';
// ===========================
// External Dependencies
// ===========================

// Barba.js for page transitions
import barba from "@barba/core";

window.barba = barba;

// GSAP for animations
import { gsap } from "gsap";
import { ScrollToPlugin } from "gsap/ScrollToPlugin";
import { ScrollTrigger } from "gsap/ScrollTrigger";

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;

// Lenis smooth scroll
import Lenis from "lenis";

window.Lenis = Lenis;

// Lottie for animations
import lottie from "lottie-web";

window.lottie = lottie;

// SplitType for text animations
import SplitType from "split-type";

window.SplitType = SplitType;

// ===========================
// Custom Modules
// ===========================

// Import Lenis initialization
import { initLenis } from "./lenis-init.js";
// Import Page Functions
import {
	animator,
	removeParallax,
	removeResizeObservers,
} from "./page-functions.js";
// Import Page Transitions
import { initPageTransitions } from "./page-transitions.js";
// Import Preloader
import { initPreloader } from "./preloader.js";
// Import Member Login Handler
import "./member-login.js";
// Import Member Logout Handler
import "./member-logout.js";

// ===========================
// Initialize Theme
// ===========================

// Initialize Lenis smooth scroll
const lenis = initLenis(Lenis, gsap, ScrollTrigger);
window.lenis = lenis;

// Initialize preloader and get the animation function
const loaderAnimation = initPreloader(lottie);
window.loaderAnimation = loaderAnimation;

// Make animator function globally available
window.animator = animator;


// Initialize Barba.js page transitions
initPageTransitions(
	barba,
	gsap,
	loaderAnimation,
	animator,
	removeParallax,
	removeResizeObservers,
);

