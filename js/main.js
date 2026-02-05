const siteHeader = document.querySelector(".site-header");
const mobileBtn = document.querySelector(".site-header__mobile-btn");
const closeBtn = document.querySelector(".site-header__close-btn");
const mainMenu = document.querySelector(".site-header__navigation");
const parentMenuItems = document.querySelectorAll(
  ".site-header .main-nav .menu-item-has-children",
);
const mainContent = document.querySelectorAll(
  ".page-template-default .main-content",
);
const blocksInContent = document.querySelectorAll(
  ".page-template-default .main-content .block[data-extract]",
);
const footerLocations = document.querySelector(
  ".locations-footer .locations-cards__carousel .splide",
);

const accordeonItems = document.querySelectorAll(".accordeon");

//Breakpoints
const mobile = 480;
const tablet = 768;
const ldpi = 1024;
const mdpi = 1200;
const hdpi = 1440;

document.addEventListener("DOMContentLoaded", () => {
  eventListeners();

  blocksInContent && extractBlocks();

  footerLocations && footerLocationsCarousel();

  document.querySelectorAll(".sidebar").forEach((el) => {
    if (!el.querySelector("*")) el.classList.add("is-empty");
  });
});

function eventListeners() {
  showMenus();

  if (closeBtn) {
    closeBtn.addEventListener("click", closeMenu);
  }

  // Debounce resize event
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      showMenus();
    }, 250);
  });

  if (document.querySelector(".site-header--sticky"))
    window.addEventListener("scroll", fadeInHeader);

  if (accordeonItems) {
    accordeonItems.forEach((item) => {
      item
        .querySelector(".accordeon__heading")
        .addEventListener("click", (e) => {
          item.classList.toggle("open");
        });
    });
  }
}

function showMenus() {
  // re-query in case the DOM changed
  const parentMenuItems = document.querySelectorAll(
    ".site-header .main-nav .menu-item-has-children",
  );

  if (!mobileBtn || !mainMenu) return;

  // always remove listener using the same reference before adding
  mobileBtn.removeEventListener("click", openMenu);

  if (window.screen.width > mdpi) {
    mobileBtn.classList.remove("active");
    mainMenu.classList.remove("active");

    // remove listeners on desktop
    parentMenuItems.forEach((item) => {
      item.removeEventListener("click", handleSubMenuClick);
      item.classList.remove("active");
    });
  } else {
    // add listener on mobile (same reference, no wrapper)
    mobileBtn.addEventListener("click", openMenu);

    parentMenuItems.forEach((item) => {
      // ensure there are no duplicates
      item.removeEventListener("click", handleSubMenuClick);
      item.addEventListener("click", handleSubMenuClick);
    });
  }
}

// Function to handle close button clicks
function closeMenu() {
  mainMenu.classList.remove("active");
  mobileBtn.classList.remove("active");
}

// Function to handle menu item clicks
function openMenu() {
  removeSubmenuActiveClasses();
  mainMenu.classList.toggle("active");
  mobileBtn.classList.toggle("active");
}

// Function to handle submenu item clicks
function handleSubMenuClick(e) {
  if (e.target.tagName !== "A") {
    e.stopPropagation();
    let currentItem = e.currentTarget;
    currentItem.classList.toggle("active");
  }
}

function removeSubmenuActiveClasses() {
  parentMenuItems.forEach((item) => {
    item.classList.remove("active");
  });
}

//Top Bar on Scroll
function fadeInHeader() {
  if (window.scrollY > 0) {
    siteHeader.classList.add("scrolling");
  } else {
    siteHeader.classList.remove("scrolling");
  }
}

//Splide Carousels
function footerLocationsCarousel() {
  new Splide(footerLocations, {
    type: "loop",
    perMove: 1,
    perPage: 4,
    arrows: true,
    pagination: false,
    breakpoints: {
      [tablet]: {
        perPage: 1,
      },
      [ldpi]: {
        perPage: 2,
      },
    },
  }).mount();
}

//Blocks
function extractBlocks() {
  blocksInContent.forEach((item) => {
    if (item.getAttribute("data-extract") === "before") {
      mainContent.insertAdjacentHTML("beforebegin", item.outerHTML);
    } else {
      mainContent.insertAdjacentHTML("afterend", item.outerHTML);
    }
    item.remove();
  });
}

//Find Blocks with Bg-BiColor class
(function findConsecutiveGroups() {
  const blocks = document.querySelectorAll("body>section");

  if (!blocks) return;

  const groups = [];
  let currentGroup = [];

  for (let i = 0; i < blocks.length; i++) {
    if (blocks[i].classList.contains("bg-bicolor")) {
      currentGroup.push(blocks[i]);
    } else {
      // Non-bg-bicolor element breaks the sequence
      if (currentGroup.length > 1) {
        groups.push([...currentGroup]);
      }
      currentGroup = []; // Reset for next potential group
    }
  }

  // Don't forget the last group
  if (currentGroup.length > 1) {
    groups.push(currentGroup);
  }

  groups.forEach((group) => {
    const firstEl = group[0];
    const wrapper = document.createElement("section");
    wrapper.classList.add("bg-bicolor");

    firstEl.parentNode.insertBefore(wrapper, firstEl);

    group.forEach((el) => {
      wrapper.appendChild(el);
    });
  });
})();

//Delay Google Maps Rendering
(function googleMapsLazyLoading() {
  "use strict";

  const embeddedMaps = document.querySelectorAll(".gmap-lazy");
  if (!embeddedMaps.length) return;

  let pageLoaded = false;
  const loadedMaps = new WeakSet();
  const loadedCarousels = new WeakSet();

  window.addEventListener("load", () => {
    pageLoaded = true;
    initMaps();
  });

  function initMaps() {
    const spliceMaps = [];
    const nonSpliceMaps = [];

    embeddedMaps.forEach((map) => {
      if (map.closest(".splide")) {
        spliceMaps.push(map);
      } else {
        nonSpliceMaps.push(map);
      }
    });

    if (spliceMaps.length) {
      observeSplideCarousels();
    }

    nonSpliceMaps.forEach((map) => observer.observe(map));
  }

  // Intersection Observer for non-carousel maps
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && pageLoaded) {
          loadEmbeddedMaps(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    {
      rootMargin: "100px",
    },
  );

  // Intersection Observer for entire carousels
  const carouselObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && pageLoaded) {
          loadCarouselMaps(entry.target);
          carouselObserver.unobserve(entry.target); // Unobserve after first trigger
        }
      });
    },
    {
      rootMargin: "200px",
    },
  );

  function observeSplideCarousels() {
    const splideElements = document.querySelectorAll(".splide:has(.gmap-lazy)");

    splideElements.forEach((splideEl) => {
      carouselObserver.observe(splideEl);
    });
  }

  function loadCarouselMaps(splideEl) {
    // Check and add in one step
    if (loadedCarousels.has(splideEl)) {
      //console.log("Carousel already loaded, skipping");
      return;
    }

    //console.log("Loading carousel maps for the first time");
    loadedCarousels.add(splideEl);

    const checkSplide = setInterval(() => {
      const splide = splideEl.classList.contains("is-initialized");

      if (!splide) return;

      clearInterval(checkSplide);

      // Load all maps at once
      const slides = splideEl.querySelectorAll(".gmap-lazy");

      slides.forEach((slide) => {
        const map = slide;
        if (map && map.dataset.src) {
          loadEmbeddedMaps(map);
        }
      });
    }, 50);

    setTimeout(() => clearInterval(checkSplide), 5000);
  }

  function loadEmbeddedMaps(container) {
    // CRITICAL: Check and mark as loaded IMMEDIATELY
    if (loadedMaps.has(container)) return;
    loadedMaps.add(container);

    const src = container.dataset.src;
    if (!src) return;

    const iframe = document.createElement("iframe");
    iframe.src = src;
    iframe.width = "100%";
    iframe.height = "100%";
    iframe.style.cssText = `
      border: 0;
      border-radius: 8px;
      position: absolute;
      top: 0;
      left: 0;
      opacity: 0;
      transition: opacity 0.3s ease;
    `;
    iframe.allowFullscreen = true;
    iframe.referrerPolicy = "no-referrer-when-downgrade";
    iframe.loading = "eager";

    container.innerHTML = "";
    container.appendChild(iframe);

    iframe.onload = () => {
      iframe.style.opacity = "1";
    };

    setTimeout(() => {
      iframe.style.opacity = "1";
    }, 300);
  }
})();

//Unwrap Elements
window.addEventListener("load", () => {
  const wrappedImages = document.querySelectorAll(
    "p:has(img), p:has(picture), p:has(figure)",
  );
  wrappedImages.forEach((paragraph) => {
    const elementsToUnwrap = paragraph.querySelectorAll("img, picture, figure");
    elementsToUnwrap.forEach((element) => {
      paragraph.insertAdjacentElement("beforebegin", element);
    });
    if (paragraph.textContent.trim() === "") {
      paragraph.remove();
    }
  });
});
