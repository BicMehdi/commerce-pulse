"use strict";
const assert = require("node:assert/strict");
const path = require("node:path");
const fs = require("node:fs");
const { chromium } = require("playwright");

const base = (process.env.BASE_URL || "http://127.0.0.1:8090").replace(/\/$/, "");
if (!["localhost", "127.0.0.1"].includes(new URL(base).hostname)) throw new Error("Browser tests are restricted to a local demo instance");
const output = path.resolve(__dirname, "../test-results/browser");
fs.mkdirSync(output, { recursive: true });
let checks = 0;
const verify = (condition, label) => { assert.ok(condition, label); checks++; console.log("PASS: " + label); };
const unique = Date.now().toString(36);

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.BROWSER_CHANNEL ? { channel: process.env.BROWSER_CHANNEL } : {}) });
  const errors = [];
  const contexts = [];
  const createContext = async options => {
    const context = await browser.newContext(options);
    contexts.push(context);
    context.on("page", page => {
      page.on("pageerror", error => errors.push(error.message));
      page.on("console", message => { if (message.type() === "error" && !message.text().includes("status of 404")) errors.push(message.text()); });
    });
    return context;
  };
  const goto = (page, route) => page.goto(base + route, { waitUntil: "networkidle" });
  const overflow = page => page.evaluate(() => ({ viewport: innerWidth, document: document.documentElement.scrollWidth }));
  const register = async (page, prefix) => {
    await goto(page, "/register");
    await page.getByLabel("Ton nom", { exact: true }).fill("Client Démo " + prefix);
    await page.getByLabel("Adresse email").fill(prefix + "-" + unique + "@commercepulse.test");
    await page.getByLabel("Mot de passe", { exact: true }).fill("Browser-Demo-2026!");
    await page.getByLabel("Confirmer le mot de passe", { exact: true }).fill("Browser-Demo-2026!");
    await Promise.all([page.waitForURL("**/account"), page.getByRole("button", { name: "Créer mon compte", exact: true }).click()]);
    verify(await page.getByRole("heading", { name: /Bonjour/ }).isVisible(), "registration reaches the customer account");
  };
  try {
    const context = await createContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: "no-preference" });
    const page = await context.newPage();
    await goto(page, "/");
    await page.waitForFunction(() => document.querySelector("#hero-title").classList.contains("is-visible"));
    verify(await page.locator("#hero-title").isVisible(), "hero is revealed when it enters the viewport");
    const scene = page.locator(".hero-scene");
    const sceneBox = await scene.boundingBox();
    await page.mouse.move(sceneBox.x + 40, sceneBox.y + 60);
    await page.waitForFunction(() => Math.abs(parseFloat(document.querySelector(".scene-keyboard").style.getPropertyValue("--shift-x"))) > .1);
    verify(true, "object composition responds to pointer movement");
    const firstCard = page.locator("[data-tilt]").first();
    await firstCard.scrollIntoViewIfNeeded();
    const cardBox = await firstCard.boundingBox();
    await page.mouse.move(cardBox.x + 24, cardBox.y + 30);
    await page.waitForFunction(() => Math.abs(parseFloat(document.querySelector("[data-tilt]").style.getPropertyValue("--tilt-x"))) > .1);
    verify(true, "product hover produces a restrained perspective effect");
    for (const item of await page.locator("[data-reveal]").all()) {
      await item.scrollIntoViewIfNeeded();
      await page.waitForFunction(element => element.classList.contains("is-visible"), await item.elementHandle());
    }
    verify(true, "all catalogue sections reveal through real scrolling");
    await page.evaluate(() => scrollTo(0, 0));
    await page.screenshot({ path: path.join(output, "desktop.png"), fullPage: true, animations: "disabled" });

    await goto(page, "/register");
    await page.getByLabel("Mot de passe", { exact: true }).fill("Browser-Demo-2026!");
    await page.getByRole("button", { name: "Afficher le mot de passe", exact: true }).click();
    verify(await page.locator("#password").getAttribute("type") === "text", "password visibility toggle displays the password");
    await page.getByRole("button", { name: "Masquer le mot de passe", exact: true }).click();
    verify(await page.locator("#password").getAttribute("type") === "password", "password visibility toggle hides it again");
    await register(page, "browser-client");
    const catalogue = await (await page.request.get(base + "/api/products")).json();
    const product = catalogue.data.find(item => item.stock > 2);
    assert.ok(product, "demo needs an available product");
    const originalStock = product.stock;
    await goto(page, "/products/" + product.id);
    await page.getByLabel("Quantité", { exact: true }).fill("2");
    await Promise.all([page.waitForURL("**/cart"), page.getByRole("button", { name: /Ajouter au panier/ }).click()]);
    verify(await page.getByRole("spinbutton").inputValue() === "2", "product form adds two units to the cart");
    await page.getByRole("spinbutton").fill("1");
    await Promise.all([page.waitForNavigation({ waitUntil: "networkidle" }), page.getByRole("button", { name: "Mettre à jour" }).click()]);
    verify(await page.getByRole("spinbutton").inputValue() === "1", "cart quantity update survives the enhanced submit state");
    await Promise.all([page.waitForURL(/\/orders\/\d+$/), page.getByRole("button", { name: /Confirmer la commande/ }).click()]);
    const orderRoute = new URL(page.url()).pathname;
    verify(await page.locator(".status").textContent().then(value => value.includes("À préparer")), "checkout reaches a pending order");
    const otherContext = await createContext({ viewport: { width: 1024, height: 900 } });
    const otherPage = await otherContext.newPage();
    await register(otherPage, "browser-other");
    verify((await goto(otherPage, orderRoute)).status() === 404, "another account cannot view this order");

    const adminContext = await createContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: "no-preference" });
    const admin = await adminContext.newPage();
    await goto(admin, "/login");
    await admin.getByLabel("Adresse email").fill("admin@commercepulse.test");
    await admin.getByLabel("Mot de passe", { exact: true }).fill(process.env.DEMO_PASSWORD || "Demo-Pulse-2026!");
    await Promise.all([admin.waitForURL("**/admin"), admin.getByRole("button", { name: "Me connecter", exact: true }).click()]);
    verify(await admin.getByRole("heading", { name: "Vue d’ensemble" }).isVisible(), "admin login reaches the working studio");
    await goto(admin, "/admin/products/new");
    const productName = "Objet de test " + unique;
    await admin.getByLabel("Nom du produit").fill(productName);
    await admin.getByLabel("Description", { exact: true }).fill("Un objet créé uniquement pour vérifier le parcours navigateur.");
    await admin.getByLabel("Prix en euros").fill("59,90");
    await admin.getByLabel("Stock disponible").fill("7");
    await Promise.all([admin.waitForURL("**/admin/products"), admin.getByRole("button", { name: /Enregistrer le produit/ }).click()]);
    await admin.getByRole("searchbox").fill(productName);
    await Promise.all([admin.waitForNavigation({ waitUntil: "networkidle" }), admin.getByRole("button", { name: "Rechercher", exact: true }).click()]);
    verify(await admin.getByText(productName, { exact: true }).isVisible(), "admin product creation is visible in search");
    await admin.getByRole("link", { name: "Modifier", exact: true }).click();
    await admin.getByLabel("Prix en euros").fill("49.95");
    await Promise.all([admin.waitForURL("**/admin/products"), admin.getByRole("button", { name: /Enregistrer le produit/ }).click()]);
    const updated = await (await admin.request.get(base + "/api/products?q=" + encodeURIComponent(productName))).json();
    verify(updated.data[0].price_cents === 4995, "admin edit preserves an exact price in cents");
    const row = admin.getByRole("row").filter({ hasText: productName });
    admin.once("dialog", dialog => dialog.accept());
    await Promise.all([admin.waitForNavigation({ waitUntil: "networkidle" }), row.getByRole("button", { name: "Archiver", exact: true }).click()]);
    verify((await (await admin.request.get(base + "/api/products?q=" + encodeURIComponent(productName))).json()).data.length === 0, "archived product disappears from the public catalogue");

    await goto(admin, orderRoute);
    await admin.getByLabel("Prochain statut").selectOption("cancelled");
    await Promise.all([admin.waitForNavigation({ waitUntil: "networkidle" }), admin.getByRole("button", { name: "Mettre à jour", exact: true }).click()]);
    verify(await admin.locator(".status").textContent().then(value => value.includes("Annulée")), "admin cancellation is shown on the order");
    const afterCancel = await (await admin.request.get(base + "/api/products")).json();
    verify(afterCancel.data.find(item => item.id === product.id).stock === originalStock, "cancellation returns exactly the reserved unit to inventory");
    await goto(admin, "/admin/analytics");
    await Promise.all([admin.waitForNavigation({ waitUntil: "networkidle" }), admin.getByRole("button", { name: "Actualiser l’analyse" }).click()]);
    verify(await admin.getByRole("status").textContent().then(value => value.includes("ont été actualisés")), "the Python report refresh works through the admin form");
    const canvasBox = await admin.locator("canvas").boundingBox();
    await admin.mouse.move(canvasBox.x + canvasBox.width / 2, canvasBox.y + 70);
    verify(await admin.locator(".chart-tooltip").isVisible(), "daily chart displays its value tooltip");
    await admin.mouse.move(5, 5);
    await admin.locator("details summary").click();
    verify(await admin.getByRole("columnheader", { name: "Jour UTC" }).isVisible(), "sales chart has an accessible tabular alternative");
    await admin.locator("details summary").click();

    const responsiveContext = await createContext({ reducedMotion: "reduce" });
    const responsive = await responsiveContext.newPage();
    for (const width of [320, 390, 768, 1024, 1440]) {
      await responsive.setViewportSize({ width, height: 900 });
      for (const route of ["/", "/register", "/login", "/products/" + product.id, "/cart"]) {
        verify((await goto(responsive, route)).status() === 200, "responsive page loads: " + width + "px " + route);
        const dimensions = await overflow(responsive);
        verify(dimensions.document <= dimensions.viewport + 1, "no page overflow: " + width + "px " + route);
      }
    }
    await responsive.setViewportSize({ width: 390, height: 844 });
    await goto(responsive, "/");
    verify(await responsive.locator(".orbit-stamp").evaluate(element => getComputedStyle(element).animationName) === "none", "reduced motion stops the decorative rotation");
    verify(await responsive.locator("#hero-title").evaluate(element => getComputedStyle(element).opacity) === "1", "reduced motion keeps all content visible");
    await responsive.screenshot({ path: path.join(output, "mobile.png"), fullPage: true, animations: "disabled" });
    await goto(responsive, "/register");
    await responsive.screenshot({ path: path.join(output, "register-mobile.png"), fullPage: true, animations: "disabled" });

    for (const width of [320, 390, 768, 1024, 1440]) {
      await admin.setViewportSize({ width, height: 900 });
      for (const route of ["/admin", "/admin/products", "/admin/orders", "/admin/analytics", "/admin/products/new"]) {
        verify((await goto(admin, route)).status() === 200, "admin responsive page loads: " + width + "px " + route);
        const dimensions = await overflow(admin);
        verify(dimensions.document <= dimensions.viewport + 1, "admin has no page overflow: " + width + "px " + route);
        if (route === "/admin" || route === "/admin/analytics") {
          const figuresFit = await admin.locator(".kpi-grid article strong").evaluateAll(elements => elements.every(element => {
            const figure = element.getBoundingClientRect();
            const card = element.closest("article").getBoundingClientRect();
            return figure.right <= card.right && element.scrollWidth <= element.clientWidth + 1;
          }));
          verify(figuresFit, "dashboard figures fit their cards: " + width + "px " + route);
        }
      }
    }
    const noJsContext = await createContext({ javaScriptEnabled: false, reducedMotion: "reduce", viewport: { width: 390, height: 844 } });
    const noJs = await noJsContext.newPage();
    await goto(noJs, "/");
    verify(await noJs.locator(".product-card").first().isVisible(), "catalogue works with JavaScript disabled");
    await register(noJs, "browser-no-js");
    await goto(page, "/account");
    await Promise.all([page.waitForURL(base + "/"), page.getByRole("button", { name: "Se déconnecter", exact: true }).click()]);
    verify(await page.getByRole("link", { name: "Connexion", exact: true }).isVisible(), "customer can log out after checkout");
    verify(errors.length === 0, "no script or console errors: " + errors.join("; "));
    console.log("Browser checks completed: " + checks);
  } finally {
    for (const context of contexts) await context.close();
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
