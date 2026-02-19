/* posts-filters.js
 * Enqueued via functions.php — depende de postsFiltersData (wp_localize_script)
 *
 * wp_localize_script( 'posts-filters', 'postsFiltersData', array(
 *     'restUrl' => get_rest_url( null, 'wp/v2' ),
 *     'perPage' => 9,
 * ));
 */

document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  // ── DOM refs ──────────────────────────────────────────────────────────────
  const form = document.getElementById("postsFiltersForm");
  const resetBtn = document.getElementById("pfReset");
  const pillsWrap = document.getElementById("pfPills");
  const grid = document.querySelector(".blog__loop, .archive__loop");
  const pgWrap = document.querySelector(
    ".blog__pagination, .archive__pagination",
  );

  if (!form || !grid) return;

  // ── Config ────────────────────────────────────────────────────────────────
  const REST_URL = postsFiltersData.restUrl;
  const PER_PAGE = postsFiltersData.perPage || 9;

  const MONTHS_EN = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];

  // ── State ─────────────────────────────────────────────────────────────────
  const state = {
    search: "",
    category: "",
    month: "",
    year: "",
    page: 1,
    totalPages: 1,
  };

  let debounceTimer = null;

  // ── Helpers ───────────────────────────────────────────────────────────────
  function pad(n) {
    return String(n).padStart(2, "0");
  }

  function stripHTML(html) {
    const div = document.createElement("div");
    div.innerHTML = html;
    return div.textContent.trim();
  }

  function monthNumber(name) {
    return MONTHS_EN.indexOf(name) + 1; // 1-based
  }

  function readForm() {
    state.search = document.getElementById("pf-search").value.trim();
    state.category = document.getElementById("pf-category")?.value ?? "";
    state.month = document.getElementById("pf-month").value;
    state.year = document.getElementById("pf-year").value;
  }

  // ── Build REST query params ───────────────────────────────────────────────
  function buildParams() {
    const params = new URLSearchParams({
      per_page: PER_PAGE,
      page: state.page,
      _embed: 1,
      status: "publish",
    });

    if (state.search) params.set("search", state.search);
    if (state.category) params.set("categories", state.category);

    if (state.year && state.month) {
      const y = parseInt(state.year, 10);
      const m = monthNumber(state.month);
      const last = new Date(y, m, 0).getDate();
      params.set("after", `${y}-${pad(m)}-01T00:00:00`);
      params.set("before", `${y}-${pad(m)}-${last}T23:59:59`);
    } else if (state.year) {
      params.set("after", `${state.year}-01-01T00:00:00`);
      params.set("before", `${state.year}-12-31T23:59:59`);
    }
    // month-only: no native WP param, filtered client-side after fetch

    return params;
  }

  // ── Fetch & render ────────────────────────────────────────────────────────
  async function fetchAndRender() {
    form.classList.add("is-loading");
    grid.classList.add("is-loading");

    try {
      const res = await fetch(`${REST_URL}/posts?${buildParams()}`);
      let posts = await res.json();

      state.totalPages = parseInt(res.headers.get("X-WP-TotalPages") ?? 1, 10);
      const total = parseInt(res.headers.get("X-WP-Total") ?? 0, 10);

      // Client-side month filter when no year is selected
      if (state.month && !state.year) {
        const m = monthNumber(state.month) - 1; // 0-based for Date
        posts = posts.filter((p) => new Date(p.date).getMonth() === m);
      }

      renderPosts(posts);
      renderPagination(total);
      renderPills();
    } catch (err) {
      console.error("[posts-filters] Fetch error:", err);
      grid.innerHTML =
        '<p class="posts-filters__error">Could not load posts. Please try again.</p>';
    }

    form.classList.remove("is-loading");
    grid.classList.remove("is-loading");
  }

  const DEFAULT_IMAGE = postsFiltersData.defaultImage ?? "";

  // ── Render posts ──────────────────────────────────────────────────────────
  function renderPosts(posts) {
    if (!posts.length) {
      grid.innerHTML =
        '<p class="posts-filters__empty">No posts found for the selected filters.</p>';
      return;
    }

    grid.innerHTML = posts
      .map((post) => {
        const date = new Date(post.date);
        const dateStr = date.toLocaleDateString("en-US", {
          month: "long",
          day: "numeric",
          year: "numeric",
        });

        const title = post.title?.rendered ?? "";
        const excerpt = stripHTML(post.excerpt?.rendered ?? "").slice(0, 200);
        const link = post.link ?? "#";

        const featuredUrl =
          post._embedded?.["wp:featuredmedia"]?.[0]?.source_url ?? "";

        let pictureHTML;
        if (featuredUrl) {
          pictureHTML = `<img class="post-card__pic" src="${featuredUrl}" alt="${title}" loading="lazy">`;
        } else if (DEFAULT_IMAGE) {
          pictureHTML = `<img class="post-card__pic" src="${DEFAULT_IMAGE}" alt="${title}" loading="lazy">`;
        } else {
          pictureHTML = `<div class="post-card__pic post-card__pic--placeholder"></div>`;
        }

        return `
            <article class="post-card blog__card">
                <div class="post-card__wrapper">
                    <a href="${link}" class="post-card__pic-wrapper" target="_self">
                        ${pictureHTML}
                    </a>
                    <div class="post-card__inner">
                        <span class="post-card__meta">${dateStr}</span>
                        <p class="post-card__title">${title}</p>
                        <p class="post-card__content">${excerpt}</p>
                        <a href="${link}" target="_self" class="post-card__link">
                            <span>
                            Read More
                            <svg xmlns="http://www.w3.org/2000/svg" width="70" height="44" viewBox="-5 0 75 44">
  <!-- Circle arc center at (35,22), radius 18 -->
  <path d="M 19.41 13 A 18 18 0 1 1 19.41 31" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
  
  <!-- Arrow: tip moved 3px right → tip at x=38, tail at x=-18 -->
  <line x1="-18" y1="22" x2="38" y2="22" stroke="currentColor" stroke-width="1.2"/>
  
  <!-- Arrow head, tip at x=38 -->
  <polyline points="32,16 38,22 32,28" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round" stroke-linecap="round"/>
</svg>
                            </span>
                        </a>
                    </div>
                </div>
            </article>`;
      })
      .join("");
  }

  // ── SVGs (mismos que la template part posts-pagination.php) ──────────────
  const SVG_PREV = `
        <svg width="54" height="54" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M18.75 9.99981C18.75 9.83405 18.6842 9.67508 18.5669 9.55787C18.4497 9.44066 18.2908 9.37481 18.125 9.37481H3.3837L7.3175 5.44231C7.4349 5.32495 7.5008 5.16578 7.5008 4.99981C7.5008 4.83384 7.4349 4.67467 7.3175 4.55731C7.2001 4.43995 7.041 4.37402 6.875 4.37402C6.709 4.37402 6.5499 4.43995 6.4325 4.55731L1.4325 9.55731C1.3743 9.61537 1.3281 9.68434 1.2966 9.76027C1.2651 9.8362 1.2489 9.9176 1.2489 9.99981C1.2489 10.082 1.2651 10.1634 1.2966 10.2394C1.3281 10.3153 1.3743 10.3843 1.4325 10.4423L6.4325 15.4423C6.5499 15.5597 6.709 15.6256 6.875 15.6256C7.041 15.6256 7.2001 15.5597 7.3175 15.4423C7.4349 15.325 7.5008 15.1658 7.5008 14.9998C7.5008 14.8338 7.4349 14.6747 7.3175 14.5573L3.3837 10.6248H18.125C18.2908 10.6248 18.4497 10.559 18.5669 10.4418C18.6842 10.3245 18.75 10.1656 18.75 9.99981Z" fill="currentColor"/>
        </svg>
        <span class="arrow__placeholder">Prev</span>`;

  const SVG_NEXT = `
        <svg width="54" height="54" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.25 9.99981C1.25 9.83405 1.31585 9.67508 1.43306 9.55787C1.55027 9.44066 1.70924 9.37481 1.875 9.37481H16.6163L12.6825 5.44231C12.5651 5.32495 12.4992 5.16578 12.4992 4.99981C12.4992 4.83384 12.5651 4.67467 12.6825 4.55731C12.7999 4.43995 12.959 4.37402 13.125 4.37402C13.291 4.37402 13.4501 4.43995 13.5675 4.55731L18.5675 9.55731C18.6257 9.61537 18.6719 9.68434 18.7034 9.76027C18.7349 9.8362 18.7511 9.9176 18.7511 9.99981C18.7511 10.082 18.7349 10.1634 18.7034 10.2394C18.6719 10.3153 18.6257 10.3843 18.5675 10.4423L13.5675 15.4423C13.4501 15.5597 13.291 15.6256 13.125 15.6256C12.959 15.6256 12.7999 15.5597 12.6825 15.4423C12.5651 15.325 12.4992 15.1658 12.4992 14.9998C12.4992 14.8338 12.5651 14.6747 12.6825 14.5573L16.6163 10.6248H1.875C1.70924 10.6248 1.55027 10.559 1.43306 10.4418C1.31585 10.3245 1.25 10.1656 1.25 9.99981Z" fill="currentColor"/>
        </svg>
        <span class="arrow__placeholder">Next</span>`;

  // ── Render pagination ─────────────────────────────────────────────────────
  // Replica el markup exacto de template-parts/posts-pagination.php
  function renderPagination() {
    if (!pgWrap) return;

    if (state.totalPages <= 1) {
      pgWrap.innerHTML = "";
      return;
    }

    // Construye los items igual que paginate_links() + el foreach del template
    const items = [];

    // Prev
    if (state.page > 1) {
      items.push({ type: "prev", page: state.page - 1 });
    }

    // Números con dots (mid_size: 2, end_size: 1 — igual que el template)
    const MID = 2;
    const END = 1;

    for (let p = 1; p <= state.totalPages; p++) {
      const inEnd = p <= END || p > state.totalPages - END;
      const inMid = p >= state.page - MID && p <= state.page + MID;

      if (inEnd || inMid) {
        items.push({ type: p === state.page ? "current" : "number", page: p });
      } else {
        // Solo añade dots si el item anterior no era ya dots
        const last = items[items.length - 1];
        if (last && last.type !== "dots") {
          items.push({ type: "dots" });
        }
      }
    }

    // Next
    if (state.page < state.totalPages) {
      items.push({ type: "next", page: state.page + 1 });
    }

    // Construye el UL
    const ul = document.createElement("ul");
    ul.className = "pagination pagination-buttons";

    items.forEach((item) => {
      const li = document.createElement("li");

      switch (item.type) {
        case "prev": {
          li.className =
            "pagination__item btn btn--tertiary pagination__item--prev arrow arrow--prev";
          const a = document.createElement("a");
          a.className =
            "page-numbers pagination__link prev pagination__link--nav";
          a.href = "#";
          a.innerHTML = SVG_PREV;
          a.addEventListener("click", (e) => {
            e.preventDefault();
            goTo(item.page);
          });
          li.appendChild(a);
          break;
        }
        case "next": {
          li.className =
            "pagination__item btn btn--tertiary pagination__item--next arrow arrow--next";
          const a = document.createElement("a");
          a.className =
            "page-numbers pagination__link next pagination__link--nav";
          a.href = "#";
          a.innerHTML = SVG_NEXT;
          a.addEventListener("click", (e) => {
            e.preventDefault();
            goTo(item.page);
          });
          li.appendChild(a);
          break;
        }
        case "current": {
          li.className =
            "pagination__item btn btn--tertiary pagination__item--current is-active";
          const span = document.createElement("span");
          span.className =
            "page-numbers pagination__link current pagination__link--active";
          span.textContent = item.page;
          li.appendChild(span);
          break;
        }
        case "dots": {
          li.className =
            "pagination__item btn btn--tertiary pagination__item--dots";
          const span = document.createElement("span");
          span.className =
            "page-numbers pagination__link dots pagination__link--dots";
          span.textContent = "…";
          li.appendChild(span);
          break;
        }
        case "number":
        default: {
          li.className =
            "pagination__item btn btn--tertiary pagination__item--number";
          const a = document.createElement("a");
          a.className = "page-numbers pagination__link";
          a.href = "#";
          a.textContent = item.page;
          a.addEventListener("click", (e) => {
            e.preventDefault();
            goTo(item.page);
          });
          li.appendChild(a);
          break;
        }
      }

      ul.appendChild(li);
    });

    pgWrap.innerHTML = "";
    pgWrap.appendChild(ul);
  }

  function goTo(page) {
    state.page = page;
    fetchAndRender();
    grid.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  // ── Render active pills ───────────────────────────────────────────────────
  function renderPills() {
    pillsWrap.innerHTML = "";

    const catSelect = document.getElementById("pf-category");
    const catLabel = catSelect?.options[catSelect.selectedIndex]?.text ?? "";

    const active = [
      { key: "search", label: `"${state.search}"` },
      { key: "category", label: catLabel },
      { key: "month", label: state.month },
      { key: "year", label: state.year },
    ].filter((f) => state[f.key]);

    active.forEach(({ key, label }) => {
      const pill = document.createElement("span");
      pill.className = "posts-filters__pill";

      const text = document.createTextNode(label + " ");
      const btn = document.createElement("button");
      btn.setAttribute("aria-label", `Remove ${label}`);
      btn.innerHTML = "&#x2715;";
      btn.addEventListener("click", () => removePill(key));

      pill.appendChild(text);
      pill.appendChild(btn);
      pillsWrap.appendChild(pill);
    });
  }

  function removePill(key) {
    state[key] = "";
    state.page = 1;

    const fieldMap = {
      search: "pf-search",
      category: "pf-category",
      month: "pf-month",
      year: "pf-year",
    };

    const el = document.getElementById(fieldMap[key]);
    if (el) el.value = "";

    fetchAndRender();
  }

  // ── Events ────────────────────────────────────────────────────────────────
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    readForm();
    state.page = 1;
    fetchAndRender();
  });

  document.getElementById("pf-search").addEventListener("input", (e) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      state.search = e.target.value.trim();
      state.page = 1;
      fetchAndRender();
    }, 450);
  });

  resetBtn.addEventListener("click", () => {
    form.reset();
    Object.assign(state, {
      search: "",
      category: "",
      month: "",
      year: "",
      page: 1,
    });
    fetchAndRender();
  });
});
