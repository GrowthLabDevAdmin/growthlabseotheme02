document.addEventListener("DOMContentLoaded", () => {
  let postsCarousels = document.querySelectorAll(".posts-carousel__carousel");

  if (postsCarousels.length > 0) {
    postsCarousels.forEach((carousel) => {
      let carouselType = carousel.dataset.type;
      let splideElement = carousel.querySelector(".splide");

      if (splideElement) {
        new Splide(splideElement, {
          type: "loop",
          perPage: 1,
          perMove: 1,
          arrows: true,
          pagination: false,
          mediaQuery: "min",
          focus: "center",
          trimSpace: false,
          breakpoints: {
            [tablet]: {
              perPage:
                carouselType === "team"
                  ? 1
                  : carouselType === "case-result" ||
                      carouselType === "testimonial" ||
                      carouselType === "post"
                    ? 2
                    : 3,
            },
            [ldpi]: {
              perPage:
                carouselType === "team"
                  ? 1
                  : carouselType === "testimonial" || carouselType === "post"
                    ? 2
                    : 3,
            },
          },
        }).mount();
      }
    });
  }
});
