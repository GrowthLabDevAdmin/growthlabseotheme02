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