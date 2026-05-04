// Lazy Load Images
(function lazyLoadImages() {
  "use strict";

  const lazyImages = document.querySelectorAll(".lazy-image");

  if (!lazyImages.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        const img = entry.target;
        const src = img.dataset.src;
        const picture = img.closest("picture");

        if (src) {
          img.src = src;
          img.removeAttribute("data-src");
        }

        if (picture) {
          const sources = picture.querySelectorAll("source");
          sources.forEach((source) => {
            const srcset = source.dataset.srcset;
            if (srcset) {
              source.srcset = srcset;
              source.removeAttribute("data-srcset");
            }
          });
        }

        img.classList.remove("lazy-image");
        observer.unobserve(img);
      });
    },
    { rootMargin: "100px" },
  );

  // Function to observe new lazy images
  const observeNewImages = (images) => {
    images.forEach((img) => {
      if (!img.classList.contains("lazy-image")) return; // Skip if already loaded
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
          if (node.classList && node.classList.contains("lazy-image")) {
            observeNewImages([node]);
          }
          // Also check descendants
          const newLazyImages = node.querySelectorAll
            ? node.querySelectorAll(".lazy-image")
            : [];
          observeNewImages(newLazyImages);
        }
      });
    });
  });

  // Start observing the entire document for changes
  mutationObserver.observe(document.body, {
    childList: true,
    subtree: true,
  });

  // Fallback: Load all images after 5 seconds if JS fails
  const fallbackTimer = setTimeout(() => {
    const allLazyImages = document.querySelectorAll(".lazy-image");
    allLazyImages.forEach((img) => {
      const src = img.dataset.src;
      const picture = img.closest("picture");

      if (src) {
        img.src = src;
        img.removeAttribute("data-src");
      }

      if (picture) {
        const sources = picture.querySelectorAll("source");
        sources.forEach((source) => {
          const srcset = source.dataset.srcset;
          if (srcset) {
            source.srcset = srcset;
            source.removeAttribute("data-srcset");
          }
        });
      }

      img.classList.remove("lazy-image");
    });
    mutationObserver.disconnect(); // Stop observing after fallback
  }, 5000);

  const init = () => {
    // Initial observation already done
  };

  if (document.readyState === "complete") {
    init();
  } else {
    window.addEventListener("load", init, { once: true });
  }
})();
