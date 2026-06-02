import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Register the ScrollTrigger plugin
gsap.registerPlugin(ScrollTrigger);

let lenisInstance = null;
let tickerCallback = null;

function initLenis() {
    // 1. Clean up any existing instances and ticker listeners
    if (lenisInstance) {
        lenisInstance.destroy();
        lenisInstance = null;
    }
    if (tickerCallback) {
        gsap.ticker.remove(tickerCallback);
        tickerCallback = null;
    }

    // 2. Check if user prefers reduced motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        console.log('Lenis: Smooth scrolling disabled due to prefers-reduced-motion setting.');
        return;
    }

    // 3. Initialize Lenis
    lenisInstance = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)), // Standard easeOutExpo
        smoothWheel: true,
    });

    // 4. Synchronize Lenis scrolling with GSAP ScrollTrigger updates
    lenisInstance.on('scroll', ScrollTrigger.update);

    // 5. Use GSAP's ticker to drive the animation frame callback
    tickerCallback = (time) => {
        if (lenisInstance) {
            lenisInstance.raf(time * 1000); // Convert seconds to milliseconds
        }
    };
    gsap.ticker.add(tickerCallback);

    // 6. Disable lag smoothing for frame updates to prevent stuttering
    gsap.ticker.lagSmoothing(0);

    // 7. Make instances globally accessible for page-specific scripts or debugging
    window.lenis = lenisInstance;
    window.gsap = gsap;
    window.ScrollTrigger = ScrollTrigger;

    console.log('Lenis and GSAP Smooth Scrolling initialized.');
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initLenis();
});

// Re-initialize cleanly when Livewire finishes dynamic navigation transitions
document.addEventListener('livewire:navigated', () => {
    initLenis();
});
