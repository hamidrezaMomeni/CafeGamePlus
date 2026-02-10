const modalSelectors = {
    console: "modal-console",
    table: "modal-table",
    "board-game": "modal-board-game",
    "cafe-item": "modal-cafe-item",
    customer: "modal-customer",
    "console-session": "modal-console-session",
    "table-session": "modal-table-session",
    "board-game-session": "modal-board-game-session",
    order: "modal-order",
    "pricing-plan": "modal-pricing-plan",
    user: "modal-user",
};

const showToast = (message) => {
    const toast = document.createElement("div");
    toast.className = "toast";
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add("toast--visible");
    }, 50);
    setTimeout(() => {
        toast.classList.remove("toast--visible");
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

const openModal = (modal) => {
    if (!modal) return;
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
};

const closeModal = (modal) => {
    if (!modal) return;
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
};

const setEditOnlyVisibility = (form, isEdit) => {
    form.querySelectorAll("[data-edit-only]").forEach((el) => {
        el.style.display = isEdit ? "block" : "none";
    });
};

const applyFields = (form, fields = {}) => {
    Object.entries(fields).forEach(([name, value]) => {
        if (name === "permissions" || name === "days_of_week") {
            const values = Array.isArray(value) ? value.map(String) : [];
            form
                .querySelectorAll(`input[name="${name}[]"]`)
                .forEach((checkbox) => {
                    checkbox.checked = values.includes(String(checkbox.value));
                });
            return;
        }

        const inputs = form.querySelectorAll(`[name="${name}"]`);
        if (!inputs.length) return;

        inputs.forEach((input) => {
            if (input.type === "checkbox") {
                input.checked = Boolean(value);
                return;
            }
            input.value = value ?? "";
        });
    });
};

const setFormMode = (form, mode, id = null, fields = {}) => {
    const methodField = form.querySelector("[data-method-field]");
    const modeField = form.querySelector("[data-form-mode]");
    const entityIdField = form.querySelector("[data-entity-id]");

    form.reset();

    if (mode === "edit") {
        const updateTemplate = form.dataset.updateTemplate;
        if (updateTemplate && id) {
            form.action = updateTemplate.replace("__ID__", id);
        }
        if (methodField) {
            methodField.disabled = false;
            methodField.value = "PUT";
        }
        if (modeField) modeField.value = "edit";
        if (entityIdField) entityIdField.value = id ?? "";
        applyFields(form, fields);
        setEditOnlyVisibility(form, true);
    } else {
        if (form.dataset.createAction) {
            form.action = form.dataset.createAction;
        }
        if (methodField) {
            methodField.disabled = true;
            methodField.value = "PUT";
        }
        if (modeField) modeField.value = "create";
        if (entityIdField) entityIdField.value = "";
        setEditOnlyVisibility(form, false);
    }

    if (form.dataset.form === "pricing-plan") {
        updatePlanSections(form);
    }
};

const updatePlanSections = (form) => {
    const typeSelect = form.querySelector("[data-plan-type]");
    if (!typeSelect) return;
    const activeType = typeSelect.value;

    form.querySelectorAll("[data-plan-section]").forEach((section) => {
        const isActive = section.dataset.planSection === activeType;
        section.style.display = isActive ? "grid" : "none";
        section.querySelectorAll("input, select, textarea").forEach((input) => {
            input.disabled = !isActive;
        });
    });
};

const escapeHtml = (value) => {
    const str = value === null || value === undefined ? "" : String(value);
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/\"/g, "&quot;")
        .replace(/'/g, "&#039;");
};

const buildOrderDetail = (order) => {
    if (!order) return "";
    const items = order.items || [];
    const itemsHtml = items
        .map(
            (item) =>
                `<div class="detail__card">${escapeHtml(item.name)} — ${escapeHtml(item.quantity)} × ${escapeHtml(item.price ?? 0)} = ${escapeHtml(item.total_price ?? 0)}</div>`
        )
        .join("");

    const tableInfo = order.table ? `
            <div class="detail__card">
                <div class="muted">میز</div>
                <div>${escapeHtml(order.table)}</div>
            </div>` : "";

    return `
        <div class="detail__header">
            <div>
                <strong>سفارش #${escapeHtml(order.id)}</strong>
                <div class="muted">${escapeHtml(order.customer)}</div>
            </div>
            <div class="badge badge--success">${escapeHtml(order.total)} تومان</div>
        </div>
        <div class="detail__grid">
            <div class="detail__card">
                <div class="muted">زمان ثبت</div>
                <div>${escapeHtml(order.created_at)}</div>
            </div>
            ${tableInfo}
        </div>
        <div class="detail__list">${itemsHtml || '<div class="muted">آیتمی وجود ندارد.</div>'}</div>
    `;
};

const buildInvoiceDetail = (invoice) => {
    if (!invoice) return "";
    const sections = [];

    const buildSessionCards = (label, sessions) => {
        if (!sessions || sessions.length === 0) return "";
        const list = sessions
            .map(
                (s) =>
                    `<div class="detail__card">${escapeHtml(s.name)} — ${escapeHtml(s.duration ?? "—")} دقیقه — ${escapeHtml(s.total ?? 0)}</div>`
            )
            .join("");
        return `<div class="detail__list"><strong>${label}</strong>${list}</div>`;
    };

    const buildOrderCards = (orders) => {
        if (!orders || orders.length === 0) return "";
        const list = orders
            .map((o) => {
                const items = (o.items || [])
                    .map((item) => `${escapeHtml(item.name)} (${escapeHtml(item.quantity)})`)
                    .join("، ");
                const tableInfo = o.table ? ` — میز ${escapeHtml(o.table)}` : "";
                return `<div class="detail__card">سفارش #${escapeHtml(o.id)}${tableInfo} — ${escapeHtml(o.total)} تومان — ${items || "بدون آیتم"}</div>`;
            })
            .join("");
        return `<div class="detail__list"><strong>سفارش‌های کافه</strong>${list}</div>`;
    };

    sections.push(buildSessionCards("سشن‌های کنسول", invoice.console_sessions));
    sections.push(buildSessionCards("سشن‌های میز", invoice.table_sessions));
    sections.push(buildSessionCards("سشن‌های بردگیم", invoice.board_game_sessions));
    sections.push(buildOrderCards(invoice.orders));

    return `
        <div class="detail__header">
            <div>
                <strong>${escapeHtml(invoice.number)}</strong>
                <div class="muted">${escapeHtml(invoice.customer)}</div>
            </div>
            <div class="badge badge--success">${escapeHtml(invoice.total)} تومان</div>
        </div>
        <div class="detail__grid">
            <div class="detail__card">
                <div class="muted">وضعیت</div>
                <div>${escapeHtml(invoice.status)}</div>
            </div>
            <div class="detail__card">
                <div class="muted">زمان ثبت</div>
                <div>${escapeHtml(invoice.created_at)}</div>
            </div>
        </div>
        ${sections.filter(Boolean).join("") || '<div class="muted">جزئیاتی یافت نشد.</div>'}
    `;
};

const orderItemState = {
    index: 0,
};

const setupOrderItems = () => {
    const modal = document.getElementById("modal-order");
    if (!modal) return;

    const list = modal.querySelector("[data-order-items]");
    const addButton = modal.querySelector("[data-add-order-item]");
    const template = document.getElementById("order-item-template");
    const totalEl = modal.querySelector("[data-order-total]");

    const updateTotal = () => {
        let total = 0;
        list.querySelectorAll("[data-order-item]").forEach((row) => {
            const select = row.querySelector("[data-order-item-select]");
            const qty = row.querySelector("[data-order-item-qty]");
            const price = parseFloat(select.selectedOptions[0]?.dataset.price ?? 0);
            const quantity = parseInt(qty.value, 10) || 0;
            total += price * quantity;
        });
        if (totalEl) totalEl.textContent = total.toLocaleString("fa-IR");
    };

    const reindex = () => {
        list.querySelectorAll("[data-order-item]").forEach((row, idx) => {
            const select = row.querySelector("[data-order-item-select]");
            const qty = row.querySelector("[data-order-item-qty]");
            if (select) select.name = `items[${idx}][cafe_item_id]`;
            if (qty) qty.name = `items[${idx}][quantity]`;
        });
        orderItemState.index = list.querySelectorAll("[data-order-item]").length;
    };

    const addRow = (data = null) => {
        const node = template.content.cloneNode(true).firstElementChild;
        const select = node.querySelector("[data-order-item-select]");
        const qty = node.querySelector("[data-order-item-qty]");
        if (select && data?.cafe_item_id) select.value = String(data.cafe_item_id);
        if (qty && data?.quantity) qty.value = data.quantity;

        node.addEventListener("change", updateTotal);
        node.querySelector("[data-remove-order-item]").addEventListener("click", () => {
            node.remove();
            reindex();
            updateTotal();
        });

        list.appendChild(node);
        reindex();
        updateTotal();
    };

    const reset = () => {
        list.innerHTML = "";
        addRow();
    };

    addButton?.addEventListener("click", () => addRow());

    modal.addEventListener("modal:open", () => {
        reset();
    });
};

const setupOrderSessionLink = () => {
    const modal = document.getElementById("modal-order");
    if (!modal) return;

    const form = modal.querySelector("form");
    if (!form) return;

    const sessionSelect = form.querySelector('[name="session_ref"]');
    const tableSelect = form.querySelector('[name="table_id"]');
    const customerSelect = form.querySelector('[name="customer_id"]');

    if (!sessionSelect) return;

    const applySelection = () => {
        const selectedOption = sessionSelect.selectedOptions[0];
        const hasSession = Boolean(sessionSelect.value);

        if (!hasSession) {
            if (tableSelect) tableSelect.disabled = false;
            if (customerSelect) customerSelect.disabled = false;
            return;
        }

        const tableId = selectedOption?.dataset.tableId;
        const customerId = selectedOption?.dataset.customerId;

        if (tableSelect) {
            tableSelect.value = tableId || "";
            tableSelect.disabled = true;
        }

        if (customerSelect) {
            if (customerId) {
                customerSelect.value = customerId;
                customerSelect.disabled = true;
            } else {
                customerSelect.disabled = false;
            }
        }
    };

    sessionSelect.addEventListener("change", applySelection);
    modal.addEventListener("modal:open", () => {
        applySelection();
    });
};

const setupModalTriggers = () => {
    document.addEventListener("click", (event) => {
        const target = event.target.closest("[data-modal-open]");
        if (!target) return;
        const modalId = target.dataset.modalOpen;
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const form = modal.querySelector("form");
        if (form) {
            const isEdit = target.dataset.edit === "1";
            const fields = target.dataset.fields ? JSON.parse(target.dataset.fields) : {};
            if (isEdit) {
                setFormMode(form, "edit", target.dataset.id, fields);
                if (form.dataset.form === "user") {
                    const isSuperAdmin = Boolean(fields.is_super_admin);
                    const permissionWrap = form.querySelector("[data-permissions]");
                    if (permissionWrap) {
                        permissionWrap.style.opacity = isSuperAdmin ? "0.5" : "1";
                        permissionWrap.querySelectorAll("input[type=checkbox]").forEach((cb) => {
                            cb.disabled = isSuperAdmin;
                        });
                    }
                }
            } else {
                setFormMode(form, "create");
                if (form.dataset.form === "user") {
                    const permissionWrap = form.querySelector("[data-permissions]");
                    if (permissionWrap) {
                        permissionWrap.style.opacity = "1";
                        permissionWrap.querySelectorAll("input[type=checkbox]").forEach((cb) => {
                            cb.disabled = false;
                            cb.checked = false;
                        });
                    }
                }
            }
        }

        if (modalId === "modal-order-detail") {
            const detail = modal.querySelector("[data-order-detail]");
            const orderData = target.dataset.order ? JSON.parse(target.dataset.order) : null;
            if (detail) detail.innerHTML = buildOrderDetail(orderData);
        }

        if (modalId === "modal-invoice") {
            const detail = modal.querySelector("[data-invoice-detail]");
            const invoiceData = target.dataset.invoice ? JSON.parse(target.dataset.invoice) : null;
            if (detail) detail.innerHTML = buildInvoiceDetail(invoiceData);
        }

        openModal(modal);
        modal.dispatchEvent(new CustomEvent("modal:open"));
    });

    document.addEventListener("click", (event) => {
        if (event.target.matches("[data-modal-close]")) {
            const modal = event.target.closest(".modal");
            closeModal(modal);
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;
        document.querySelectorAll(".modal.is-open").forEach(closeModal);
    });
};

const setupPlanTypeListeners = () => {
    document.querySelectorAll("[data-plan-type]").forEach((select) => {
        updatePlanSections(select.closest("form"));
        select.addEventListener("change", () => updatePlanSections(select.closest("form")));
    });
};

const setupConfirmations = () => {
    document.addEventListener("submit", (event) => {
        const form = event.target.closest("form");
        if (!form || !form.dataset.confirm) return;
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
};

const setupSystemTick = () => {
    document.querySelectorAll("form[data-auto-submit]").forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            const token = form.querySelector('input[name="_token"]')?.value;
            try {
                const response = await fetch(form.action, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": token || "",
                        Accept: "application/json",
                    },
                });
                const data = await response.json().catch(() => null);
                if (data) {
                    const total =
                        (data.ended_console_sessions || 0) +
                        (data.ended_table_sessions || 0) +
                        (data.ended_board_game_sessions || 0);
                    showToast(`پایان خودکار ${total} سشن انجام شد.`);
                } else {
                    showToast("همگام‌سازی انجام شد.");
                }
            } catch (error) {
                showToast("خطا در همگام‌سازی.");
            }
        });
    });
};

const setupLiveTimers = () => {
    const items = Array.from(
        document.querySelectorAll("[data-elapsed-start], [data-elapsed-start-ts]")
    );
    if (!items.length) return;

    const pad = (num) => String(num).padStart(2, "0");

    const update = () => {
        const now = Date.now();
        items.forEach((el) => {
            const startTs = el.dataset.elapsedStartTs;
            const startIso = el.dataset.elapsedStart;
            let startMs = null;
            if (startTs) {
                const parsed = parseInt(startTs, 10);
                if (!Number.isNaN(parsed)) {
                    startMs = parsed * 1000;
                }
            }
            if (startMs === null && startIso) {
                const parsedIso = new Date(startIso).getTime();
                if (!Number.isNaN(parsedIso)) {
                    startMs = parsedIso;
                }
            }
            if (startMs === null) return;
            const diff = Math.max(0, Math.floor((now - startMs) / 1000));
            const hours = Math.floor(diff / 3600);
            const minutes = Math.floor((diff % 3600) / 60);
            const seconds = diff % 60;
            el.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        });
    };

    update();
    setInterval(update, 1000);
};

// Jalali datepicker
const setupJalaliPickers = () => {
    const inputs = Array.from(document.querySelectorAll("[data-jalali-picker]"));
    if (!inputs.length) return;

    const months = [
        "فروردین",
        "اردیبهشت",
        "خرداد",
        "تیر",
        "مرداد",
        "شهریور",
        "مهر",
        "آبان",
        "آذر",
        "دی",
        "بهمن",
        "اسفند",
    ];

    const weekDays = ["ش", "ی", "د", "س", "چ", "پ", "ج"];

    const pad = (num) => String(num).padStart(2, "0");

    const div = (a, b) => Math.floor(a / b);
    const mod = (a, b) => a - Math.floor(a / b) * b;

    const jalCal = (jy) => {
        const breaks = [
            -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
            1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
        ];
        const bl = breaks.length;
        let gy = jy + 621;
        let leapJ = -14;
        let jp = breaks[0];
        let jm;
        let jump;
        let leap;
        let leapG;
        let march;
        let n;
        if (jy < jp || jy >= breaks[bl - 1]) {
            return null;
        }
        for (let i = 1; i < bl; i += 1) {
            jm = breaks[i];
            jump = jm - jp;
            if (jy < jm) {
                break;
            }
            leapJ = leapJ + div(jump, 33) * 8 + div(mod(jump, 33), 4);
            jp = jm;
        }
        n = jy - jp;
        leapJ = leapJ + div(n, 33) * 8 + div(mod(n, 33) + 3, 4);
        if (mod(jump, 33) === 4 && jump - n === 4) {
            leapJ += 1;
        }
        leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
        march = 20 + leapJ - leapG;
        if (jump - n < 6) {
            n = n - jump + div(jump + 4, 33) * 33;
        }
        leap = mod(mod(n + 1, 33) - 1, 4);
        if (leap === -1) leap = 4;
        return { leap, gy, march };
    };

    const isLeapJalaaliYear = (jy) => jalCal(jy)?.leap === 0;
    const jalaaliMonthLength = (jy, jm) =>
        jm <= 6 ? 31 : jm <= 11 ? 30 : isLeapJalaaliYear(jy) ? 30 : 29;

    const jdnFromGregorian = (gy, gm, gd) => {
        const d =
            div((gy + div(gm - 8, 6) + 100100) * 1461, 4) +
            div(153 * mod(gm + 9, 12) + 2, 5) +
            gd -
            34840408;
        return d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
    };

    const jdnToGregorian = (jdn) => {
        let j = 4 * jdn + 139361631;
        j = j + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        const i = div(mod(j, 1461), 4) * 5 + 308;
        const gd = div(mod(i, 153), 5) + 1;
        const gm = mod(div(i, 153), 12) + 1;
        const gy = div(j, 1461) - 100100 + div(8 - gm, 6);
        return { gy, gm, gd };
    };

    const jdnToJalaali = (jdn) => {
        const g = jdnToGregorian(jdn);
        let jy = g.gy - 621;
        const r = jalCal(jy);
        const jdn1f = jdnFromGregorian(g.gy, 3, r.march);
        let k = jdn - jdn1f;
        let jm;
        let jd;
        if (k >= 0) {
            if (k <= 185) {
                jm = 1 + div(k, 31);
                jd = mod(k, 31) + 1;
                return { jy, jm, jd };
            }
            k -= 186;
        } else {
            jy -= 1;
            k += 179;
            if (r.leap === 1) k += 1;
        }
        jm = 7 + div(k, 30);
        jd = mod(k, 30) + 1;
        return { jy, jm, jd };
    };

    const toJalaali = (gy, gm, gd) => jdnToJalaali(jdnFromGregorian(gy, gm, gd));

    const toGregorian = (jy, jm, jd) => {
        const r = jalCal(jy);
        if (!r) return null;
        const jdn =
            jdnFromGregorian(r.gy, 3, r.march) +
            (jm - 1) * 31 -
            div(jm, 7) * (jm - 7) +
            (jd - 1);
        return jdnToGregorian(jdn);
    };

    const getTodayJalali = () => {
        const now = new Date();
        return toJalaali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    };

    const parseInputValue = (value, mode) => {
        if (!value) return null;
        const trimmed = value.trim().replace(/\//g, "-");
        if (mode === "datetime" && /^\d{1,2}:\d{2}$/.test(trimmed)) {
            const today = getTodayJalali();
            return {
                ...today,
                hour: parseInt(trimmed.split(":")[0], 10),
                minute: parseInt(trimmed.split(":")[1], 10),
            };
        }

        const dateTimeMatch = trimmed.match(
            /^(\d{4})-(\d{1,2})-(\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?$/
        );
        if (!dateTimeMatch) return null;
        const jy = parseInt(dateTimeMatch[1], 10);
        const jm = parseInt(dateTimeMatch[2], 10);
        const jd = parseInt(dateTimeMatch[3], 10);
        const hour = dateTimeMatch[4] ? parseInt(dateTimeMatch[4], 10) : 0;
        const minute = dateTimeMatch[5] ? parseInt(dateTimeMatch[5], 10) : 0;
        return { jy, jm, jd, hour, minute };
    };

    const formatValue = (state, mode) => {
        if (!state) return "";
        const datePart = `${state.jy}-${pad(state.jm)}-${pad(state.jd)}`;
        if (mode === "datetime") {
            return `${datePart} ${pad(state.hour ?? 0)}:${pad(state.minute ?? 0)}`;
        }
        return datePart;
    };

    const picker = document.createElement("div");
    picker.className = "jalali-picker";
    picker.innerHTML = `
        <div class="jalali-picker__header">
            <button type="button" class="jalali-picker__nav" data-jp-prev>‹</button>
            <div class="jalali-picker__title" data-jp-title></div>
            <button type="button" class="jalali-picker__nav" data-jp-next>›</button>
        </div>
        <div class="jalali-picker__week">
            ${weekDays.map((d) => `<span>${d}</span>`).join("")}
        </div>
        <div class="jalali-picker__grid" data-jp-grid></div>
        <div class="jalali-picker__time" data-jp-time>
            <label>
                <span>ساعت</span>
                <select data-jp-hour>
                    ${Array.from({ length: 24 }, (_, i) => `<option value="${i}">${pad(i)}</option>`).join("")}
                </select>
            </label>
            <label>
                <span>دقیقه</span>
                <select data-jp-minute>
                    ${Array.from({ length: 60 }, (_, i) => `<option value="${i}">${pad(i)}</option>`).join("")}
                </select>
            </label>
        </div>
        <div class="jalali-picker__actions">
            <button type="button" data-jp-today>امروز</button>
            <button type="button" data-jp-clear>پاک کردن</button>
        </div>
    `;
    document.body.appendChild(picker);

    const gridEl = picker.querySelector("[data-jp-grid]");
    const titleEl = picker.querySelector("[data-jp-title]");
    const hourEl = picker.querySelector("[data-jp-hour]");
    const minuteEl = picker.querySelector("[data-jp-minute]");
    const timeWrap = picker.querySelector("[data-jp-time]");

    let activeInput = null;
    let activeMode = "date";
    let currentView = null;
    let selected = null;

    const closePicker = () => {
        picker.classList.remove("is-open");
        activeInput = null;
    };

    const openPicker = (input) => {
        activeInput = input;
        activeMode = input.dataset.jalaliPicker || "date";
        const parsed = parseInputValue(input.value, activeMode);
        const today = getTodayJalali();
        selected = parsed || { ...today, hour: 0, minute: 0 };
        currentView = { jy: selected.jy, jm: selected.jm };

        if (activeMode === "datetime") {
            timeWrap.style.display = "grid";
            hourEl.value = selected.hour ?? 0;
            minuteEl.value = selected.minute ?? 0;
        } else {
            timeWrap.style.display = "none";
        }

        render();

        const rect = input.getBoundingClientRect();
        const top = rect.bottom + window.scrollY + 8;
        const left = rect.left + window.scrollX;
        const pickerWidth = 280;
        picker.style.top = `${top}px`;
        picker.style.left = `${Math.min(left, window.innerWidth - pickerWidth - 16)}px`;

        picker.classList.add("is-open");
    };

    const render = () => {
        if (!currentView) return;
        titleEl.textContent = `${months[currentView.jm - 1]} ${currentView.jy}`;

        const firstDayGregorian = toGregorian(currentView.jy, currentView.jm, 1);
        const firstDate = new Date(
            firstDayGregorian.gy,
            firstDayGregorian.gm - 1,
            firstDayGregorian.gd
        );
        const weekIndex = (firstDate.getDay() + 1) % 7; // Saturday=0
        const daysInMonth = jalaaliMonthLength(currentView.jy, currentView.jm);

        const cells = [];
        for (let i = 0; i < weekIndex; i++) {
            cells.push(`<span class="jalali-picker__empty"></span>`);
        }
        for (let day = 1; day <= daysInMonth; day++) {
            const isSelected =
                selected &&
                selected.jy === currentView.jy &&
                selected.jm === currentView.jm &&
                selected.jd === day;
            cells.push(
                `<button type="button" class="jalali-picker__day ${isSelected ? "is-selected" : ""}" data-day="${day}">${day}</button>`
            );
        }
        gridEl.innerHTML = cells.join("");
    };

    const updateInputValue = () => {
        if (!activeInput || !selected) return;
        activeInput.value = formatValue(selected, activeMode);
    };

    picker.addEventListener("click", (event) => {
        const target = event.target;
        if (target.matches("[data-jp-prev]")) {
            currentView.jm -= 1;
            if (currentView.jm < 1) {
                currentView.jm = 12;
                currentView.jy -= 1;
            }
            render();
        }
        if (target.matches("[data-jp-next]")) {
            currentView.jm += 1;
            if (currentView.jm > 12) {
                currentView.jm = 1;
                currentView.jy += 1;
            }
            render();
        }
        if (target.matches("[data-jp-today]")) {
            const today = getTodayJalali();
            selected = { ...today, hour: selected?.hour ?? 0, minute: selected?.minute ?? 0 };
            currentView = { jy: selected.jy, jm: selected.jm };
            updateInputValue();
            render();
        }
        if (target.matches("[data-jp-clear]")) {
            if (activeInput) activeInput.value = "";
            closePicker();
        }
        if (target.matches("[data-day]")) {
            const day = parseInt(target.dataset.day, 10);
            selected = {
                jy: currentView.jy,
                jm: currentView.jm,
                jd: day,
                hour: parseInt(hourEl.value, 10) || 0,
                minute: parseInt(minuteEl.value, 10) || 0,
            };
            updateInputValue();
            render();
        }
    });

    hourEl.addEventListener("change", () => {
        if (!selected) return;
        selected.hour = parseInt(hourEl.value, 10) || 0;
        updateInputValue();
    });

    minuteEl.addEventListener("change", () => {
        if (!selected) return;
        selected.minute = parseInt(minuteEl.value, 10) || 0;
        updateInputValue();
    });

    document.addEventListener("click", (event) => {
        if (!picker.classList.contains("is-open")) return;
        if (picker.contains(event.target)) return;
        if (activeInput && activeInput.contains(event.target)) return;
        closePicker();
    });

    inputs.forEach((input) => {
        input.addEventListener("focus", () => openPicker(input));
        input.addEventListener("click", () => openPicker(input));
    });
};

const setupThemeToggle = () => {
    const toggle = document.querySelector("[data-theme-toggle]");
    if (!toggle) return;
    const label = toggle.querySelector("[data-theme-label]");

    const applyTheme = (theme) => {
        const isDark = theme === "dark";
        document.body.classList.toggle("theme-dark", isDark);
        toggle.setAttribute("aria-pressed", String(isDark));
        if (label) {
            label.textContent = isDark ? "حالت روز" : "حالت شب";
        }
        localStorage.setItem("theme", theme);
    };

    const storedTheme = localStorage.getItem("theme");
    if (storedTheme) {
        applyTheme(storedTheme);
    } else {
        const prefersDark = window.matchMedia?.("(prefers-color-scheme: dark)").matches;
        applyTheme(prefersDark ? "dark" : "light");
    }

    toggle.addEventListener("click", () => {
        const isDark = document.body.classList.contains("theme-dark");
        applyTheme(isDark ? "light" : "dark");
    });
};

const openModalFromState = () => {
    if (!window.dashboardState) return;
    const {
        openModal: formKey,
        openInvoiceId,
        oldFormMode,
        oldEntityId,
        oldInput,
    } = window.dashboardState;

    if (formKey && modalSelectors[formKey]) {
        const modal = document.getElementById(modalSelectors[formKey]);
        if (modal) {
            const form = modal.querySelector("form");
            if (form) {
                if (oldFormMode === "edit") {
                    setFormMode(form, "edit", oldEntityId, oldInput || {});
                } else {
                    setFormMode(form, "create");
                    applyFields(form, oldInput || {});
                }
            }
            openModal(modal);
            modal.dispatchEvent(new CustomEvent("modal:open"));
        }
    }

    if (openInvoiceId) {
        const btn = document.querySelector(`[data-invoice-id="${openInvoiceId}"]`);
        if (btn) btn.click();
    }
};

const setup = () => {
    setupModalTriggers();
    setupPlanTypeListeners();
    setupConfirmations();
    setupOrderItems();
    setupOrderSessionLink();
    setupSystemTick();
    setupLiveTimers();
    setupJalaliPickers();
    setupThemeToggle();
    openModalFromState();
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", setup);
} else {
    setup();
}
