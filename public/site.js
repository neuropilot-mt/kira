// Фото загружаются в админке /admin.
// Страница берёт список из /photos/photos.json: превью на карточке, оригинал по клику.

const contact = {
  email: "",
  phone: "",
};

function showImage(frame, src) {
  const img = frame.querySelector("img");
  const fallback = frame.querySelector("[data-fallback]");
  if (!img || !src) return;

  img.addEventListener("error", () => {
    img.hidden = true;
    img.removeAttribute("src");
    if (fallback) fallback.hidden = false;
  }, { once: true });

  img.addEventListener("load", () => {
    img.hidden = false;
    if (fallback) fallback.hidden = true;
  }, { once: true });

  img.src = src;
}

function photoPath(url) {
  return url.split("/").map((part) => encodeURIComponent(part)).join("/");
}

function photoVersion(url, version) {
  const path = photoPath(url);
  if (!version) return path;
  return `${path}?v=${version}`;
}

function safePhoto(entry, version) {
  if (!entry || typeof entry.preview !== "string" || typeof entry.original !== "string") return null;
  const local = (value) => value.startsWith("photos/") && !value.includes("..");
  if (!local(entry.preview) || !local(entry.original)) return null;
  return {
    preview: photoVersion(entry.preview, version),
    original: photoVersion(entry.original, version),
    alt: typeof entry.alt === "string" ? entry.alt : "",
  };
}

function bindOpen(frame, photo) {
  frame.classList.add("photo-open");
  frame.tabIndex = 0;
  frame.setAttribute("role", "button");
  frame.setAttribute("aria-label", photo.alt ? `Открыть оригинал: ${photo.alt}` : "Открыть оригинал");

  const open = () => openPhoto(photo);
  frame.addEventListener("click", open);
  frame.addEventListener("keydown", (event) => {
    if (event.key !== "Enter" && event.key !== " ") return;
    event.preventDefault();
    open();
  });
}

function openPhoto(photo) {
  const dialog = document.querySelector("[data-viewer]");
  const img = dialog?.querySelector("img");
  const status = dialog?.querySelector("[data-viewer-status]");
  if (!dialog || !img) return;

  const token = `${photo.original}:${Date.now()}`;
  dialog.dataset.token = token;
  img.alt = photo.alt || "Фото";
  img.src = photo.preview;
  if (status) {
    status.textContent = "Открываю оригинал";
    status.hidden = false;
  }
  if (!dialog.open) dialog.showModal();

  const full = new Image();
  full.onload = () => {
    if (dialog.dataset.token !== token) return;
    img.src = photo.original;
    if (status) status.hidden = true;
  };
  full.onerror = () => {
    if (dialog.dataset.token !== token) return;
    if (status) status.textContent = "Не удалось открыть оригинал";
  };
  full.src = photo.original;
}

function setupViewer() {
  const dialog = document.querySelector("[data-viewer]");
  if (!dialog) return;
  const img = dialog.querySelector("img");

  dialog.querySelector("[data-viewer-close]")?.addEventListener("click", () => dialog.close());
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener("close", () => {
    dialog.dataset.token = "";
    img?.removeAttribute("src");
  });
}

function renderWorks(works) {
  const scroller = document.querySelector("[data-scroller]");
  const dots = document.querySelector("[data-dots]");
  if (!scroller || !dots || !works.length) return;

  scroller.replaceChildren();
  dots.replaceChildren();

  works.forEach((photo, index) => {
    const article = document.createElement("article");
    article.className = "w-[82%] shrink-0 snap-center sm:w-[46%] lg:w-auto";
    article.dataset.card = "";

    const figure = document.createElement("figure");
    figure.className = "relative aspect-[4/5] overflow-hidden rounded-[1.35rem] bg-sand";

    const fallback = document.createElement("div");
    fallback.dataset.fallback = "";
    fallback.className = "absolute inset-0 bg-[radial-gradient(80%_70%_at_50%_28%,#FFFCFA_0%,#E7E1DA_70%)]";

    const img = document.createElement("img");
    img.className = "absolute inset-0 h-full w-full object-cover";
    img.alt = photo.alt || "Фото";
    img.hidden = true;

    figure.append(fallback, img);
    article.append(figure);
    scroller.append(article);
    showImage(figure, photo.preview);
    bindOpen(figure, photo);

    const dot = document.createElement("button");
    dot.className = index === 0 ? "h-1.5 w-1.5 rounded-full bg-ink" : "h-1.5 w-1.5 rounded-full bg-stone";
    dot.type = "button";
    dot.setAttribute("role", "tab");
    dot.setAttribute("aria-selected", index === 0 ? "true" : "false");
    dot.setAttribute("aria-label", photo.alt || `Фото ${index + 1}`);
    dot.dataset.dot = String(index);
    dots.append(dot);
  });

  dots.hidden = works.length < 2;
}

function applyManifest(manifest) {
  const version = manifest?.version;
  const hero = safePhoto(manifest?.hero, version);
  const works = Array.isArray(manifest?.works)
    ? manifest.works.map((entry) => safePhoto(entry, version)).filter(Boolean)
    : [];

  if (hero) {
    const frame = document.querySelector('[data-photo="hero"]');
    const img = frame?.querySelector("img");
    if (img && hero.alt) img.alt = hero.alt;
    if (frame) {
      showImage(frame, hero.preview);
      bindOpen(frame, hero);
    }
  }

  const scroller = document.querySelector("[data-scroller]");
  const dots = document.querySelector("[data-dots]");
  const seeAll = document.querySelector("[data-see-all]");

  if (works.length) {
    renderWorks(works);
  } else if (hero && scroller && dots) {
    scroller.replaceChildren();
    dots.replaceChildren();
    dots.hidden = true;
    if (seeAll) seeAll.hidden = true;
  }
}

async function loadManifest() {
  try {
    const response = await fetch("photos/photos.json", { cache: "no-store" });
    if (!response.ok) return null;
    return await response.json();
  } catch {
    return null;
  }
}

function applyContact() {
  const email = contact.email.trim();
  const phone = contact.phone.trim();
  const tel = phone.replace(/[^\d+]/g, "");
  const href = email ? `mailto:${email}` : tel ? `tel:${tel}` : "";

  if (href) {
    document.querySelectorAll("[data-contact]").forEach((link) => {
      link.setAttribute("href", href);
    });
  }

  const line = document.querySelector("[data-contact-line]");
  if (!line) return;
  line.replaceChildren();

  const links = [];
  if (email) {
    const mail = document.createElement("a");
    mail.href = `mailto:${email}`;
    mail.textContent = email;
    mail.className = "underline decoration-stone underline-offset-4";
    links.push(mail);
  }
  if (tel) {
    const call = document.createElement("a");
    call.href = `tel:${tel}`;
    call.textContent = phone;
    call.className = "underline decoration-stone underline-offset-4";
    links.push(call);
  }

  if (!links.length) {
    line.hidden = true;
    return;
  }

  links.forEach((node, index) => {
    if (index) line.append(document.createTextNode(", "));
    line.append(node);
  });
  line.hidden = false;
}

function setupWorks() {
  const scroller = document.querySelector("[data-scroller]");
  const seeAll = document.querySelector("[data-see-all]");
  if (!scroller || !seeAll) return;

  const cards = [...scroller.querySelectorAll("[data-card]")];
  const dots = [...document.querySelectorAll("[data-dot]")];
  const label = seeAll.querySelector("[data-see-label]");
  const arrow = seeAll.querySelector("svg");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function select(index) {
    dots.forEach((dot, i) => {
      const on = i === index;
      dot.setAttribute("aria-selected", on ? "true" : "false");
      dot.classList.toggle("bg-ink", on);
      dot.classList.toggle("bg-stone", !on);
    });
  }

  dots.forEach((dot) => {
    dot.addEventListener("click", () => {
      const index = Number(dot.dataset.dot);
      if (!cards[index]) return;
      select(index);
      if (scroller.dataset.expanded === "true") return;
      cards[index].scrollIntoView({
        behavior: reduceMotion ? "auto" : "smooth",
        inline: "center",
        block: "nearest",
      });
    });
  });

  function syncDots() {
    if (scroller.dataset.expanded === "true") return;
    if (scroller.scrollWidth <= scroller.clientWidth + 1) return;
    const root = scroller.getBoundingClientRect();
    let best = 0;
    let bestVisible = -1;
    cards.forEach((card, index) => {
      const rect = card.getBoundingClientRect();
      const visible = Math.min(rect.right, root.right) - Math.max(rect.left, root.left);
      if (visible > bestVisible) {
        bestVisible = visible;
        best = index;
      }
    });
    select(best);
  }

  scroller.addEventListener("scroll", syncDots, { passive: true });
  window.addEventListener("resize", syncDots);
  requestAnimationFrame(() => requestAnimationFrame(syncDots));

  seeAll.addEventListener("click", () => {
    const nextOpen = scroller.dataset.expanded !== "true";
    scroller.dataset.expanded = nextOpen ? "true" : "false";
    seeAll.setAttribute("aria-expanded", nextOpen ? "true" : "false");
    if (label) label.textContent = nextOpen ? "Свернуть" : "Смотреть все";
    if (arrow) {
      if (nextOpen) arrow.setAttribute("hidden", "");
      else arrow.removeAttribute("hidden");
    }
  });
}

function setupMenu() {
  const menu = document.querySelector("header details");
  if (!menu) return;
  menu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      menu.open = false;
    });
  });
}

async function start() {
  const manifest = await loadManifest();
  if (manifest) applyManifest(manifest);
  applyContact();
  setupWorks();
  setupMenu();
  setupViewer();
}

start();
