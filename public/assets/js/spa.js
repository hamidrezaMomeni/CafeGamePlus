(function ($, window, document) {
    "use strict";

    const Spa = {
        modalInstance: null,
        confirmInstance: null,
        pendingConfirmAction: null,
        liveTimerIntervalId: null,
        autoCloseIntervalId: null,

        init() {
            this.setupAjax();
            this.setupBootstrap();
            this.bindEvents();
            this.initTheme();
            this.initSidebarState();
            this.startLiveTimers();
            this.startAutoCloseTick();
            this.updateActiveNav(window.location.pathname);

            window.addEventListener("popstate", () => {
                this.loadIntoMain(window.location.href, { pushState: false });
            });

            document.addEventListener("keydown", (e) => {
                if (e.key === "Escape") this.closeSidebar();
            });
        },

        isMobile() {
            return window.matchMedia && window.matchMedia("(max-width: 991.98px)").matches;
        },

        openSidebar() {
            document.body.classList.add("sidebar-open");
        },

        closeSidebar() {
            document.body.classList.remove("sidebar-open");
        },

        toggleSidebar() {
            if (document.body.classList.contains("sidebar-open")) this.closeSidebar();
            else this.openSidebar();
        },

        isSidebarCollapsed() {
            return document.body.classList.contains("sidebar-collapsed");
        },

        setSidebarCollapsed(collapsed, { persist = true } = {}) {
            const next = !!collapsed;
            if (next) document.body.classList.add("sidebar-collapsed");
            else document.body.classList.remove("sidebar-collapsed");

            this.updateSidebarCollapseIcon();

            if (!persist) return;
            try {
                window.localStorage?.setItem("cgp_sidebar_collapsed", next ? "1" : "0");
            } catch {
                // ignore
            }
        },

        applySavedSidebarCollapsed() {
            if (this.isMobile()) {
                document.body.classList.remove("sidebar-collapsed");
                this.updateSidebarCollapseIcon();
                return;
            }

            let saved = null;
            try {
                saved = window.localStorage?.getItem("cgp_sidebar_collapsed");
            } catch {
                saved = null;
            }

            this.setSidebarCollapsed(saved === "1", { persist: false });
        },

        initSidebarState() {
            this.applySavedSidebarCollapsed();
            window.addEventListener("resize", () => this.applySavedSidebarCollapsed());
        },

        toggleSidebarCollapsed() {
            if (this.isMobile()) return;
            this.setSidebarCollapsed(!this.isSidebarCollapsed(), { persist: true });
        },

        updateSidebarCollapseIcon() {
            const btn = document.getElementById("sidebarCollapseBtn");
            if (!btn) return;
            const icon = btn.querySelector("i");
            if (!icon) return;

            if (this.isSidebarCollapsed()) {
                icon.className = "bi bi-layout-sidebar-inset";
                btn.setAttribute("title", "باز کردن منو");
                btn.setAttribute("aria-label", "باز کردن منو");
            } else {
                icon.className = "bi bi-layout-sidebar-inset-reverse";
                btn.setAttribute("title", "جمع کردن منو");
                btn.setAttribute("aria-label", "جمع کردن منو");
            }
        },

        getTheme() {
            return document.body?.getAttribute("data-theme") || "light";
        },

        setTheme(theme, { persist = true } = {}) {
            const normalized = theme === "dark" ? "dark" : "light";
            document.body?.setAttribute("data-theme", normalized);
            this.updateThemeToggleIcon(normalized);

            if (!persist) return;
            try {
                window.localStorage?.setItem("cgp_theme", normalized);
            } catch {
                // ignore
            }
        },

        initTheme() {
            let saved = null;
            try {
                saved = window.localStorage?.getItem("cgp_theme");
            } catch {
                saved = null;
            }

            if (saved === "dark" || saved === "light") {
                this.setTheme(saved, { persist: false });
                return;
            }

            this.updateThemeToggleIcon(this.getTheme());
        },

        toggleTheme() {
            const next = this.getTheme() === "dark" ? "light" : "dark";
            this.setTheme(next, { persist: true });
        },

        updateThemeToggleIcon(theme) {
            const btn = document.getElementById("themeToggleBtn");
            if (!btn) return;
            const icon = btn.querySelector("i");
            if (!icon) return;

            if (theme === "dark") {
                icon.className = "bi bi-sun";
                btn.setAttribute("title", "حالت روشن");
                btn.setAttribute("aria-label", "حالت روشن");
            } else {
                icon.className = "bi bi-moon-stars";
                btn.setAttribute("title", "حالت تیره");
                btn.setAttribute("aria-label", "حالت تیره");
            }
        },

        setupAjax() {
            const csrf = $('meta[name="csrf-token"]').attr("content");
            $.ajaxSetup({
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
            });
        },

        setupBootstrap() {
            const modalEl = document.getElementById("spaModal");
            if (modalEl) {
                this.modalInstance = new bootstrap.Modal(modalEl, { backdrop: "static" });
            }

            const confirmEl = document.getElementById("spaConfirmModal");
            if (confirmEl) {
                this.confirmInstance = new bootstrap.Modal(confirmEl);
            }
        },

        bindEvents() {
            $(document).on("click", "a[data-spa-nav]", (e) => {
                if (!this.isEligibleClick(e)) return;
                const href = $(e.currentTarget).attr("href");
                if (!href) return;
                e.preventDefault();
                this.loadIntoMain(href, { pushState: true });
                if (this.isMobile()) this.closeSidebar();
            });

            $(document).on("click", "a[data-spa-modal]", (e) => {
                if (!this.isEligibleClick(e)) return;
                const $link = $(e.currentTarget);
                const href = $link.attr("href");
                if (!href) return;
                e.preventDefault();
                this.openModal(href, {
                    title: $link.data("modalTitle") || null,
                    size: $link.data("modalSize") || null,
                });
            });

            $(document).on("click", "#spa-content a", (e) => {
                const $a = $(e.currentTarget);
                if ($a.is("[data-no-spa]") || $a.is("[data-spa-modal]") || $a.is("[data-spa-nav]")) return;
                if (!this.isEligibleClick(e)) return;
                const href = $a.attr("href");
                if (!href || href.startsWith("#")) return;
                e.preventDefault();
                this.loadIntoMain(href, { pushState: true });
            });

            $(document).on("submit", "#spaModal form", (e) => {
                const $form = $(e.currentTarget);
                if ($form.is("[data-no-spa]")) return;

                if ($form.is("[data-spa-confirm]")) {
                    e.preventDefault();
                    this.askConfirm({
                        title: $form.data("confirmTitle") || "تایید عملیات",
                        message: $form.data("confirmMessage") || "آیا مطمئن هستید؟",
                        onConfirm: () => this.submitForm($form, { inModal: true }),
                    });
                    return;
                }

                e.preventDefault();
                this.submitForm($form, { inModal: true });
            });

            $(document).on("click", "#spaModal a", (e) => {
                const $a = $(e.currentTarget);
                if ($a.is("[data-no-spa]")) return;
                if ($a.is("[data-spa-modal]") || $a.is("[data-spa-nav]")) return;
                if (!this.isEligibleClick(e)) return;

                const href = $a.attr("href");
                if (!href || href.startsWith("#")) return;

                e.preventDefault();

                const targetUrl = this.stripSpaParam(new URL(href, window.location.origin).toString());
                const currentUrl = this.stripSpaParam(window.location.href);

                if (this.modalInstance) this.modalInstance.hide();
                if (targetUrl === currentUrl) return;

                this.loadIntoMain(targetUrl, { pushState: true });
            });

            $(document).on("submit", "form[data-spa-submit]", (e) => {
                const $form = $(e.currentTarget);
                if ($form.is("[data-no-spa]")) return;
                if ($form.closest("#spaModal").length) return;
                e.preventDefault();
                this.submitForm($form, { inModal: false });
            });

            $(document).on("submit", "form[data-spa-confirm]", (e) => {
                const $form = $(e.currentTarget);
                if ($form.is("[data-no-spa]")) return;
                if ($form.closest("#spaModal").length) return;
                e.preventDefault();
                this.askConfirm({
                    title: $form.data("confirmTitle") || "تایید عملیات",
                    message: $form.data("confirmMessage") || "آیا مطمئن هستید؟",
                    onConfirm: () => this.submitForm($form, { inModal: false }),
                });
            });

            $(document).on("click", "#spaConfirmBtn", (e) => {
                e.preventDefault();
                const action = this.pendingConfirmAction;
                this.pendingConfirmAction = null;
                if (this.confirmInstance) this.confirmInstance.hide();
                if (typeof action === "function") action();
            });

            // Shell UI
            $(document).on("click", "#sidebarToggleBtn", (e) => {
                e.preventDefault();
                this.openSidebar();
            });

            $(document).on("click", "#sidebarCloseBtn, #appOverlay", (e) => {
                e.preventDefault();
                this.closeSidebar();
            });

            $(document).on("click", "#sidebarCollapseBtn", (e) => {
                e.preventDefault();
                this.toggleSidebarCollapsed();
            });

            $(document).on("click", "#themeToggleBtn", (e) => {
                e.preventDefault();
                this.toggleTheme();
            });

            // Console session rate helper
            $(document).on("change", "#consoleSelect, #controllerCount", () => {
                this.updateConsoleSessionRateInfo();
            });

            // Table session rate helper
            $(document).on("change", "#tableSelect", () => {
                this.updateTableSessionRateInfo();
            });

            // Board game session rate helper
            $(document).on("change", "#boardGameSelect", () => {
                this.updateBoardGameSessionRateInfo();
            });

            // Order form helpers
            $(document).on("click", "#addItem", (e) => {
                e.preventDefault();
                this.addOrderItem($(e.currentTarget));
            });

            $(document).on("change", ".order-item .item-select", (e) => {
                const $form = $(e.currentTarget).closest("form");
                this.updateOrderTotal($form);
            });

            $(document).on("input", ".order-item .quantity-input", (e) => {
                const $form = $(e.currentTarget).closest("form");
                this.updateOrderTotal($form);
            });

            $(document).on("click", ".order-item .remove-item", (e) => {
                e.preventDefault();
                const $row = $(e.currentTarget).closest(".order-item");
                const $form = $row.closest("form");
                $row.remove();
                this.updateOrderTotal($form);
            });
        },

        isEligibleClick(e) {
            if (e.defaultPrevented) return false;
            if (e.button !== 0) return false;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;

            const a = e.currentTarget;
            if (!a || a.tagName !== "A") return false;
            if (a.target && a.target !== "_self") return false;
            if (a.hasAttribute("download")) return false;

            const href = a.getAttribute("href");
            if (!href || href.startsWith("mailto:") || href.startsWith("tel:")) return false;

            try {
                const url = new URL(href, window.location.origin);
                if (url.origin !== window.location.origin) return false;
            } catch {
                return false;
            }

            return true;
        },

        withSpaParam(url) {
            const u = new URL(url, window.location.origin);
            u.searchParams.set("_spa", "1");
            return u.toString();
        },

        stripSpaParam(url) {
            try {
                const u = new URL(url, window.location.origin);
                u.searchParams.delete("_spa");
                return u.toString();
            } catch {
                return url;
            }
        },

        showLoading(show) {
            const el = document.getElementById("spa-loading");
            if (!el) return;
            el.classList.toggle("d-none", !show);
        },

        clearAlerts() {
            $("#spa-alerts").empty();
        },

        pushAlert(type, text) {
            const bsType =
                type === "success"
                    ? "success"
                    : type === "danger"
                      ? "danger"
                      : type === "warning"
                        ? "warning"
                        : "info";

            const html = `
                <div class="alert alert-${bsType} alert-dismissible fade show" role="alert">
                    ${this.escapeHtml(text)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $("#spa-alerts").append(html);
        },

        escapeHtml(str) {
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        formatFaNumber(value) {
            const num = Number(value);
            return new Intl.NumberFormat("fa-IR").format(Number.isFinite(num) ? num : 0);
        },

        toFaDigits(input) {
            return String(input).replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[Number(d)]);
        },

        pad2(value) {
            return String(value).padStart(2, "0");
        },

        startLiveTimers() {
            if (this.liveTimerIntervalId) return;
            this.liveTimerIntervalId = window.setInterval(() => this.updateLiveTimers(), 1000);
            this.updateLiveTimers();
        },

        startAutoCloseTick() {
            if (this.autoCloseIntervalId) return;

            const hasCsrf = $('meta[name="csrf-token"]').length > 0;
            if (!hasCsrf) return;

            this.autoCloseIntervalId = window.setInterval(() => this.tickAutoClose(), 30000);
            this.tickAutoClose();
        },

        tickAutoClose() {
            return $.ajax({
                url: "/system/tick",
                method: "POST",
                dataType: "json",
            })
                .done((data) => {
                    const endedConsole = Number(data?.ended_console_sessions || 0);
                    const endedTable = Number(data?.ended_table_sessions || 0);
                    const endedBoardGame = Number(data?.ended_board_game_sessions || 0);
                    const endedTotal = endedConsole + endedTable + endedBoardGame;
                    if (!endedTotal) return;

                    this.pushAlert("info", `${this.toFaDigits(String(endedTotal))} سشن به صورت خودکار بسته شد.`);

                    if (window.location.pathname === "/" || window.location.pathname === "") {
                        this.loadIntoMain(window.location.href, { pushState: false, preserveAlerts: true });
                    }
                })
                .fail(() => {
                    // ignore
                });
        },

        updateLiveTimers() {
            const now = Date.now();
            document.querySelectorAll("[data-live-timer][data-timer-start]").forEach((el) => {
                const raw = el.getAttribute("data-timer-start");
                if (!raw) return;

                let start = Number(raw);
                if (!Number.isFinite(start) || start <= 0) return;

                // seconds -> ms
                if (start < 1e12) start *= 1000;

                const diffMs = Math.max(0, now - start);
                const totalSeconds = Math.floor(diffMs / 1000);
                const hours = Math.floor(totalSeconds / 3600);
                const minutes = Math.floor((totalSeconds % 3600) / 60);
                const seconds = totalSeconds % 60;

                const hourText = hours < 100 ? this.pad2(hours) : String(hours);
                const text = `${hourText}:${this.pad2(minutes)}:${this.pad2(seconds)}`;
                el.textContent = this.toFaDigits(text);
            });
        },

        updateConsoleSessionRateInfo() {
            const consoleSelect = document.getElementById("consoleSelect");
            const controllerCount = document.getElementById("controllerCount");
            const priceInfo = document.getElementById("priceInfo");
            const rateDisplay = document.getElementById("rateDisplay");

            if (!consoleSelect || !controllerCount || !priceInfo || !rateDisplay) return;

            const selectedOption = consoleSelect.options[consoleSelect.selectedIndex];
            const count = controllerCount.value;

            if (!selectedOption || !selectedOption.value) {
                priceInfo.style.display = "none";
                return;
            }

            let rate;
            if (count === "1") rate = selectedOption.dataset.single;
            else if (count === "2") rate = selectedOption.dataset.double;
            else if (count === "3") rate = selectedOption.dataset.triple;
            else rate = selectedOption.dataset.quadruple;

            rateDisplay.textContent = `${this.formatFaNumber(rate)} تومان در ساعت`;
            priceInfo.style.display = "block";
        },

        updateTableSessionRateInfo() {
            const tableSelect = document.getElementById("tableSelect");
            const priceInfo = document.getElementById("priceInfo");
            const rateDisplay = document.getElementById("rateDisplay");

            if (!tableSelect || !priceInfo || !rateDisplay) return;

            const selectedOption = tableSelect.options[tableSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                priceInfo.style.display = "none";
                return;
            }

            const rate = selectedOption.dataset.rate;
            rateDisplay.textContent = `${this.formatFaNumber(rate)} تومان در ساعت`;
            priceInfo.style.display = "block";
        },

        updateBoardGameSessionRateInfo() {
            const boardGameSelect = document.getElementById("boardGameSelect");
            const priceInfo = document.getElementById("priceInfo");
            const rateDisplay = document.getElementById("rateDisplay");

            if (!boardGameSelect || !priceInfo || !rateDisplay) return;

            const selectedOption = boardGameSelect.options[boardGameSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                priceInfo.style.display = "none";
                return;
            }

            const rate = selectedOption.dataset.rate;
            rateDisplay.textContent = `${this.formatFaNumber(rate)} تومان در ساعت`;
            priceInfo.style.display = "block";
        },

        addOrderItem($button) {
            const $form = $button.closest("form");
            const $itemsWrap = $form.find("#orderItems");
            const $template = $itemsWrap.find(".order-item").first();

            if ($template.length === 0) return;

            const newIndex = $itemsWrap.find(".order-item").length;
            const $newItem = $template.clone(false, false);

            $newItem.find("select, input").each(function () {
                const $el = $(this);
                const name = $el.attr("name");
                if (name) $el.attr("name", name.replace(/\[\d+\]/, `[${newIndex}]`));

                if (this.tagName === "SELECT") {
                    this.selectedIndex = 0;
                } else {
                    $el.val(1);
                }
            });

            $newItem.find(".remove-item").css("display", "block");
            $itemsWrap.append($newItem);
            this.updateOrderTotal($form);
        },

        updateOrderTotal($form) {
            if (!$form || $form.length === 0) return;

            let total = 0;
            $form.find(".order-item").each(function () {
                const $row = $(this);
                const $select = $row.find(".item-select");
                const $qty = $row.find(".quantity-input");

                const option = $select.find("option:selected");
                const id = option.val();
                if (!id) return;

                const price = Number(option.data("price")) || 0;
                const quantity = Number($qty.val()) || 0;
                total += price * quantity;
            });

            $form.find("#totalAmount").text(`${this.formatFaNumber(total)} تومان`);
        },

        extract(html) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, "text/html");

            const title = doc.querySelector("title")?.textContent?.trim() || null;
            const contentEl = doc.querySelector("#spa-content");

            const contentHtml = contentEl ? contentEl.innerHTML : (doc.body?.innerHTML || html);
            const alerts = [];

            doc.querySelectorAll("#spa-alerts .alert").forEach((el) => {
                const type = el.classList.contains("alert-success")
                    ? "success"
                    : el.classList.contains("alert-danger")
                      ? "danger"
                      : el.classList.contains("alert-warning")
                        ? "warning"
                        : "info";

                const text = (el.textContent || "").trim();
                if (text) alerts.push({ type, text });
            });

            const hasValidationErrors = doc.querySelectorAll(".is-invalid, .invalid-feedback").length > 0;

            const looksLikeLogin = !!doc.querySelector('form[action*="/login"] input[name="username"]');

            return { title, contentHtml, alerts, hasValidationErrors, looksLikeLogin };
        },

        setTitle(title) {
            if (!title) return;
            document.title = title;
            $(".app-topbar .page-title").text(title);
        },

        setMain(contentHtml, title) {
            this.setTitle(title);
            $("#spa-content").html(contentHtml);
            this.afterDomUpdate($("#spa-content"));
            this.updateActiveNav(window.location.pathname);
        },

        afterDomUpdate($root) {
            this.updateConsoleSessionRateInfo();
            this.updateTableSessionRateInfo();
            this.updateBoardGameSessionRateInfo();

            const $orderForm = $root.find("#orderForm");
            if ($orderForm.length) this.updateOrderTotal($orderForm);

            const $pricingPlanForm = $root.find("[data-pricing-plan-form]");
            if ($pricingPlanForm.length) this.initPricingPlanForm($pricingPlanForm);

            this.updateLiveTimers();
        },

        initPricingPlanForm($form) {
            const $type = $form.find("#pricingPlanType");
            if ($type.length === 0) return;

            const toggle = () => {
                const type = $type.val();
                $form.find("[data-plan-config]").each(function () {
                    const $section = $(this);
                    const sectionType = $section.attr("data-plan-config");
                    const isActive = sectionType === type;

                    $section.toggleClass("d-none", !isActive);
                    $section.find("input, select, textarea, button").prop("disabled", !isActive);
                });
            };

            $type.off("change.spaPricingPlan").on("change.spaPricingPlan", toggle);
            toggle();
        },

        updateActiveNav(pathname) {
            const normalized = (pathname || "/").replace(/\/+$/, "") || "/";
            const firstSegment = normalized.split("/").filter(Boolean)[0] || null;

            $(".app-sidebar .nav-link").removeClass("active");

            let matchEl = null;
            document.querySelectorAll(".app-sidebar .nav-link").forEach((el) => {
                if (matchEl) return;
                const href = el.getAttribute("href");
                if (!href) return;

                try {
                    const p = (new URL(href, window.location.origin).pathname || "/").replace(/\/+$/, "") || "/";
                    if (p === normalized) matchEl = el;
                } catch {
                    // ignore
                }
            });

            if (!matchEl && firstSegment) {
                document.querySelectorAll(".app-sidebar .nav-link").forEach((el) => {
                    if (matchEl) return;
                    const href = el.getAttribute("href");
                    if (!href) return;

                    try {
                        const p = (new URL(href, window.location.origin).pathname || "/").replace(/\/+$/, "") || "/";
                        if (p.startsWith(`/${firstSegment}`)) matchEl = el;
                    } catch {
                        // ignore
                    }
                });
            }

            if (matchEl) matchEl.classList.add("active");
        },

        loadIntoMain(url, { pushState, preserveAlerts = false }) {
            this.showLoading(true);
            if (!preserveAlerts) this.clearAlerts();

            const requestUrl = this.withSpaParam(url);
            return $.ajax({ url: requestUrl, method: "GET" })
                .done((html, _status, jqXHR) => {
                    const finalUrlRaw = jqXHR.responseURL || url;
                    const finalUrl = this.stripSpaParam(finalUrlRaw);
                    const extracted = this.extract(html);
                    if (extracted.looksLikeLogin) {
                        window.location.href = "/login";
                        return;
                    }

                    if (pushState) {
                        window.history.pushState({}, "", finalUrl);
                    }

                    this.setMain(extracted.contentHtml, extracted.title);
                    extracted.alerts.forEach((a) => this.pushAlert(a.type, a.text));
                })
                .fail((jqXHR) => {
                    const message =
                        jqXHR.status === 404
                            ? "صفحه مورد نظر پیدا نشد."
                            : "خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.";
                    this.pushAlert("danger", message);
                })
                .always(() => this.showLoading(false));
        },

        openModal(url, { title, size }) {
            if (!this.modalInstance) return;
            this.showLoading(true);

            const requestUrl = this.withSpaParam(url);
            return $.ajax({ url: requestUrl, method: "GET" })
                .done((html) => {
                    const extracted = this.extract(html);
                    if (extracted.looksLikeLogin) {
                        window.location.href = "/login";
                        return;
                    }

                    const modalTitle = title || extracted.title || "جزئیات";
                    $("#spaModalTitle").text(modalTitle);
                    $("#spaModalBody").html(extracted.contentHtml);
                    this.afterDomUpdate($("#spaModalBody"));
                    this.applyModalSize(size);
                    this.modalInstance.show();
                })
                .fail(() => {
                    this.pushAlert("danger", "بارگذاری اطلاعات با خطا مواجه شد.");
                })
                .always(() => this.showLoading(false));
        },

        applyModalSize(size) {
            const $dialog = $("#spaModalDialog");
            $dialog.removeClass("modal-sm modal-lg modal-xl modal-fullscreen");
            if (!size) return;
            if (["modal-sm", "modal-lg", "modal-xl", "modal-fullscreen"].includes(size)) {
                $dialog.addClass(size);
            }
        },

        submitForm($form, { inModal }) {
            const method = ($form.attr("method") || "POST").toUpperCase();
            const action = $form.attr("action") || window.location.href;

            if (method === "GET") {
                const query = $form.serialize();
                const url = query ? `${action}?${query}` : action;
                if (inModal && this.modalInstance) this.modalInstance.hide();
                return this.loadIntoMain(url, { pushState: true });
            }

            this.showLoading(true);
            this.clearAlerts();

            const formEl = $form.get(0);
            const data = new FormData(formEl);

            return $.ajax({
                url: action,
                method,
                data,
                processData: false,
                contentType: false,
                headers: { "X-SPA": "1" },
            })
                .done((html, _status, jqXHR) => {
                    const finalUrl = jqXHR.responseURL || action;
                    const extracted = this.extract(html);

                    if (extracted.looksLikeLogin) {
                        window.location.href = "/login";
                        return;
                    }

                    if (inModal && extracted.hasValidationErrors) {
                        $("#spaModalBody").html(extracted.contentHtml);
                        this.afterDomUpdate($("#spaModalBody"));
                        extracted.alerts.forEach((a) => this.pushAlert(a.type, a.text));
                        return;
                    }

                    if (inModal && this.modalInstance) this.modalInstance.hide();
                    window.history.pushState({}, "", finalUrl);
                    this.setMain(extracted.contentHtml, extracted.title);
                    extracted.alerts.forEach((a) => this.pushAlert(a.type, a.text));
                })
                .fail((jqXHR) => {
                    const message =
                        jqXHR.status === 422
                            ? "برخی فیلدها معتبر نیستند."
                            : "ثبت اطلاعات با خطا مواجه شد.";
                    this.pushAlert("danger", message);
                })
                .always(() => this.showLoading(false));
        },

        askConfirm({ title, message, onConfirm }) {
            if (!this.confirmInstance) {
                if (window.confirm(message)) onConfirm();
                return;
            }

            $("#spaConfirmTitle").text(title);
            $("#spaConfirmMessage").text(message);
            this.pendingConfirmAction = onConfirm;
            this.confirmInstance.show();
        },
    };

    window.Spa = Spa;

    $(function () {
        const enabled = $("body").attr("data-spa") === "1";
        if (enabled) Spa.init();
    });
})(jQuery, window, document);
