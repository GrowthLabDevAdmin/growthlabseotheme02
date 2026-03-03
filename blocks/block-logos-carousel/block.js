(() => {
  const logosCarousels = document.querySelectorAll(
    ".logos-carousel__carousel .splide",
  );
  if (logosCarousels) {
    for (var i = 0; i < logosCarousels.length; i++) {
      new Splide(logosCarousels[i], {
        type: "loop",
        perPage: 1,
        perMove: 1,
        arrows: false,
        pagination: true,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: logosCarousels[i].closest(".sidebar") ? 2 : 3,
          },
          [ldpi]: {
            perPage: logosCarousels[i].closest(".sidebar") ? 3 : 5,
            pagination: false,
          },
        },
      }).mount();
    }
  }
})();
