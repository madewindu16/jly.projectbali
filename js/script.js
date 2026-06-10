const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add("is-visible");
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll(".reveal").forEach((element) => {
  observer.observe(element);
});

const searchForm = document.querySelector(".search-form");
const searchInput = document.querySelector("#site-search");
const searchResults = document.querySelector("#search-results");
const searchableItems = [...document.querySelectorAll("[data-search-title]")];
const mobileMenuToggle = document.querySelector(".mobile-menu-toggle");
const siteNav = document.querySelector(".site-nav");

const closeMobileMenu = () => {
  siteNav?.classList.remove("is-open");
  mobileMenuToggle?.setAttribute("aria-expanded", "false");
};

const closeSearchResults = () => {
  searchResults.classList.remove("is-open");
  searchInput.setAttribute("aria-expanded", "false");
};

const openSearchResults = () => {
  searchResults.classList.add("is-open");
  searchInput.setAttribute("aria-expanded", "true");
};

const goToSearchItem = (item) => {
  closeSearchResults();
  searchInput.value = item.dataset.searchTitle;
  item.scrollIntoView({ behavior: "smooth", block: "center" });
  item.classList.add("search-highlight");

  window.setTimeout(() => {
    item.classList.remove("search-highlight");
  }, 1400);
};

const renderSearchResults = (matches) => {
  searchResults.innerHTML = "";

  if (!matches.length) {
    searchResults.innerHTML = '<div class="search-empty">Hasil tidak ditemukan.</div>';
    openSearchResults();
    return;
  }

  matches.slice(0, 6).forEach((item) => {
    const result = document.createElement("button");
    result.className = "search-result";
    result.type = "button";
    result.setAttribute("role", "option");
    result.innerHTML = `
      <strong>${item.dataset.searchTitle}</strong>
      <span>${item.dataset.searchCategory}</span>
    `;
    result.addEventListener("click", () => goToSearchItem(item));
    searchResults.append(result);
  });

  openSearchResults();
};

const handleSearch = () => {
  const query = searchInput.value.trim().toLowerCase();

  if (!query) {
    closeSearchResults();
    searchResults.innerHTML = "";
    return;
  }

  const matches = searchableItems.filter((item) => {
    const title = item.dataset.searchTitle.toLowerCase();
    const category = item.dataset.searchCategory.toLowerCase();
    const text = item.dataset.searchText.toLowerCase();

    return title.includes(query) || category.includes(query) || text.includes(query);
  });

  renderSearchResults(matches);
};

searchInput.addEventListener("input", handleSearch);
searchInput.addEventListener("focus", handleSearch);

searchForm.addEventListener("submit", (event) => {
  event.preventDefault();
  const firstResult = searchableItems.find((item) => {
    const query = searchInput.value.trim().toLowerCase();

    return query && (
      item.dataset.searchTitle.toLowerCase().includes(query) ||
      item.dataset.searchText.toLowerCase().includes(query)
    );
  });

  if (firstResult) {
    goToSearchItem(firstResult);
  }
});

mobileMenuToggle?.addEventListener("click", () => {
  const isOpen = siteNav.classList.toggle("is-open");
  mobileMenuToggle.setAttribute("aria-expanded", String(isOpen));
});

siteNav?.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", closeMobileMenu);
});

document.addEventListener("click", (event) => {
  if (!searchForm.contains(event.target)) {
    closeSearchResults();
  }

  if (
    siteNav?.classList.contains("is-open") &&
    !siteNav.contains(event.target) &&
    !mobileMenuToggle?.contains(event.target)
  ) {
    closeMobileMenu();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeSearchResults();
    closeMobileMenu();
  }
});

const shareButton = document.querySelector("[data-share-button]");

shareButton?.addEventListener("click", async () => {
  const shareData = {
    title: "jly.projectbali",
    text: "Rental dekorasi pernikahan elegan di Bali.",
    url: window.location.href,
  };

  try {
    if (navigator.share) {
      await navigator.share(shareData);
      return;
    }

    await navigator.clipboard.writeText(window.location.href);
    shareButton.setAttribute("aria-label", "Link website sudah disalin");
  } catch (error) {
    shareButton.setAttribute("aria-label", "Bagikan website jly.projectbali");
  }
});

const productModal = document.querySelector("[data-product-modal]");
const productDetailButtons = document.querySelectorAll("[data-product-detail]");
const cartPanel = document.querySelector("[data-cart-panel]");
const cartItems = document.querySelector("[data-cart-items]");
const cartCount = document.querySelector("[data-cart-count]");
const cartClear = document.querySelector("[data-cart-clear]");
const eventDateInput = document.querySelector("[data-event-date]");
const modalCartAdd = document.querySelector("[data-modal-cart-add]");
const cartJsonInput = document.querySelector("[data-cart-json]");
const bookingForm = document.querySelector("[data-booking-form]");
const bookingSubmit = document.querySelector("[data-booking-submit]");

const today = new Date();
today.setHours(0, 0, 0, 0);

let currentProductCard = null;
let calendarMonth = new Date(today.getFullYear(), today.getMonth(), 1);
let cart = JSON.parse(localStorage.getItem("jlyRentalCart") || "[]");

if (new URLSearchParams(window.location.search).get("booking") === "success") {
  cart = [];
  localStorage.removeItem("jlyRentalCart");
}

const dateFormatter = new Intl.DateTimeFormat("id-ID", {
  day: "numeric",
  month: "long",
  year: "numeric",
});

const monthFormatter = new Intl.DateTimeFormat("id-ID", {
  month: "long",
  year: "numeric",
});

const toDateKey = (date) => {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
};

const parseBookedDates = (productCard) => {
  try {
    return new Set(JSON.parse(productCard?.dataset.productBookedDates || "[]"));
  } catch (error) {
    return new Set();
  }
};

const setModalText = (selector, value) => {
  const element = productModal?.querySelector(selector);

  if (element) {
    element.textContent = value;
  }
};

const productCardToItem = (productCard) => ({
  id: productCard.dataset.productId,
  title: productCard.dataset.searchTitle,
  category: productCard.dataset.searchCategory,
  price: productCard.dataset.productPrice,
  bookedDates: [...parseBookedDates(productCard)],
});

const isProductBookedOnDate = (productCard, dateKey) => {
  if (!dateKey) {
    return false;
  }

  return parseBookedDates(productCard).has(dateKey);
};

const updateCart = () => {
  if (!cartPanel || !cartItems || !cartCount) {
    return;
  }

  localStorage.setItem("jlyRentalCart", JSON.stringify(cart));
  if (cartJsonInput) {
    cartJsonInput.value = JSON.stringify(cart.map(({ id }) => ({ id })));
  }

  cartPanel.classList.toggle("is-open", cart.length > 0);
  cartCount.textContent = String(cart.length);
  cartItems.innerHTML = "";

  cart.forEach((item) => {
    const isBooked = eventDateInput?.value && item.bookedDates.includes(eventDateInput.value);
    const row = document.createElement("div");
    const content = document.createElement("div");
    const title = document.createElement("strong");
    const price = document.createElement("span");
    const removeButton = document.createElement("button");
    const icon = document.createElement("span");
    row.className = "cart-item";
    title.textContent = item.title;
    price.textContent = item.price;
    content.append(title, price);

    if (isBooked) {
      const warning = document.createElement("em");
      warning.textContent = "Sudah dipesan di tanggal acara";
      content.append(warning);
    }

    icon.className = "material-symbols-outlined";
    icon.setAttribute("aria-hidden", "true");
    icon.textContent = "close";
    removeButton.type = "button";
    removeButton.dataset.cartRemove = item.id;
    removeButton.setAttribute("aria-label", `Hapus ${item.title}`);
    removeButton.append(icon);
    row.append(content, removeButton);
    cartItems.append(row);
  });

  if (bookingSubmit) {
    bookingSubmit.disabled = cart.length === 0;
    bookingSubmit.classList.toggle("is-disabled", cart.length === 0);
  }
};

const addProductToCart = (productCard) => {
  if (!productCard) {
    return;
  }

  const eventDate = eventDateInput?.value || "";

  if (isProductBookedOnDate(productCard, eventDate)) {
    alert("Produk ini sudah dipesan pada tanggal acara yang dipilih.");
    return;
  }

  const item = productCardToItem(productCard);
  const existingIndex = cart.findIndex((cartItem) => cartItem.id === item.id);

  if (existingIndex >= 0) {
    cart[existingIndex] = item;
  } else {
    cart.push(item);
  }

  updateCart();
};

const renderAvailabilityCalendar = () => {
  if (!productModal || !currentProductCard) {
    return;
  }

  const monthLabel = productModal.querySelector("[data-calendar-month]");
  const grid = productModal.querySelector("[data-calendar-grid]");
  const status = productModal.querySelector("[data-calendar-status]");
  const bookedDates = parseBookedDates(currentProductCard);
  const selectedDate = eventDateInput?.value || "";

  if (!monthLabel || !grid) {
    return;
  }

  monthLabel.textContent = monthFormatter.format(calendarMonth);
  grid.innerHTML = "";

  const firstDay = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);
  const daysInMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 0).getDate();

  for (let index = 0; index < firstDay.getDay(); index += 1) {
    const spacer = document.createElement("span");
    spacer.className = "calendar-day is-empty";
    grid.append(spacer);
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), day);
    const dateKey = toDateKey(date);
    const isPast = date < today;
    const isBooked = bookedDates.has(dateKey);
    const button = document.createElement("button");
    button.type = "button";
    button.className = `calendar-day ${isBooked ? "is-booked" : "is-available"} ${selectedDate === dateKey ? "is-selected" : ""}`;
    button.textContent = String(day);
    button.disabled = isPast;
    button.setAttribute("aria-label", `${dateFormatter.format(date)} ${isBooked ? "sudah dipesan" : "tersedia"}`);
    button.addEventListener("click", () => {
      if (eventDateInput) {
        eventDateInput.value = dateKey;
      }
      renderAvailabilityCalendar();
      updateCart();
    });
    grid.append(button);
  }

  if (status) {
    if (!selectedDate) {
      status.textContent = "Pilih tanggal acara untuk cek produk ini.";
    } else if (bookedDates.has(selectedDate)) {
      status.textContent = "Produk ini sudah dipesan pada tanggal acara tersebut.";
    } else {
      status.textContent = "Produk ini tersedia pada tanggal acara tersebut.";
    }
  }

};

const openProductModal = (productCard) => {
  if (!productModal || !productCard) {
    return;
  }

  currentProductCard = productCard;
  calendarMonth = eventDateInput?.value
    ? new Date(`${eventDateInput.value}T00:00:00`)
    : new Date(today.getFullYear(), today.getMonth(), 1);
  calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), 1);

  const title = productCard.dataset.searchTitle;
  const category = productCard.dataset.searchCategory;
  const price = productCard.dataset.productPrice;
  const description = productCard.dataset.productDescription;
  const size = productCard.dataset.productSize;
  const material = productCard.dataset.productMaterial;
  const flowers = productCard.dataset.productFlowers;
  const bundle = productCard.dataset.productBundle;
  const image = productCard.querySelector("img");
  const modalImage = productModal.querySelector("[data-modal-image]");
  const bundleRow = productModal.querySelector("[data-modal-bundle-row]");

  setModalText("[data-modal-title]", title);
  setModalText("[data-modal-category]", category);
  setModalText("[data-modal-price]", price);
  setModalText("[data-modal-description]", description);
  setModalText("[data-modal-size]", size);
  setModalText("[data-modal-material]", material);
  setModalText("[data-modal-flowers]", flowers);
  setModalText("[data-modal-bundle]", bundle);

  if (bundleRow) {
    bundleRow.hidden = !bundle;
  }

  if (modalImage) {
    modalImage.src = image?.src || "";
    modalImage.alt = image?.alt || title;
  }

  renderAvailabilityCalendar();
  productModal.classList.add("is-open");
  productModal.setAttribute("aria-hidden", "false");
  document.body.classList.add("modal-open");
};

const closeProductModal = () => {
  if (!productModal) {
    return;
  }

  productModal.classList.remove("is-open");
  productModal.setAttribute("aria-hidden", "true");
  document.body.classList.remove("modal-open");
};

productDetailButtons.forEach((button) => {
  button.addEventListener("click", () => {
    openProductModal(button.closest(".product-card"));
  });
});

document.querySelectorAll("[data-cart-add]").forEach((button) => {
  button.addEventListener("click", () => {
    addProductToCart(button.closest(".product-card"));
  });
});

modalCartAdd?.addEventListener("click", () => {
  addProductToCart(currentProductCard);
});

productModal?.querySelector("[data-calendar-prev]")?.addEventListener("click", () => {
  calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1, 1);
  renderAvailabilityCalendar();
});

productModal?.querySelector("[data-calendar-next]")?.addEventListener("click", () => {
  calendarMonth = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1, 1);
  renderAvailabilityCalendar();
});

cartItems?.addEventListener("click", (event) => {
  const removeButton = event.target.closest("[data-cart-remove]");

  if (!removeButton) {
    return;
  }

  cart = cart.filter((item) => item.id !== removeButton.dataset.cartRemove);
  updateCart();
});

cartClear?.addEventListener("click", () => {
  cart = [];
  updateCart();
});

eventDateInput?.addEventListener("change", () => {
  renderAvailabilityCalendar();
  updateCart();
});

bookingForm?.addEventListener("submit", (event) => {
  if (cart.length === 0) {
    event.preventDefault();
    alert("Pilih minimal satu produk terlebih dahulu.");
    return;
  }

  if (!eventDateInput?.value) {
    event.preventDefault();
    alert("Pilih tanggal acara terlebih dahulu.");
    return;
  }

  const hasBookedItem = cart.some((item) => item.bookedDates.includes(eventDateInput.value));
  if (hasBookedItem) {
    event.preventDefault();
    alert("Ada produk yang sudah dipesan pada tanggal acara. Hapus produk tersebut atau pilih tanggal lain.");
  }
});

updateCart();

productModal?.querySelectorAll("[data-product-close]").forEach((button) => {
  button.addEventListener("click", closeProductModal);
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeProductModal();
  }
});
