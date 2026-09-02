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

const accordionItems = document.querySelectorAll(".accordion:not(.disabled)");

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

  document
    .querySelectorAll("select:not(.posts-filters__select)")
    .forEach((el) => {
      new TomSelect(el, {
        controlInput: null,
      });
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
  const blocks = document.querySelectorAll("body>.block");

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
      { rootMargin: "100px" },
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

// Observe Section Visibility
(function observeSectionVisibility() {
  "use strict";

  const sections = document.querySelectorAll("section");

  if (!sections.length) return;

  const visibilityObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (
          entry.isIntersecting &&
          !entry.target.hasAttribute("data-visible")
        ) {
          entry.target.setAttribute("data-visible", "true");
        }
        // Do not remove the attribute when exiting
      });
    },
    { rootMargin: "0px", threshold: 0.1 }, // Adjust threshold as needed
  );

  const initVisibility = () => {
    sections.forEach((section) => visibilityObserver.observe(section));
  };

  if (document.readyState === "complete") {
    initVisibility();
  } else {
    window.addEventListener("load", initVisibility, { once: true });
  }
})();

// Playback Video
(function () {
  // ─── Detección de fuente ─────────────────────────────────────────────────────

  function isLocalVideo(videoId) {
    if (!videoId) return false;
    if (videoId.startsWith(`${siteData["homeURL"]}/wp-content/`)) return true;
    try {
      return (
        new URL(videoId, window.location.href).hostname ===
        window.location.hostname
      );
    } catch {
      return false;
    }
  }

  function extractYouTubeID(url) {
    const regex =
      /(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/|youtube\.com\/shorts\/)([^"&?/ ]{11})/i;
    const match = url.match(regex);
    return match ? match[1] : null;
  }

  function detectSource(trigger, rawValue) {
    if (isLocalVideo(rawValue)) return "local";
    if (trigger.classList.contains("ig-video")) return "instagram";
    if (extractYouTubeID(rawValue)) return "youtube";
    return "unknown";
  }

  // ─── Resolución del video ID ─────────────────────────────────────────────────
  // Acepta tanto data-videourl (URL completa) como data-videoid (ID directo)

  function resolveVideoId(trigger, source) {
    const raw = trigger.dataset.videourl || trigger.dataset.videoid || "";
    if (source === "youtube") return extractYouTubeID(raw) || raw;
    return raw;
  }

  // ─── Constructores de player ─────────────────────────────────────────────────

  function buildIframe(attrs) {
    const attrStr = Object.entries(attrs)
      .map(([k, v]) => (v === true ? k : `${k}="${v}"`))
      .join(" ");
    return `<iframe ${attrStr}></iframe>`;
  }

  function buildPlayer(source, videoId, autoplay = false) {
    switch (source) {
      case "youtube":
        return buildIframe({
          src: `https://www.youtube.com/embed/${videoId}?enablejsapi=1&rel=0&autoplay=${autoplay ? 1 : 0}`,
          title: "YouTube video player",
          frameborder: "0",
          allow:
            "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share",
          allowfullscreen: true,
        });

      case "instagram":
        return buildIframe({
          class: "instagram-media instagram-media-rendered",
          src: `https://www.instagram.com/reel/${videoId}/embed/?cr=1&v=14&wp=1080`,
          allowtransparency: "true",
          allowfullscreen: true,
          frameborder: "0",
          scrolling: "no",
          style: [
            "background:white",
            "max-width:540px",
            "width:calc(100% - 2px)",
            "border-radius:3px",
            "border:1px solid rgb(219,219,219)",
            "display:block",
            "margin:0 0 12px",
            "min-width:326px",
            "padding:0",
          ].join(";"),
        });

      case "local":
        return `<video controls ${autoplay ? "autoplay" : ""} playsinline>
                  <source src="${videoId}" type="video/mp4">
                </video>`;

      default:
        console.warn("[VideoPlayer] Fuente de video no reconocida:", videoId);
        return "";
    }
  }

  // ─── Modo lightbox ───────────────────────────────────────────────────────────

  function openLightbox(source, videoId) {
    if (document.getElementById("video-overlay")) return;

    document.body.insertAdjacentHTML(
      "afterbegin",
      `<div id="video-overlay" class="${source === "instagram" ? "ig-video" : ""}" role="dialog" aria-modal="true">
        <button class="overlay-close" aria-label="Cerrar video">✕</button>
        <div class="video-container">
          ${buildPlayer(source, videoId, true)}
        </div>
      </div>`,
    );

    const overlay = document.getElementById("video-overlay");
    const container = overlay.querySelector(".video-container");
    const iframe = overlay.querySelector("iframe");

    overlay
      .querySelector(".overlay-close")
      .addEventListener("click", closeOverlay);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) closeOverlay();
    });
    document.addEventListener("keydown", handleKeydown);

    // Activar transición: display:flex requiere un frame para que el transition funcione
    requestAnimationFrame(() =>
      requestAnimationFrame(() => {
        overlay.classList.add("active");
        container.classList.add("active");

        // Autoplay diferido: esperar a que el iframe esté rendered
        if (iframe && source === "youtube") {
          iframe.addEventListener(
            "load",
            () => {
              iframe.src = iframe.src.replace("autoplay=0", "autoplay=1");
            },
            { once: true },
          );
        }
      }),
    );
  }

  function closeOverlay() {
    const overlay = document.getElementById("video-overlay");
    if (!overlay) return;

    const container = overlay.querySelector(".video-container");
    const iframe = overlay.querySelector("iframe");
    const video = overlay.querySelector("video");

    if (iframe) iframe.src = "";
    if (video) video.pause();

    overlay.classList.remove("active");
    container.classList.remove("active");
    document.removeEventListener("keydown", handleKeydown);

    setTimeout(() => overlay.remove(), 500);
  }

  function handleKeydown(e) {
    if (e.key === "Escape") closeOverlay();
  }

  // ─── Modo inline ─────────────────────────────────────────────────────────────

  function openInline(trigger, source, videoId) {
    const container = trigger.dataset.target
      ? document.querySelector(trigger.dataset.target)
      : null;

    if (!container) {
      console.warn(
        "[VideoPlayer] Inline container not found. Fallback to lightbox.",
      );
      openLightbox(source, videoId);
      return;
    }

    const prevIframe = container.querySelector("iframe");
    const prevVideo = container.querySelector("video");
    if (prevIframe) prevIframe.src = "";
    if (prevVideo) prevVideo.pause();

    container.innerHTML = buildPlayer(source, videoId, true);
    container.classList.add("active");
    container.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  // ─── Init ────────────────────────────────────────────────────────────────────

  function init() {
    // Event delegation — cubre botones estáticos y dinámicos (AJAX, Gutenberg, etc.)
    document.addEventListener("click", (e) => {
      const trigger = e.target.closest("[data-videourl], [data-videoid]");
      if (!trigger) return;

      e.preventDefault();

      const raw = (
        trigger.dataset.videourl ||
        trigger.dataset.videoid ||
        ""
      ).trim();
      if (!raw) return;

      const source = detectSource(trigger, raw);
      const videoId = resolveVideoId(trigger, source);
      const mode = trigger.dataset.mode || "lightbox";

      mode === "inline"
        ? openInline(trigger, source, videoId)
        : openLightbox(source, videoId);
    });
  }

  document.readyState === "loading"
    ? document.addEventListener("DOMContentLoaded", init)
    : init();
})();
