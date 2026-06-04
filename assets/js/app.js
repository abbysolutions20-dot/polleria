document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const sidebarToggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
    const sidebarCloseTriggers = Array.from(document.querySelectorAll('[data-sidebar-close]'));

    if (sidebarToggles.length > 0) {
        const closeSidebar = () => {
            body.classList.remove('sidebar-open');
        };

        sidebarToggles.forEach((toggle) => {
            toggle.addEventListener('click', () => {
                body.classList.toggle('sidebar-open');
            });
        });

        sidebarCloseTriggers.forEach((element) => {
            element.addEventListener('click', closeSidebar);
        });

        document.querySelectorAll('.nav__link, .logout-link').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
    }

    const serviceSelector = document.querySelector('[data-service-selector]');
    const tableField = document.querySelector('[data-table-field]');

    if (serviceSelector && tableField) {
        const toggleTableField = () => {
            const isTableService = serviceSelector.value === 'MESA';
            tableField.style.display = isTableService ? 'grid' : 'none';
            const select = tableField.querySelector('select');

            if (select && !isTableService) {
                select.value = '';
            }
        };

        serviceSelector.addEventListener('change', toggleTableField);
        toggleTableField();
    }

    const confirmModalElement = document.getElementById('appConfirmModal');
    const confirmMessage = document.getElementById('appConfirmModalMessage');
    const confirmAccept = document.getElementById('appConfirmModalAccept');
    const bootstrapModal = confirmModalElement && window.bootstrap
        ? new window.bootstrap.Modal(confirmModalElement)
        : null;
    let pendingConfirmForm = null;

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === '1') {
                form.dataset.confirmed = '';
                return;
            }

            const question = form.getAttribute('data-confirm') || 'Confirmas esta accion?';

            if (!bootstrapModal || !confirmMessage || !confirmAccept) {
                if (!window.confirm(question)) {
                    event.preventDefault();
                }

                return;
            }

            event.preventDefault();
            pendingConfirmForm = form;
            confirmMessage.textContent = question;
            bootstrapModal.show();
        });
    });

    if (confirmAccept) {
        confirmAccept.addEventListener('click', () => {
            if (!pendingConfirmForm) {
                return;
            }

            pendingConfirmForm.dataset.confirmed = '1';

            if (bootstrapModal) {
                bootstrapModal.hide();
            }

            if (typeof pendingConfirmForm.requestSubmit === 'function') {
                pendingConfirmForm.requestSubmit();
                return;
            }

            pendingConfirmForm.submit();
        });
    }

    if (confirmModalElement) {
        confirmModalElement.addEventListener('hidden.bs.modal', () => {
            pendingConfirmForm = null;
        });
    }

    const quickPayModalElement = document.getElementById('quickPayModal');

    if (quickPayModalElement && quickPayModalElement.parentElement !== document.body) {
        document.body.appendChild(quickPayModalElement);
    }

    if (quickPayModalElement) {
        const paymentForm = quickPayModalElement.querySelector('[data-payment-flow-form]');
        const methodStep = quickPayModalElement.querySelector('[data-payment-step="methods"]');
        const detailStep = quickPayModalElement.querySelector('[data-payment-step="detail"]');
        const methodInput = quickPayModalElement.querySelector('[data-payment-flow-method]');
        const selectedLabel = quickPayModalElement.querySelector('[data-payment-selected-label]');
        const receiptLabel = quickPayModalElement.querySelector('[data-payment-receipt-label]');
        const receiptNote = quickPayModalElement.querySelector('[data-payment-receipt-note]');
        const receiptOptions = Array.from(quickPayModalElement.querySelectorAll('[data-payment-receipt-option]'));
        const paymentOptionButtons = Array.from(quickPayModalElement.querySelectorAll('[data-payment-option]'));
        const paymentBackButton = quickPayModalElement.querySelector('[data-payment-back]');

        const showPaymentMethods = () => {
            if (methodStep) {
                methodStep.hidden = false;
            }

            if (detailStep) {
                detailStep.hidden = true;
            }

            if (methodInput) {
                methodInput.value = '';
            }

            paymentOptionButtons.forEach((button) => button.classList.remove('is-selected'));
        };

        const syncReceiptCopy = () => {
            const selectedReceipt = receiptOptions.find((option) => option.checked);
            const receiptName = selectedReceipt ? selectedReceipt.value : 'Boleta';

            if (receiptLabel) {
                receiptLabel.textContent = receiptName;
            }

            if (receiptNote) {
                receiptNote.textContent = receiptName === 'Factura'
                    ? 'Factura electronica - Datos obligatorios'
                    : receiptName === 'Nota de Venta'
                        ? 'Nota de venta interna - Datos opcionales'
                        : 'Boleta Simple (< S/ 700) - Datos opcionales';
            }
        };

        paymentOptionButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (button.disabled) {
                    return;
                }

                const methodKey = button.getAttribute('data-payment-key') || '';
                const methodLabel = button.getAttribute('data-payment-label') || methodKey;

                if (methodInput) {
                    methodInput.value = methodKey;
                }

                if (selectedLabel) {
                    selectedLabel.textContent = methodLabel;
                }

                paymentOptionButtons.forEach((item) => item.classList.toggle('is-selected', item === button));

                if (methodStep) {
                    methodStep.hidden = true;
                }

                if (detailStep) {
                    detailStep.hidden = false;
                }

                syncReceiptCopy();
            });
        });

        receiptOptions.forEach((option) => {
            option.addEventListener('change', syncReceiptCopy);
        });

        if (paymentBackButton) {
            paymentBackButton.addEventListener('click', showPaymentMethods);
        }

        if (paymentForm && methodInput) {
            paymentForm.addEventListener('submit', (event) => {
                if (methodInput.value.trim() === '') {
                    event.preventDefault();
                    showPaymentMethods();
                }
            });
        }

        quickPayModalElement.addEventListener('hidden.bs.modal', showPaymentMethods);
    }

    document.querySelectorAll('[data-visibility-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const form = toggle.closest('form');
            const target = form ? form.querySelector('[data-visibility-value]') : null;

            if (!form || !target) {
                return;
            }

            target.value = toggle.checked ? '1' : '0';
            form.submit();
        });
    });

    const paymentMethodSelect = document.querySelector('[data-payment-method-select]');
    const paymentAmountInput = document.querySelector('[data-payment-amount]');
    const paymentReceivedInput = document.querySelector('[data-payment-received]');

    if (paymentMethodSelect && paymentAmountInput && paymentReceivedInput) {
        const syncPaymentReceivedField = () => {
            const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
            const methodName = (selectedOption?.getAttribute('data-payment-method-name') || '').trim().toUpperCase();
            const isCardPayment = methodName === 'TARJETA';

            if (isCardPayment) {
                paymentReceivedInput.value = paymentAmountInput.value;
                paymentReceivedInput.readOnly = true;
                paymentReceivedInput.setAttribute('aria-readonly', 'true');
                paymentReceivedInput.title = 'Para tarjeta, el monto recibido se completa automaticamente.';
            } else {
                paymentReceivedInput.readOnly = false;
                paymentReceivedInput.removeAttribute('aria-readonly');
                paymentReceivedInput.removeAttribute('title');

                if (methodName !== '' && paymentReceivedInput.value === paymentAmountInput.value) {
                    paymentReceivedInput.value = '';
                }
            }
        };

        paymentMethodSelect.addEventListener('change', syncPaymentReceivedField);
        paymentAmountInput.addEventListener('input', () => {
            const selectedOption = paymentMethodSelect.options[paymentMethodSelect.selectedIndex];
            const methodName = (selectedOption?.getAttribute('data-payment-method-name') || '').trim().toUpperCase();

            if (methodName === 'TARJETA') {
                paymentReceivedInput.value = paymentAmountInput.value;
            }
        });

        syncPaymentReceivedField();
    }

    const receiptToggle = document.querySelector('[data-receipt-toggle]');
    const receiptOptions = document.querySelector('[data-receipt-options]');
    const receiptTypeSelect = document.querySelector('[data-receipt-type]');

    if (receiptToggle && receiptOptions) {
        const syncReceiptOptions = () => {
            const isChecked = receiptToggle.checked;

            receiptOptions.hidden = !isChecked;

            if (receiptTypeSelect) {
                receiptTypeSelect.disabled = !isChecked;
                receiptTypeSelect.required = isChecked;
            }
        };

        receiptToggle.addEventListener('change', syncReceiptOptions);
        syncReceiptOptions();
    }

    document.querySelectorAll('[data-product-picker]').forEach((picker) => {
        const searchInput = picker.querySelector('[data-picker-search]');
        const categoryButtons = Array.from(picker.querySelectorAll('[data-picker-category]'));
        const productButtons = Array.from(picker.querySelectorAll('[data-picker-product]'));
        const sections = Array.from(picker.querySelectorAll('[data-picker-section]'));
        const hiddenInput = picker.querySelector('[data-picker-product-input]');
        const quantityInput = picker.querySelector('[data-picker-quantity]');
        const noteInput = picker.querySelector('[data-picker-note]');

        if (!hiddenInput) {
            return;
        }

        const filterProducts = () => {
            const searchValue = (searchInput?.value || '').trim().toLowerCase();
            const activeButton = categoryButtons.find((button) => button.classList.contains('is-active'));
            const activeCategoryId = activeButton ? activeButton.getAttribute('data-picker-category') || '' : '';

            productButtons.forEach((button) => {
                const categoryId = button.getAttribute('data-product-category-id') || '';
                const haystack = [
                    button.getAttribute('data-product-name') || '',
                    button.getAttribute('data-product-category') || '',
                    button.getAttribute('data-product-area') || '',
                ].join(' ').toLowerCase();

                const matchesCategory = activeCategoryId === '' || activeCategoryId === categoryId;
                const matchesSearch = searchValue === '' || haystack.includes(searchValue);

                button.hidden = !(matchesCategory && matchesSearch);
            });

            sections.forEach((section) => {
                const hasVisibleProducts = Array.from(section.querySelectorAll('[data-picker-product]'))
                    .some((button) => !button.hidden);

                section.hidden = !hasVisibleProducts;
            });
        };

        productButtons.forEach((button) => {
            button.addEventListener('click', () => {
                hiddenInput.value = button.getAttribute('data-product-id') || '';

                if (quantityInput) {
                    quantityInput.value = '1';
                }

                if (noteInput) {
                    noteInput.value = '';
                }

                if (hiddenInput.value.trim() === '') {
                    window.alert('Selecciona un plato antes de agregarlo a la orden.');
                    return;
                }

                if (typeof picker.requestSubmit === 'function') {
                    picker.requestSubmit();
                    return;
                }

                picker.submit();
            });
        });

        categoryButtons.forEach((button) => {
            button.addEventListener('click', () => {
                categoryButtons.forEach((item) => {
                    const isActive = item === button;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                filterProducts();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', filterProducts);
        }

        picker.addEventListener('submit', (event) => {
            if (hiddenInput.value.trim() !== '') {
                return;
            }

            event.preventDefault();
        });

        filterProducts();
    });

    document.querySelectorAll('.table-wrap .table').forEach((table) => {
        const tbody = table.querySelector('tbody');
        const wrap = table.closest('.table-wrap');
        const perPage = Number(table.getAttribute('data-table-paginate') || 8);

        if (!tbody || !wrap || Number.isNaN(perPage) || perPage <= 0 || table.hasAttribute('data-no-pagination')) {
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr')).filter((row) => {
            const singleCell = row.children.length === 1 ? row.children[0] : null;
            return !(singleCell && singleCell.hasAttribute('colspan'));
        });

        if (rows.length <= perPage) {
            return;
        }

        const totalPages = Math.ceil(rows.length / perPage);
        let currentPage = 1;
        const paginationBar = document.createElement('div');
        paginationBar.className = 'pagination-bar';

        const summary = document.createElement('div');
        summary.className = 'pagination-summary';

        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'Paginacion de tabla');

        const pagination = document.createElement('ul');
        pagination.className = 'pagination';
        nav.appendChild(pagination);
        paginationBar.append(summary, nav);
        wrap.insertAdjacentElement('afterend', paginationBar);

        const renderTablePage = () => {
            const start = (currentPage - 1) * perPage;
            const end = start + perPage;

            rows.forEach((row, index) => {
                row.hidden = index < start || index >= end;
            });

            const from = start + 1;
            const to = Math.min(end, rows.length);
            summary.textContent = `Mostrando ${from} a ${to} de ${rows.length} registros`;
            pagination.innerHTML = '';

            const createPageItem = (label, page, disabled = false, active = false) => {
                const item = document.createElement('li');
                item.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;

                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'page-link';
                button.textContent = label;
                button.disabled = disabled;
                button.addEventListener('click', () => {
                    currentPage = page;
                    renderTablePage();
                });

                item.appendChild(button);
                pagination.appendChild(item);
            };

            createPageItem('Prev', Math.max(1, currentPage - 1), currentPage === 1);

            for (let page = 1; page <= totalPages; page += 1) {
                createPageItem(String(page), page, false, page === currentPage);
            }

            createPageItem('Next', Math.min(totalPages, currentPage + 1), currentPage === totalPages);
        };

        renderTablePage();
    });

    const focusableSearchInputs = () => Array.from(document.querySelectorAll('input[type="search"], input[name="search"]'))
        .filter((input) => input instanceof HTMLElement && !input.hidden && input.offsetParent !== null);

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const isTypingContext = target instanceof HTMLElement
            && (target.matches('input, textarea, select') || target.isContentEditable);

        if (event.key === '/' && !isTypingContext) {
            const searchInput = focusableSearchInputs()[0];

            if (!searchInput) {
                return;
            }

            event.preventDefault();
            searchInput.focus();

            if (typeof searchInput.select === 'function') {
                searchInput.select();
            }
        }
    });

    document.querySelectorAll('.flash.alert').forEach((flash, index) => {
        const shouldAutoDismiss = flash.classList.contains('alert-success') || flash.classList.contains('alert-info');

        if (!shouldAutoDismiss) {
            return;
        }

        const delay = 4200 + (index * 350);

        window.setTimeout(() => {
            if (!flash.isConnected) {
                return;
            }

            if (window.bootstrap?.Alert) {
                const instance = window.bootstrap.Alert.getOrCreateInstance(flash);
                instance.close();
                return;
            }

            flash.remove();
        }, delay);
    });

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            window.requestAnimationFrame(() => {
                if (event.defaultPrevented) {
                    return;
                }

                const method = (form.getAttribute('method') || 'get').trim().toLowerCase();

                if (method !== 'post') {
                    return;
                }

                const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));

                if (submitButtons.length === 0) {
                    return;
                }

                form.classList.add('is-submitting');

                submitButtons.forEach((button) => {
                    button.classList.add('is-loading');
                    button.setAttribute('aria-busy', 'true');
                    button.setAttribute('disabled', 'disabled');
                });
            });
        });
    });

    const kitchenTimers = Array.from(document.querySelectorAll('[data-kitchen-timer]'));

    if (kitchenTimers.length > 0) {
        const formatKitchenElapsed = (startedAtIso, nowTimestamp) => {
            const startedAt = Date.parse(startedAtIso);

            if (Number.isNaN(startedAt)) {
                return '--';
            }

            const seconds = Math.max(0, Math.floor((nowTimestamp - startedAt) / 1000));

            if (seconds < 60) {
                return `${seconds}s`;
            }

            const minutes = Math.floor(seconds / 60);

            if (minutes < 60) {
                return `${minutes}m`;
            }

            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;

            return remainingMinutes > 0
                ? `${hours}h ${remainingMinutes}m`
                : `${hours}h`;
        };

        const renderKitchenTimers = () => {
            const nowTimestamp = Date.now();

            kitchenTimers.forEach((timer) => {
                const startedAtIso = timer.getAttribute('data-started-at') || '';
                const label = timer.querySelector('[data-kitchen-timer-label]');

                if (!label) {
                    return;
                }

                label.textContent = formatKitchenElapsed(startedAtIso, nowTimestamp);
            });
        };

        renderKitchenTimers();
        window.setInterval(renderKitchenTimers, 1000);
    }

    const orderSelectionCards = Array.from(document.querySelectorAll('[data-order-select-url]'));

    if (orderSelectionCards.length > 0) {
        const isInteractiveTarget = (target) => target instanceof HTMLElement
            && Boolean(target.closest('a, button, input, select, textarea, summary, details, form, label'));

        const closeOrderMenus = (currentMenu = null) => {
            document.querySelectorAll('.order-side-item__menu[open]').forEach((menu) => {
                if (currentMenu && menu === currentMenu) {
                    return;
                }

                menu.removeAttribute('open');
            });
        };

        orderSelectionCards.forEach((card) => {
            const destination = card.getAttribute('data-order-select-url');

            if (!destination) {
                return;
            }

            card.addEventListener('click', (event) => {
                if (isInteractiveTarget(event.target)) {
                    return;
                }

                window.location.href = destination;
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                if (isInteractiveTarget(event.target)) {
                    return;
                }

                event.preventDefault();
                window.location.href = destination;
            });
        });

        document.querySelectorAll('.order-side-item__menu').forEach((menu) => {
            menu.addEventListener('toggle', () => {
                if (menu.hasAttribute('open')) {
                    closeOrderMenus(menu);
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (event.target instanceof HTMLElement && event.target.closest('.order-side-item__menu')) {
                return;
            }

            closeOrderMenus();
        });
    }

    const selectedOrderEditor = document.querySelector('[data-selected-order-editor]');

    if (selectedOrderEditor) {
        const searchInput = selectedOrderEditor.querySelector('[data-selected-order-search]');
        const categoryButtons = Array.from(selectedOrderEditor.querySelectorAll('[data-selected-order-category]'));
        const productButtons = Array.from(selectedOrderEditor.querySelectorAll('[data-selected-order-product]'));
        const addForm = selectedOrderEditor.querySelector('[data-selected-order-add-form]');
        const productInput = selectedOrderEditor.querySelector('[data-selected-order-product-input]');
        const printButton = selectedOrderEditor.querySelector('[data-print-selected-order]');

        if (printButton) {
            const clearPrintMode = () => {
                document.body.classList.remove('is-printing-order');
            };

            printButton.addEventListener('click', () => {
                document.body.classList.add('is-printing-order');
                window.print();
                window.setTimeout(clearPrintMode, 700);
            });

            window.addEventListener('afterprint', clearPrintMode);
        }

        if (searchInput && addForm && productInput) {
            const filterSelectedOrderCatalog = () => {
                const searchValue = searchInput.value.trim().toLowerCase();
                const activeButton = categoryButtons.find((button) => button.classList.contains('is-active'));
                const activeCategoryId = activeButton ? activeButton.getAttribute('data-selected-order-category') || '' : '';

                productButtons.forEach((button) => {
                    const categoryId = button.getAttribute('data-product-category-id') || '';
                    const haystack = [
                        button.getAttribute('data-product-name') || '',
                        button.getAttribute('data-product-category') || '',
                        button.getAttribute('data-product-area') || '',
                    ].join(' ').toLowerCase();

                    const matchesCategory = activeCategoryId === '' || categoryId === activeCategoryId;
                    const matchesSearch = searchValue === '' || haystack.includes(searchValue);

                    button.hidden = !(matchesCategory && matchesSearch);
                });
            };

            categoryButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    categoryButtons.forEach((item) => {
                        const isActive = item === button;
                        item.classList.toggle('is-active', isActive);
                        item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });

                    filterSelectedOrderCatalog();
                });
            });

            productButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const productId = button.getAttribute('data-product-id') || '';

                    if (!productId) {
                        return;
                    }

                    productInput.value = productId;

                    if (typeof addForm.requestSubmit === 'function') {
                        addForm.requestSubmit();
                        return;
                    }

                    addForm.submit();
                });
            });

            searchInput.addEventListener('input', filterSelectedOrderCatalog);
            filterSelectedOrderCatalog();
        }
    }

    const posBuilder = document.querySelector('[data-pos-builder]');
    const orderBuilderTrigger = document.querySelector('[data-order-builder-trigger]');
    const ordersSummary = document.getElementById('orders-summary');
    const ordersList = document.getElementById('orders-list');

    if (!posBuilder) {
        return;
    }

    const searchInput = posBuilder.querySelector('[data-pos-search]');
    const productButtons = Array.from(posBuilder.querySelectorAll('[data-product-card]'));
    const catalogButtons = Array.from(posBuilder.querySelectorAll('[data-product-item]'));
    const categoryButtons = Array.from(posBuilder.querySelectorAll('[data-category-filter]'));
    const cartList = posBuilder.querySelector('[data-pos-cart-list]');
    const itemsInput = posBuilder.querySelector('[data-pos-items-input]');
    const subtotalElement = posBuilder.querySelector('[data-pos-subtotal]');
    const taxElement = posBuilder.querySelector('[data-pos-tax]');
    const totalElement = posBuilder.querySelector('[data-pos-total]');
    const submitButton = posBuilder.querySelector('[data-pos-submit]');
    const itemCountElement = posBuilder.querySelector('[data-pos-item-count]');
    const form = posBuilder.querySelector('[data-inline-order-form]');
    const serviceButtons = Array.from(posBuilder.querySelectorAll('[data-service-value]'));
    const serviceInput = posBuilder.querySelector('[data-pos-service-input]');
    const posTableField = posBuilder.querySelector('[data-pos-table-field]');

    if (
        !searchInput
        || !cartList
        || !itemsInput
        || !subtotalElement
        || !taxElement
        || !totalElement
        || !submitButton
        || !itemCountElement
        || !form
        || !serviceInput
    ) {
        return;
    }

    const money = (value) => {
        const amount = Number(value || 0);
        return `S/ ${amount.toFixed(2)}`;
    };

    const dishIcon = `
        <span class="pos-cart-item__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M8 3v7"></path>
                <path d="M11 3v7"></path>
                <path d="M8 7H5.5A2.5 2.5 0 0 1 3 4.5V3"></path>
                <path d="M11 7h2.5A2.5 2.5 0 0 0 16 4.5V3"></path>
                <path d="M9.5 10v11"></path>
                <path d="M19 3c-1.7 2-2.5 4.2-2.5 6.7V21"></path>
            </svg>
        </span>
    `;
    const trashIcon = `
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4 7h16"></path>
            <path d="M9 7V5h6v2"></path>
            <path d="M7 7l1 12h8l1-12"></path>
            <path d="M10 11v5"></path>
            <path d="M14 11v5"></path>
        </svg>
    `;
    const emptyIcon = `
        <span class="pos-cart__empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M5 10h14l-1.2 8.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z"></path>
                <path d="M9 10V8a3 3 0 0 1 6 0v2"></path>
            </svg>
        </span>
    `;
    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const revealBuilder = () => {
        posBuilder.hidden = false;
        posBuilder.classList.add('is-open');

        if (ordersSummary) {
            ordersSummary.hidden = true;
        }

        if (ordersList) {
            ordersList.hidden = true;
        }

        if (orderBuilderTrigger) {
            orderBuilderTrigger.setAttribute('aria-expanded', 'true');
        }
    };

    if (!posBuilder.hidden) {
        revealBuilder();
    }

    if (orderBuilderTrigger) {
        orderBuilderTrigger.addEventListener('click', (event) => {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            revealBuilder();
            posBuilder.scrollIntoView({ behavior: 'smooth', block: 'start' });
            searchInput.focus();
        });
    }

    const catalog = new Map();

    productButtons.forEach((button) => {
        const id = button.getAttribute('data-product-id');

        if (!id || catalog.has(id)) {
            return;
        }

        catalog.set(id, {
            id,
            name: button.getAttribute('data-product-name') || 'Producto',
            price: Number(button.getAttribute('data-product-price') || 0),
            categoryId: button.getAttribute('data-product-category-id') || '',
            category: button.getAttribute('data-product-category') || '',
            area: button.getAttribute('data-product-area') || '',
            taxable: Number(button.getAttribute('data-product-taxable') || 0) === 1,
        });
    });

    const state = new Map();

    const loadDraftItems = () => {
        const raw = (itemsInput.value || '').trim();

        if (!raw) {
            return;
        }

        try {
            const parsed = JSON.parse(raw);

            if (!Array.isArray(parsed)) {
                return;
            }

            parsed.forEach((item) => {
                const productId = String(item.product_id ?? item.id ?? '');
                const quantity = Number(item.quantity ?? 0);

                if (!productId || !catalog.has(productId) || !Number.isInteger(quantity) || quantity <= 0) {
                    return;
                }

                state.set(productId, {
                    productId,
                    quantity,
                    note: String(item.note ?? ''),
                });
            });
        } catch (error) {
            console.warn('No se pudieron recuperar los items de la orden en borrador.', error);
        }
    };

    const syncServiceButtons = () => {
        const currentValue = serviceInput.value || 'MESA';
        const isTableService = currentValue === 'MESA';
        const tableSelect = posTableField ? posTableField.querySelector('select') : null;

        serviceButtons.forEach((button) => {
            const isActive = button.getAttribute('data-service-value') === currentValue;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (posTableField) {
            posTableField.hidden = !isTableService;
        }

        if (!isTableService && tableSelect) {
            tableSelect.value = '';
        }
    };

    const syncHiddenItems = () => {
        const serialized = Array.from(state.values()).map((entry) => ({
            product_id: Number(entry.productId),
            quantity: entry.quantity,
            note: entry.note || '',
        }));

        itemsInput.value = JSON.stringify(serialized);
    };

    const syncCatalogSelection = () => {
        productButtons.forEach((button) => {
            const productId = button.getAttribute('data-product-id') || '';
            const entry = state.get(productId);
            const quantity = entry ? String(entry.quantity) : '';

            button.classList.toggle('is-selected', Boolean(entry));
            button.setAttribute('data-selected-qty', quantity);
        });
    };

    const renderCart = () => {
        const entries = Array.from(state.values());
        const totalUnits = entries.reduce((carry, entry) => carry + entry.quantity, 0);
        let subtotal = 0;
        let taxableTotal = 0;

        itemCountElement.textContent = `${totalUnits} item(s)`;

        if (entries.length === 0) {
            cartList.innerHTML = `
                <div class="pos-cart__empty">
                    ${emptyIcon}
                    <strong>La orden esta vacia</strong>
                    <span>Haz clic en un plato para empezar a armar el pedido.</span>
                </div>
            `;
            subtotalElement.textContent = money(0);
            taxElement.textContent = money(0);
            totalElement.textContent = money(0);
            submitButton.disabled = true;
            syncHiddenItems();
            syncCatalogSelection();
            return;
        }

        const rows = entries.map((entry) => {
            const product = catalog.get(entry.productId);

            if (!product) {
                return '';
            }

            const lineTotal = product.price * entry.quantity;
            subtotal += lineTotal;

            if (product.taxable) {
                taxableTotal += lineTotal;
            }

            return `
                <article class="pos-cart-item">
                    <div class="pos-cart-item__main">
                        <div class="pos-cart-item__title">
                            ${dishIcon}
                            <span class="pos-cart-item__badge">${entry.quantity}x</span>
                            <div>
                                <strong>${escapeHtml(product.name)}</strong>
                                <span>${escapeHtml(product.category || 'Carta')}</span>
                            </div>
                        </div>
                        <div class="pos-cart-item__side">
                            <strong class="pos-cart-item__price">${money(lineTotal)}</strong>
                            <button
                                type="button"
                                class="pos-cart-item__remove"
                                data-cart-action="remove"
                                data-product-id="${escapeHtml(entry.productId)}"
                                title="Quitar plato"
                                aria-label="Quitar ${escapeHtml(product.name)}"
                            >
                                ${trashIcon}
                            </button>
                        </div>
                    </div>
                    <div class="pos-cart-item__controls">
                        <div class="pos-qty-control">
                            <button type="button" data-cart-action="decrease" data-product-id="${escapeHtml(entry.productId)}">-</button>
                            <span>${entry.quantity}</span>
                            <button type="button" data-cart-action="increase" data-product-id="${escapeHtml(entry.productId)}">+</button>
                        </div>
                        <div class="pos-cart-item__meta">
                            <span>${escapeHtml(product.area || 'Carta general')}</span>
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        const igv = taxableTotal > 0 ? taxableTotal - (taxableTotal / 1.18) : 0;

        cartList.innerHTML = rows;
        subtotalElement.textContent = money(subtotal);
        taxElement.textContent = money(igv);
        totalElement.textContent = money(subtotal);
        submitButton.disabled = false;
        syncHiddenItems();
        syncCatalogSelection();
    };

    const addItem = (productId) => {
        const currentEntry = state.get(productId);

        if (currentEntry) {
            currentEntry.quantity += 1;
            state.set(productId, currentEntry);
        } else {
            state.set(productId, {
                productId,
                quantity: 1,
                note: '',
            });
        }

        revealBuilder();
        renderCart();
    };

    const filterCatalog = () => {
        const searchValue = searchInput.value.trim().toLowerCase();
        const activeButton = categoryButtons.find((button) => button.classList.contains('is-active'));
        const activeCategoryId = activeButton ? activeButton.getAttribute('data-category-filter') || '' : '';

        catalogButtons.forEach((button) => {
            const categoryId = button.getAttribute('data-product-category-id') || '';
            const haystack = [
                button.getAttribute('data-product-name') || '',
                button.getAttribute('data-product-category') || '',
                button.getAttribute('data-product-area') || '',
            ].join(' ').toLowerCase();

            const matchesCategory = activeCategoryId === '' || categoryId === activeCategoryId;
            const matchesSearch = searchValue === '' || haystack.includes(searchValue);

            button.hidden = !(matchesCategory && matchesSearch);
        });
    };

    productButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');

            if (!productId) {
                return;
            }

            addItem(productId);
        });
    });

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            categoryButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            filterCatalog();
        });
    });

    searchInput.addEventListener('input', filterCatalog);

    cartList.addEventListener('click', (event) => {
        const target = event.target;

        if (!(target instanceof HTMLElement)) {
            return;
        }

        const action = target.getAttribute('data-cart-action');
        const productId = target.getAttribute('data-product-id');

        if (!action || !productId || !state.has(productId)) {
            return;
        }

        const currentEntry = state.get(productId);

        if (!currentEntry) {
            return;
        }

        if (action === 'increase') {
            currentEntry.quantity += 1;
            state.set(productId, currentEntry);
        }

        if (action === 'decrease') {
            currentEntry.quantity -= 1;

            if (currentEntry.quantity <= 0) {
                state.delete(productId);
            } else {
                state.set(productId, currentEntry);
            }
        }

        if (action === 'remove') {
            state.delete(productId);
        }

        renderCart();
    });

    serviceButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const serviceValue = button.getAttribute('data-service-value') || 'MESA';
            serviceInput.value = serviceValue;
            syncServiceButtons();
        });
    });

    form.addEventListener('submit', (event) => {
        if (state.size > 0) {
            return;
        }

        event.preventDefault();
        window.alert('Agrega al menos un plato antes de crear la orden.');
    });

    loadDraftItems();
    syncServiceButtons();
    filterCatalog();
    renderCart();
});
