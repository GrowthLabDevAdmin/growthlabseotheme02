const siteHeader = document.querySelector(".site-header");
const mobileBtn = document.querySelector(".site-header__mobile-btn");
const closeBtn = document.querySelector(".site-header__close-btn");
const mainMenu = document.querySelector(".site-header__navigation");
const parentMenuItems = document.querySelectorAll(
  ".site-header .main-nav .menu-item-has-children",
);
const pageInner = document.querySelector(".page-template-default .page__inner");
const blocksInContent = document.querySelectorAll(
  ".page-template-default .page__main .block[data-extract]",
);

const accordionItems = document.querySelectorAll(".accordion");

//Breakpoints
const mobile = 480;
const tablet = 768;
const ldpi = 1024;
const mdpi = 1200;
const hdpi = 1440;

requestAnimationFrame(() => {
  findConsecutiveGroups();
});

blocksInContent && extractBlocks();

document.addEventListener("DOMContentLoaded", () => {
  showMenus();
  footerOfficesSelector();
  eventListeners();

  document.querySelectorAll(".sidebar").forEach((el) => {
    if (!el.querySelector("*")) el.classList.add("is-empty");
  });
});

function eventListeners() {
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

  if (accordionItems) {
    accordionItems.forEach((item) => {
      item
        .querySelector(".accordion__heading")
        .addEventListener("click", toggleAccordion);
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

//Blocks
function extractBlocks() {
  blocksInContent.forEach((item) => {
    if (item.getAttribute("data-extract") === "before") {
      pageInner.insertAdjacentHTML("beforebegin", item.outerHTML);
    } else {
      pageInner.insertAdjacentHTML("afterend", item.outerHTML);
    }
    item.remove();
  });
}

//Find Blocks with Bg-gradient class
function findConsecutiveGroups() {
  const blocks = document.querySelectorAll("body>section");

  if (!blocks) return;

  const groups = [];
  let currentGroup = [];

  for (let i = 0; i < blocks.length; i++) {
    if (blocks[i].classList.contains("bg-gradient")) {
      currentGroup.push(blocks[i]);
    } else {
      // Non-bg-gradient element breaks the sequence
      if (currentGroup.length > 1) {
        groups.push([...currentGroup]);
      }
      currentGroup = []; // Reset for next potential group
    }
  }

  if (currentGroup.length > 1) {
    groups.push(currentGroup);
  }

  groups.forEach((group) => {
    const firstEl = group[0];
    const wrapper = document.createElement("section");
    wrapper.classList.add("bg-gradient");

    firstEl.parentNode.insertBefore(wrapper, firstEl);

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          wrapper.appendChild(entry.target);
          observer.unobserve(entry.target);

          // Una vez movido, si el wrapper tiene ya el resto de elementos, podemos activar la carga diferencial.
          if (wrapper.children.length === group.length) {
            lazyLoadBgGradient();
          }
        });
      },
      { rootMargin: "100px" }
    );

    group.forEach((el) => {
      observer.observe(el);
    });
  });

  // Always execute lazy load for:
  // - Non-consecutive bg-gradient sections
  // - Groups that haven't entered viewport yet
  lazyLoadBgGradient();
}

// Lazy Load Background Images for .bg-gradient
function lazyLoadBgGradient() {
  "use strict";

  const bgGradientElements = Array.from(
    document.querySelectorAll(".bg-gradient"),
  ).filter((el) => !el.parentElement?.closest(".bg-gradient"));

  if (!bgGradientElements.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("bg-gradient--loaded");
        observer.unobserve(entry.target);
      });
    },
    { rootMargin: "100px" },
  );

  const init = () => bgGradientElements.forEach((el) => observer.observe(el));

  // Si load ya disparó (script defer), init directo
  if (document.readyState === "complete") {
    init();
  } else {
    window.addEventListener("load", init, { once: true });
  }
}

//Accordion Items
function toggleAccordion(e) {
  const header = e.target;
  const content = header.nextElementSibling;
  const inner = content.querySelector(".accordion__inner");

  header.closest(".accordion").classList.toggle("open");

  if (content.style.maxHeight) {
    // Cerrar
    content.style.maxHeight = null;
  } else {
    // Abrir - usa la altura real del contenido
    content.style.maxHeight = inner.scrollHeight + "px";
  }

  new ResizeObserver((inner) => {
    const content = inner.target.closest(".accordion__content");
    if (content && content.classList.contains("active")) {
      content.style.maxHeight = entry.target.scrollHeight + "px";
    }
  });
}

//Footer Offices Selector
function footerOfficesSelector() {
  const officeSelectors = document.querySelectorAll(
    ".footer-offices-selector__item",
  );
  const offices = document.querySelectorAll(".footer-office");

  if (!officeSelectors.length || !offices.length) return;

  // Set first element as active on page load
  if (officeSelectors[0]) {
    officeSelectors[0].classList.add("active");
  }
  if (offices[0]) {
    offices[0].classList.add("active");
  }

  officeSelectors.forEach((selector) => {
    selector.addEventListener("click", (e) => {
      const officeCity = selector.getAttribute("data-office");

      // Remove active class from all selectors and offices
      officeSelectors.forEach((item) => item.classList.remove("active"));
      offices.forEach((office) => office.classList.remove("active"));

      // Add active class to clicked selector
      selector.classList.add("active");

      // Add active class to matching office
      offices.forEach((office) => {
        if (office.getAttribute("data-office") === officeCity) {
          office.classList.add("active");
        }
      });
    });
  });
}

//Delay Google Maps Rendering
(function googleMapsLazyLoading() {
  "use strict";

  const embeddedMaps = document.querySelectorAll(".gmap-lazy");
  if (!embeddedMaps.length) return;

  let pageLoaded = false;
  const loadedMaps = new WeakSet();

  window.addEventListener("load", () => {
    pageLoaded = true;
    initMaps();
  });

  function initMaps() {
    embeddedMaps.forEach((map) => {
      observer.observe(map);
    });
  }

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

// Lazy Load Images
(function lazyLoadImages() {
  "use strict";

  const lazyImages = document.querySelectorAll('.lazy-image');

  if (!lazyImages.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        const img = entry.target;
        const src = img.dataset.src;
        const picture = img.closest('picture');

        if (src) {
          img.src = src;
          img.removeAttribute('data-src');
        }

        if (picture) {
          const sources = picture.querySelectorAll('source');
          sources.forEach((source) => {
            const srcset = source.dataset.srcset;
            if (srcset) {
              source.srcset = srcset;
              source.removeAttribute('data-srcset');
            }
          });
        }

        img.classList.remove('lazy-image');
        observer.unobserve(img);
      });
    },
    { rootMargin: '100px' }
  );

  // Function to observe new lazy images
  const observeNewImages = (images) => {
    images.forEach((img) => {
      if (!img.classList.contains('lazy-image')) return; // Skip if already loaded
      observer.observe(img);
    });
  };

  // Observe initial images
  observeNewImages(lazyImages);

  // Use MutationObserver to detect dynamically added images (e.g., Splide clones)
  const mutationObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          // Check if the added node is a lazy image
          if (node.classList && node.classList.contains('lazy-image')) {
            observeNewImages([node]);
          }
          // Also check descendants
          const newLazyImages = node.querySelectorAll ? node.querySelectorAll('.lazy-image') : [];
          observeNewImages(newLazyImages);
        }
      });
    });
  });

  // Start observing the entire document for changes
  mutationObserver.observe(document.body, {
    childList: true,
    subtree: true
  });

  // Fallback: Load all images after 5 seconds if JS fails
  const fallbackTimer = setTimeout(() => {
    const allLazyImages = document.querySelectorAll('.lazy-image');
    allLazyImages.forEach((img) => {
      const src = img.dataset.src;
      const picture = img.closest('picture');

      if (src) {
        img.src = src;
        img.removeAttribute('data-src');
      }

      if (picture) {
        const sources = picture.querySelectorAll('source');
        sources.forEach((source) => {
          const srcset = source.dataset.srcset;
          if (srcset) {
            source.srcset = srcset;
            source.removeAttribute('data-srcset');
          }
        });
      }

      img.classList.remove('lazy-image');
    });
    mutationObserver.disconnect(); // Stop observing after fallback
  }, 5000);

  const init = () => {
    // Initial observation already done
  };

  if (document.readyState === 'complete') {
    init();
  } else {
    window.addEventListener('load', init, { once: true });
  }
})();

// Observe Section Visibility
(function observeSectionVisibility() {
  "use strict";

  const sections = document.querySelectorAll('section');

  if (!sections.length) return;

  const visibilityObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && !entry.target.hasAttribute('data-visible')) {
          entry.target.setAttribute('data-visible', 'true');
        }
        // Do not remove the attribute when exiting
      });
    },
    { rootMargin: '0px', threshold: 0.1 } // Adjust threshold as needed
  );

  const initVisibility = () => {
    sections.forEach((section) => visibilityObserver.observe(section));
  };

  if (document.readyState === 'complete') {
    initVisibility();
  } else {
    window.addEventListener('load', initVisibility, { once: true });
  }
})();
