if (!window.loadSplide) {
  window.loadSplide = (callback) => {
    if (typeof Splide !== "undefined") return callback();
    if (window.__splideLoading) {
      window.__splideCallbacks = window.__splideCallbacks || [];
      window.__splideCallbacks.push(callback);
      return;
    }
    window.__splideLoading = true;
    window.__splideCallbacks = [callback];
    if (!window.splideData || !window.splideData.url) {
      console.error("Splide data not available");
      return;
    }
    const script = document.createElement("script");
    script.src = splideData.url;
    script.onload = () => {
      const checkSplide = (attempts = 0) => {
        if (typeof Splide !== "undefined") {
          window.__splideCallbacks.forEach((fn) => fn());
          window.__splideCallbacks = [];
          return;
        }
        if (attempts < 10) {
          // Retry up to 10 times
          setTimeout(() => checkSplide(attempts + 1), 100); // Check every 100ms
        } else {
          console.error("Splide failed to load after retries");
        }
      };
      checkSplide();
    };
    script.onerror = () => {
      console.error("Failed to load Splide script");
    };
    document.head.appendChild(script);
  };
}

(() => {
  const logosCarousels = document.querySelectorAll(
    ".logos-carousel__carousel .splide",
  );
  if (!logosCarousels.length) return;

  const initCarousel = (splideElement) => {
    const inSidebar = !!splideElement.closest(".sidebar");

    loadSplide(() => {
      new Splide(splideElement, {
        type: "loop",
        perPage: 1,
        perMove: 1,
        arrows: false,
        pagination: true,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: inSidebar ? 2 : 3,
          },
          [ldpi]: {
            perPage: inSidebar ? 3 : 5,
            pagination: false,
          },
        },
      }).mount();
    });
  };

  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        initCarousel(entry.target);
        obs.unobserve(entry.target);
      });
    },
    { rootMargin: "200px 0px" },
  );

  logosCarousels.forEach((carousel) => observer.observe(carousel));
})();
