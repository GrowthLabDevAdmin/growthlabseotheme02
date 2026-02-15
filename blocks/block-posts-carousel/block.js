document.addEventListener("DOMContentLoaded", () => {
  let postsCarousels = document.querySelectorAll(".posts-carousel__carousel");

  if (postsCarousels.length > 0) {
    postsCarousels.forEach((carousel) => {
      let carouselType = carousel.dataset.type;
      let splideElement = carousel.querySelector(".splide");

      const perPageTablet =
        carouselType === "testimonial" || carouselType === "team"
          ? 1
          : carouselType === "case-result" || carouselType === "post"
            ? 2
            : 3;

      const perPageLdpi =
        carouselType === "team"
          ? 1
          : carouselType === "testimonial" || carouselType === "post"
            ? 2
            : 3;

      const focusFor = (n) => (n % 2 === 1 ? "center" : false);
      const trimFor = (n) => (n % 2 === 1 ? true : false);

      carouselObj = {
        type: "loop",
        perPage: 1,
        perMove: 1,
        arrows: true,
        pagination: false,
        mediaQuery: "min",
        breakpoints: {
          [tablet]: {
            perPage: perPageTablet,
            focus: focusFor(perPageTablet),
            trimSpace: trimFor(perPageTablet),
          },
          [ldpi]: {
            perPage: perPageLdpi,
            focus: focusFor(perPageLdpi),
            trimSpace: trimFor(perPageLdpi),
          },
        },
      };

      if (splideElement) {
        new Splide(splideElement, carouselObj).mount();
      }
    });
  }
});
