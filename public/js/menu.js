/* =====================================================
   MENUS + MODAL + CART (API Symfony)
   Compatível com templates/menu/index.html.twig
   ✅ Sem onclick inline (CSP friendly)
   ✅ Checkout seguro via /api/checkout/session (Stripe Checkout)
===================================================== */
(() => {
  const DEFAULT_IMG = document.body?.dataset?.defaultImg || "/uploads/default.jpg";
  const LOGIN_URL = document.body?.dataset?.loginUrl || "/login";

  // Estado
  window.menus = window.menus || [];
  window.currentModalIndex = null;
  window.currentModalMenuId = null;

  const __menuDetailCache = new Map(); // id -> detail

  // ---------------- Helpers ----------------
  function escapeHtml(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value ?? "";
  }

  function toAbsoluteUrl(url) {
    if (!url) return null;
    if (/^https?:\/\//i.test(url)) return url;
    if (url.startsWith("/")) return url;
    return "/" + url;
  }

  function normalizeMenu(menu) {
    const normalized = { ...menu };
    normalized.id = normalized.id ?? normalized.menuId ?? normalized._id ?? null;
    normalized.nom = normalized.nom ?? normalized.name ?? "";
    normalized.preco = Number(normalized.preco ?? normalized.price ?? 0);
    normalized.descricao = normalized.descricao ?? normalized.description ?? "";
    normalized.imageUrl = normalized.imageUrl ?? normalized.image ?? null;
    normalized.stock = Number(normalized.availableStock ?? normalized.stock ?? 0);
    return normalized;
  }

  function pickImage(menu) {
    const img = menu?.imageUrl ?? menu?.image ?? null;
    return toAbsoluteUrl(img) || DEFAULT_IMG;
  }

  // 3 imagens: main + 2 minis (se não existir, repete)
  function pick3Images(menuOrDetail) {
    const images = Array.isArray(menuOrDetail?.images) ? menuOrDetail.images : [];
    const main = toAbsoluteUrl(images?.[0]) || pickImage(menuOrDetail);
    const img2 = toAbsoluteUrl(images?.[1]) || main;
    const img3 = toAbsoluteUrl(images?.[2]) || main;
    return [main, img2, img3];
  }

  function showCartMsg(msg) {
    const box = document.getElementById("cartMsg");
    if (!box) return;
    box.textContent = msg;
    box.style.display = "block";
    setTimeout(() => (box.style.display = "none"), 1800);
  }

  function fmtList(v) {
    if (!v) return "—";
    if (Array.isArray(v)) return v.filter(Boolean).join(", ") || "—";
    return String(v);
  }

  // compatibilidade cart: id/qty OU menuId/quantity
  function cartItemId(it) {
    const id = it?.id ?? it?.menuId ?? it?.menu_id ?? null;
    const n = Number(id);
    return Number.isFinite(n) ? n : NaN;
  }
  function cartItemQty(it) {
    const q = it?.qty ?? it?.quantity ?? it?.qte ?? 0;
    const n = Number(q);
    return Number.isFinite(n) ? n : 0;
  }

  // ---------------- API: menus ----------------
  async function fetchMenuDetail(id) {
    const n = Number(id);
    if (!Number.isFinite(n) || n <= 0) return null;

    const key = String(n);
    if (__menuDetailCache.has(key)) return __menuDetailCache.get(key);

    try {
      const res = await fetch(`/api/menus/${n}`, {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
      });
      if (!res.ok) return null;

      const data = await res.json().catch(() => null);
      __menuDetailCache.set(key, data);
      return data;
    } catch {
      return null;
    }
  }

  // ---------------- API: cart ----------------
  async function getCart() {
    try {
      const res = await fetch("/api/cart", {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
      });
      if (!res.ok) return [];
      const data = await res.json().catch(() => ({}));
      return Array.isArray(data?.items) ? data.items : [];
    } catch {
      return [];
    }
  }

  async function saveCart(cart) {
    const minimal = (cart || [])
      .map((it) => {
        const id = cartItemId(it);
        const qty = Math.max(1, cartItemQty(it) || 1);
        if (!Number.isFinite(id) || id <= 0) return null;
        return { id, qty, menuId: id, quantity: qty };
      })
      .filter(Boolean);

    const res = await fetch("/api/cart/sync", {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ items: minimal }),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
      showCartMsg(data?.message || "Erreur panier.");
      return await getCart();
    }

    const items = Array.isArray(data?.items) ? data.items : minimal;
    await updateCartBadges(items);
    return items;
  }

  async function updateCartBadges(cartMaybe) {
    const cart = cartMaybe ?? (await getCart());
    const count = cart.reduce((s, it) => s + (cartItemQty(it) || 0), 0);

    const fab = document.getElementById("cartBadge");
    if (fab) fab.textContent = String(count);

    const navItem = document.getElementById("nav-cart-item");
    const navCount = document.getElementById("navCartCount");
    if (navCount) navCount.textContent = String(count);
    if (navItem) navItem.style.display = count > 0 ? "" : "none";
  }

  // ---------------- Render menus ----------------
  function renderMenus(items) {
    const grid = document.getElementById("menuGrid");
    if (!grid) return;

    window.menus = (items ?? []).map(normalizeMenu);

    if (!window.menus.length) {
      grid.innerHTML = '<p style="padding:12px; font-weight:800;">Aucun menu trouvé.</p>';
      return;
    }

    grid.innerHTML = window.menus
      .map((menu, idx) => {
        const img = pickImage(menu);
        const name = escapeHtml(menu.nom);
        const price = Number(menu.preco ?? 0).toFixed(2);
        const soldOut = Number(menu.stock ?? 0) <= 0;

        return `
          <div class="menu-item" data-action="open-menu" data-idx="${idx}">
            <img src="${img}" alt="${name}">
            <h4>${name}</h4>
            <p>Prix : €${price}</p>
            <button
              type="button"
              data-action="add-menu"
              data-idx="${idx}"
              ${soldOut ? "disabled" : ""}
            >
              ${soldOut ? "Épuisé" : "Ajouter au panier"}
            </button>
          </div>
        `;
      })
      .join("");
  }

  // ---------------- Load menus ----------------
  async function loadMenus() {
    const status = document.getElementById("menusStatus");

    const q = document.getElementById("q")?.value?.trim() ?? "";
    const minPrice = document.getElementById("minPrice")?.value ?? "";
    const maxPrice = document.getElementById("maxPrice")?.value ?? "";
    const sortVal = document.getElementById("sort")?.value ?? "createdAt|DESC";
    const [sort, order] = sortVal.split("|");

    const params = new URLSearchParams({
      q,
      minPrice,
      maxPrice,
      sort,
      order,
      page: "1",
      limit: "24",
    });

    try {
      if (status) status.textContent = "Chargement...";

      const res = await fetch("/api/menus?" + params.toString(), {
        method: "GET",
        headers: { Accept: "application/json" },
      });

      if (!res.ok) {
        if (status) status.textContent = "Erreur de chargement";
        renderMenus([]);
        return;
      }

      const data = await res.json().catch(() => ({}));
      const items = data?.items ?? (Array.isArray(data) ? data : []);
      renderMenus(items);

      if (status) status.textContent = `${items.length} menu(s)`;
    } catch {
      if (status) status.textContent = "Erreur réseau";
      renderMenus([]);
    }
  }

  function debounceLoad() {
    clearTimeout(window.__menus_t);
    window.__menus_t = setTimeout(loadMenus, 250);
  }

  // ==========================
  // 🧩 MODAL
  // ==========================
  window.openModal = async function (idx) {
    const m0 = window.menus?.[idx];
    if (!m0) return;

    const m = normalizeMenu(m0);
    const id = Number(m.id);
    if (!Number.isFinite(id) || id <= 0) return;

    window.currentModalIndex = idx;
    window.currentModalMenuId = id;

    const detail = await fetchMenuDetail(id);
    const data = detail ? normalizeMenu(detail) : m;

    setText("modalQuantity", "1");

    const [img1, img2, img3] = pick3Images(detail ?? m);
    const modalImg = document.getElementById("modalImg");
    if (modalImg) {
      modalImg.src = img1;
      modalImg.alt = data.nom || "";
    }

    const tg = document.getElementById("thumbnailGrid");
    if (tg) {
      tg.innerHTML = `
        <button type="button" class="thumb" style="border:0;background:transparent;padding:0;cursor:pointer;">
          <img src="${escapeHtml(img2)}" style="width:64px;height:64px;object-fit:cover;border-radius:12px;">
        </button>
        <button type="button" class="thumb" style="border:0;background:transparent;padding:0;cursor:pointer;">
          <img src="${escapeHtml(img3)}" style="width:64px;height:64px;object-fit:cover;border-radius:12px;">
        </button>
      `;
      const btns = tg.querySelectorAll("button.thumb");
      if (btns[0] && modalImg) btns[0].onclick = () => (modalImg.src = img2);
      if (btns[1] && modalImg) btns[1].onclick = () => (modalImg.src = img3);
    }

    setText("itemName", data.nom);
    setText("itemDescription", data.descricao || "");
    setText("itemPrice", `€${Number(data.preco || 0).toFixed(2)}`);

    const available = Number(detail?.availableStock ?? detail?.stock ?? data.stock ?? 0);
    setText("itemStock", available <= 0 ? "Épuisé" : String(available));
    setText("itemIngredients", fmtList(detail?.ingredients ?? detail?.ingredientes));
    setText("itemAllergens", fmtList(detail?.allergens ?? detail?.alergenos));

    const addBtn = document.querySelector("#menuModal .btn-cart");
    if (addBtn) {
      addBtn.disabled = available <= 0;
      addBtn.textContent = available <= 0 ? "Épuisé" : "Ajouter au panier";
    }

    const btnPlus = document.getElementById("btnQtyPlus");
    const btnMinus = document.getElementById("btnQtyMinus");
    const qEl = document.getElementById("modalQuantity");

    const qtyNow = Number(qEl?.textContent || "1");
    if (btnPlus) btnPlus.disabled = available <= 0 || qtyNow >= available;
    if (btnMinus) btnMinus.disabled = qtyNow <= 1;

    const modal = document.getElementById("menuModal");
    if (modal) {
      modal.classList.add("active");
      modal.style.display = "flex";
    }
  };

  window.closeModal = function (e) {
    const modal = document.getElementById("menuModal");
    if (!modal) return;
    if (e && e.target !== modal) return;
    modal.classList.remove("active");
    modal.style.display = "none";
  };

  window.changeQuantityModal = function (delta) {
    const qEl = document.getElementById("modalQuantity");
    if (!qEl) return;

    const current = Number(qEl.textContent || "1");
    const next = Math.max(1, current + (Number(delta) || 0));
    qEl.textContent = String(next);

    const btnPlus = document.getElementById("btnQtyPlus");
    const btnMinus = document.getElementById("btnQtyMinus");

    const stockTxt = document.getElementById("itemStock")?.textContent || "0";
    const available = Number(stockTxt) || 0;

    if (btnPlus) btnPlus.disabled = available <= 0 || next >= available;
    if (btnMinus) btnMinus.disabled = next <= 1;
  };

  // ==========================
  // 🛒 Add to cart (card + modal)
  // ==========================
  async function addById(id, qty) {
    const n = Number(id);
    if (!Number.isFinite(n) || n <= 0) return false;

    const detail = await fetchMenuDetail(n);
    const available = Number(detail?.availableStock ?? detail?.stock ?? 0);

    if (available <= 0) {
      showCartMsg("Épuisé.");
      return false;
    }

    const cart = await getCart();
    const found = cart.find((x) => cartItemId(x) === n);
    const currentQty = cartItemQty(found) || 0;

    if (currentQty + qty > available) {
      showCartMsg(`Stock insuffisant (max ${available}).`);
      return false;
    }

    if (found) {
      found.qty = currentQty + qty;
      found.quantity = currentQty + qty;
    } else {
      cart.push({ id: n, qty, menuId: n, quantity: qty });
    }

    await saveCart(cart);
    __menuDetailCache.delete(String(n));
    await updateCartBadges();
    return true;
  }

  window.addToCart = async function (event, idx) {
    event?.preventDefault?.();
    event?.stopPropagation?.();

    const m0 = window.menus?.[idx];
    if (!m0) return;

    const m = normalizeMenu(m0);
    const id = Number(m.id);
    if (!Number.isFinite(id) || id <= 0) return;

    const ok = await addById(id, 1);
    if (ok) showCartMsg("Ajouté au panier ✅");
  };

  window.addToCartFromModal = async function () {
    const id = Number(window.currentModalMenuId);
    if (!Number.isFinite(id) || id <= 0) return;

    const qty = Math.max(1, Number(document.getElementById("modalQuantity")?.textContent || "1"));
    const ok = await addById(id, qty);
    if (!ok) return;

    const detail = await fetchMenuDetail(id);
    const available = Number(detail?.availableStock ?? detail?.stock ?? 0);
    setText("itemStock", available <= 0 ? "Épuisé" : String(available));

    await window.openCart(true);
  };

  // ==========================
  // 🛒 CART UI
  // ==========================
  async function renderCart() {
    const wrap = document.getElementById("cartItems");
    const totalEl = document.getElementById("cartTotal");
    if (!wrap || !totalEl) return;

    const baseCart = await getCart();

    const ids = baseCart
      .map((it) => cartItemId(it))
      .filter((x) => Number.isFinite(x) && x > 0);

    await Promise.all(ids.map((id) => fetchMenuDetail(id)));

    const items = baseCart
      .map((it) => {
        const id = cartItemId(it);
        if (!Number.isFinite(id) || id <= 0) return null;

        const d = __menuDetailCache.get(String(id));
        const available = Number(d?.availableStock ?? d?.stock ?? 0);

        return {
          id,
          qty: Math.max(1, cartItemQty(it) || 1),
          name: it.name ?? it.nom ?? d?.nom ?? d?.name ?? "—",
          price: Number(it.price ?? it.preco ?? d?.preco ?? d?.price ?? 0) || 0,
          image: it.image ?? it.imageUrl ?? d?.imageUrl ?? d?.image ?? DEFAULT_IMG,
          available,
        };
      })
      .filter(Boolean);

    const total = items.reduce((s, it) => s + it.qty * it.price, 0);
    totalEl.textContent = `€${total.toFixed(2)}`;

    if (!items.length) {
      wrap.innerHTML = `<p style="margin:6px 0;color:#64748b;font-weight:800;">Panier vide.</p>`;
      return;
    }

    wrap.innerHTML = items
      .map((it) => {
        const canInc = it.available > 0 && it.qty < it.available;

        return `
          <div class="cart-item" style="align-items:center;" data-cart-id="${escapeHtml(it.id)}">
            <img src="${escapeHtml(it.image)}" alt="${escapeHtml(it.name)}">
            <div class="meta">
              <strong>${escapeHtml(it.name)}</strong>
              <small>€${it.price.toFixed(2)} • Stock: ${Math.max(0, it.available)}</small>
            </div>

            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
              <button type="button" class="cart-del" title="Supprimer" data-action="cart-remove">🗑️</button>
              <div class="cart-qty">
                <button type="button" data-action="cart-qty" data-delta="-1">−</button>
                <span>${it.qty}</span>
                <button type="button" data-action="cart-qty" data-delta="1" ${canInc ? "" : "disabled"}>+</button>
              </div>
            </div>
          </div>
        `;
      })
      .join("");
  }

  window.openCart = async function (fromQuickAdd = false) {
    const modal = document.getElementById("cartModal");
    if (!modal) return;
    modal.style.display = "flex";
    await renderCart();
    if (fromQuickAdd) showCartMsg("Ajouté au panier ✅");
  };

  window.closeCart = function (e) {
    const modal = document.getElementById("cartModal");
    if (!modal) return;
    if (e && e.target !== modal) return;
    modal.style.display = "none";
  };

  window.clearCart = async function () {
    await saveCart([]);
    await renderCart();
  };

  window.removeFromCart = async function (id) {
    const n = Number(id);
    const cart = await getCart();
    const filtered = cart.filter((x) => cartItemId(x) !== n);
    await saveCart(filtered);
    await renderCart();
  };

  window.changeCartQty = async function (id, delta) {
    const n = Number(id);
    if (!Number.isFinite(n) || n <= 0) return;

    const cart = await getCart();
    const it = cart.find((x) => cartItemId(x) === n);
    if (!it) return;

    const d = Number(delta) || 0;
    const current = cartItemQty(it) || 1;
    const nextQty = Math.max(1, current + d);

    if (d > 0) {
      const detail = await fetchMenuDetail(n);
      const available = Number(detail?.availableStock ?? detail?.stock ?? 0);
      if (available <= 0) return void showCartMsg("Épuisé.");
      if (nextQty > available) return void showCartMsg(`Stock insuffisant (max ${available}).`);
    }

    it.qty = nextQty;
    it.quantity = nextQty;

    await saveCart(cart);
    __menuDetailCache.delete(String(n));
    await renderCart();
  };

  // ==========================
  // Checkout (✅ seguro: cria sessão no backend e redireciona)
  // ==========================
  async function isLogged() {
    try {
      const r = await fetch("/api/me", {
        method: "GET",
        credentials: "include",
        headers: { Accept: "application/json" },
      });
      return r.ok;
    } catch {
      return false;
    }
  }

  function getCheckoutKey() {
    const k = sessionStorage.getItem("checkoutKey");
    if (k && k.length >= 12) return k;
    const fresh =
      (globalThis.crypto?.randomUUID ? globalThis.crypto.randomUUID() : null) ||
      `${Date.now()}_${Math.random().toString(16).slice(2)}`;
    sessionStorage.setItem("checkoutKey", fresh);
    return fresh;
  }

  function clearCheckoutKey() {
    sessionStorage.removeItem("checkoutKey");
  }

  window.checkout = async function () {
    const cart = await getCart();
    if (!cart.length) return void showCartMsg("Panier vide.");

    const ok = await isLogged();
    if (!ok) return void (window.location.href = LOGIN_URL);

    try {
      const res = await fetch("/api/checkout/session", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({
          idempotencyKey: getCheckoutKey(),
          items: cart.map((it) => ({
            menuId: cartItemId(it),
            quantity: cartItemQty(it),
          })),
        }),
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok) return void showCartMsg(data?.message || "Impossible de payer.");

      if (!data?.checkoutUrl) return void showCartMsg("URL de paiement manquante.");
      window.location.href = data.checkoutUrl;
    } catch {
      showCartMsg("Erreur réseau au paiement.");
    }
  };

  // ==========================
  // Boot
  // ==========================
  function boot() {
    document.getElementById("btnApply")?.addEventListener("click", loadMenus);
    document.getElementById("q")?.addEventListener("input", debounceLoad);
    document.getElementById("minPrice")?.addEventListener("input", debounceLoad);
    document.getElementById("maxPrice")?.addEventListener("input", debounceLoad);
    document.getElementById("sort")?.addEventListener("change", loadMenus);

    document.getElementById("cartFab")?.addEventListener("click", () => window.openCart());
    document.getElementById("navCartBtn")?.addEventListener("click", () => window.openCart());

    // Fechar cart clicando fora
    document.getElementById("cartModal")?.addEventListener("click", (e) => {
      if (e.target === e.currentTarget) window.closeCart(e);
    });

    // ✅ Event delegation MENUS (abre modal / add ao carrinho)
    document.getElementById("menuGrid")?.addEventListener("click", (e) => {
      const addBtn = e.target.closest('[data-action="add-menu"]');
      if (addBtn) {
        e.preventDefault();
        e.stopPropagation();
        const idx = Number(addBtn.dataset.idx);
        if (Number.isFinite(idx)) window.addToCart(e, idx);
        return;
      }

      const card = e.target.closest('[data-action="open-menu"]');
      if (card) {
        const idx = Number(card.dataset.idx);
        if (Number.isFinite(idx)) window.openModal(idx);
      }
    });

    // ✅ Event delegation CARRINHO (🗑️ e +/-)
    document.getElementById("cartItems")?.addEventListener("click", async (e) => {
      const row = e.target.closest(".cart-item");
      const id = Number(row?.dataset?.cartId);
      if (!Number.isFinite(id) || id <= 0) return;

      const del = e.target.closest('[data-action="cart-remove"]');
      if (del) {
        e.preventDefault();
        await window.removeFromCart(id);
        clearCheckoutKey(); // carrinho mudou -> novo checkout
        return;
      }

      const qtyBtn = e.target.closest('[data-action="cart-qty"]');
      if (qtyBtn) {
        e.preventDefault();
        const delta = Number(qtyBtn.dataset.delta || 0);
        await window.changeCartQty(id, delta);
        clearCheckoutKey(); // carrinho mudou -> novo checkout
      }
    });

    // Fechar modal clicando fora
    document.getElementById("menuModal")?.addEventListener("click", (e) => {
      if (e.target === e.currentTarget) window.closeModal(e);
    });

    // Botões +/- do modal (se existirem)
    document
      .getElementById("btnQtyPlus")
      ?.addEventListener("click", () => window.changeQuantityModal(1));
    document
      .getElementById("btnQtyMinus")
      ?.addEventListener("click", () => window.changeQuantityModal(-1));

    // Botão adicionar do modal (se existir)
    document
      .querySelector("#menuModal .btn-cart")
      ?.addEventListener("click", () => {
        clearCheckoutKey(); // carrinho muda
        window.addToCartFromModal();
      });

    const yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = new Date().getFullYear();

    updateCartBadges();
    loadMenus();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", boot);
  else boot();
})();
