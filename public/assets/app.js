(() => {
    const authExpiresAt = Number(document.body.dataset.authExpiresAt || 0);
    const loginUrl = document.body.dataset.loginUrl || '';
    const landingUrl = document.body.dataset.landingUrl || '/';
    const logoutUrl = document.body.dataset.logoutUrl || '';
    const sessionHeartbeatUrl = document.body.dataset.sessionHeartbeatUrl || '';
    const csrfName = document.body.dataset.csrfName || '';
    const csrfHash = document.body.dataset.csrfHash || '';
    const activeTabStorageKey = 'j-operasional-active-tab';
    const currentTabStorageKey = 'j-operasional-current-tab';
    const tabWindowPrefix = 'j-operasional-tab:';
    const logoutSignalStorageKey = 'j-operasional-force-logout';
    const activeTabLifetimeMilliseconds = 60000;
    const activeTabHeartbeatMilliseconds = 5000;
    const serverHeartbeatMilliseconds = 30000;

    const randomTabId = () => {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    };

    const readActiveTab = () => {
        const stored = localStorage.getItem(activeTabStorageKey);
        if (!stored) return null;

        try {
            const parsed = JSON.parse(stored);
            if (parsed && typeof parsed.id === 'string' && Number.isFinite(Number(parsed.seenAt))) {
                return { id: parsed.id, seenAt: Number(parsed.seenAt) };
            }
        } catch (error) {
            // Format lama hanya berisi ID tab dan dianggap sudah kedaluwarsa.
        }

        return { id: stored, seenAt: 0 };
    };

    const writeActiveTab = (tabId) => {
        localStorage.setItem(activeTabStorageKey, JSON.stringify({ id: tabId, seenAt: Date.now() }));
    };

    const removeOwnedActiveTab = (tabId) => {
        const activeTab = readActiveTab();
        if (activeTab?.id === tabId) localStorage.removeItem(activeTabStorageKey);
    };

    const showDuplicateTabBlocker = () => {
        document.body.className = 'duplicate-tab-page';
        document.body.innerHTML = `
            <main class="duplicate-tab-card" role="alert" aria-live="assertive">
                <span class="duplicate-tab-icon" aria-hidden="true">!</span>
                <p>AKSES DIBATASI</p>
                <h1>Akun sedang digunakan di tab lain</h1>
                <span data-duplicate-tab-message>Akses pada tab ini diblokir. Tutup tab sebelumnya, lalu pilih Periksa kembali.</span>
                <div class="duplicate-tab-actions">
                    <button type="button" class="btn btn-primary" data-duplicate-tab-retry>Periksa kembali</button>
                </div>
            </main>`;
        const retryButton = document.querySelector('[data-duplicate-tab-retry]');
        const blockerMessage = document.querySelector('[data-duplicate-tab-message]');

        retryButton?.addEventListener('click', async () => {
            const currentTabId = sessionStorage.getItem(currentTabStorageKey) || '';
            const activeTab = readActiveTab();
            const activeTabIsFresh = activeTab && (Date.now() - activeTab.seenAt) < activeTabLifetimeMilliseconds;

            if (!activeTabIsFresh && currentTabId !== '') {
                writeActiveTab(currentTabId);
                window.location.reload();
                return;
            }

            if (logoutUrl === '' || csrfName === '' || csrfHash === '') return;
            retryButton.disabled = true;
            retryButton.textContent = 'Memproses kembali...';

            try {
                const formData = new FormData();
                formData.append(csrfName, csrfHash);
                const response = await fetch(logoutUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Sesi belum dapat diakhiri.');

                try {
                    localStorage.removeItem(activeTabStorageKey);
                    localStorage.setItem(logoutSignalStorageKey, String(Date.now()));
                    sessionStorage.removeItem(currentTabStorageKey);
                    window.name = '';
                } catch (error) {}

                window.location.replace(landingUrl);
            } catch (error) {
                if (blockerMessage) blockerMessage.textContent = error.message || 'Sesi belum dapat diakhiri. Silakan coba kembali.';
                retryButton.disabled = false;
                retryButton.textContent = 'Periksa kembali';
            }
        });
    };

    window.addEventListener('storage', (event) => {
        if (event.key !== logoutSignalStorageKey || !event.newValue) return;
        try {
            sessionStorage.removeItem(currentTabStorageKey);
            window.name = '';
        } catch (error) {}
        window.location.replace(landingUrl);
    });

    let duplicateTabDetected = false;
    if (loginUrl !== '') {
        try {
            const storedTabId = sessionStorage.getItem(currentTabStorageKey);
            const windowTabId = window.name.startsWith(tabWindowPrefix)
                ? window.name.slice(tabWindowPrefix.length)
                : '';
            const currentTabId = storedTabId !== '' && storedTabId === windowTabId
                ? storedTabId
                : randomTabId();

            sessionStorage.setItem(currentTabStorageKey, currentTabId);
            window.name = `${tabWindowPrefix}${currentTabId}`;

            const activeTab = readActiveTab();
            const activeTabIsFresh = activeTab && (Date.now() - activeTab.seenAt) < activeTabLifetimeMilliseconds;
            if (activeTabIsFresh && activeTab.id !== currentTabId) {
                duplicateTabDetected = true;
            } else {
                writeActiveTab(currentTabId);
            }
        } catch (error) {
            // Browser tanpa Web Storage tetap memakai validasi sesi server.
        }
    }

    if (duplicateTabDetected) {
        showDuplicateTabBlocker();
        return;
    }

    if (loginUrl !== '') {
        const currentTabId = sessionStorage.getItem(currentTabStorageKey) || '';
        const maintainActiveTab = () => {
            if (currentTabId !== '') writeActiveTab(currentTabId);
        };

        maintainActiveTab();
        window.setInterval(maintainActiveTab, activeTabHeartbeatMilliseconds);
        window.addEventListener('pagehide', () => removeOwnedActiveTab(currentTabId));
        window.addEventListener('beforeunload', () => removeOwnedActiveTab(currentTabId));
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) maintainActiveTab();
        });
    }

    if (sessionHeartbeatUrl !== '' && csrfName !== '' && csrfHash !== '') {
        const sendSessionHeartbeat = async () => {
            try {
                const formData = new FormData();
                formData.append(csrfName, csrfHash);
                const response = await fetch(sessionHeartbeatUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (response.status === 401) window.location.replace(landingUrl);
            } catch (error) {
                // Gangguan jaringan sementara tidak langsung mengeluarkan pengguna.
            }
        };

        sendSessionHeartbeat();
        window.setInterval(sendSessionHeartbeat, serverHeartbeatMilliseconds);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) sendSessionHeartbeat();
        });
    }

    if (authExpiresAt > 0 && loginUrl !== '') {
        const redirectToLogin = () => window.location.replace(loginUrl);
        const remainingMilliseconds = (authExpiresAt * 1000) - Date.now();

        if (remainingMilliseconds <= 0) {
            redirectToLogin();
            return;
        }

        window.setTimeout(redirectToLogin, remainingMilliseconds);
    }

    const systemDate = document.querySelector('[data-system-date]');
    const systemTime = document.querySelector('[data-system-time]');
    const systemTimeElement = systemTime?.closest('time');
    if (systemDate || systemTime) {
        const dateFormatter = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Jakarta',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
        const timeFormatter = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23',
        });
        const updateSystemClock = () => {
            const now = new Date();
            if (systemDate) systemDate.textContent = dateFormatter.format(now);
            if (systemTime) systemTime.textContent = timeFormatter.format(now);
            if (systemTimeElement) systemTimeElement.dateTime = now.toISOString();
        };

        updateSystemClock();
        window.setInterval(updateSystemClock, 1000);
    }

    const reloadOperationalPage = () => {
        if (typeof window.__operationalRouteUrl === 'string' && window.__operationalRouteUrl !== '') {
            window.location.replace(window.__operationalRouteUrl);
            return;
        }

        window.location.reload();
    };

    const sidebar = document.querySelector('#sidebar');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const desktopMedia = window.matchMedia('(min-width: 901px)');
    const sidebarStorageKey = 'j-operasional-sidebar';
    const storedSidebarState = () => {
        try { return localStorage.getItem(sidebarStorageKey); } catch (error) { return null; }
    };
    const updateToggleAccessibility = (expanded) => {
        if (!sidebarToggle) return;
        sidebarToggle.setAttribute('aria-expanded', String(expanded));
        sidebarToggle.setAttribute('aria-label', expanded ? 'Tutup menu' : 'Buka menu');
        sidebarToggle.title = expanded ? 'Tutup sidebar' : 'Buka sidebar';
    };
    const setMobileSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
        updateToggleAccessibility(open);
    };
    const setDesktopSidebar = (collapsed, persist = true) => {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        sidebar?.classList.remove('open');
        document.body.style.overflow = '';
        updateToggleAccessibility(!collapsed);
        if (!persist) return;
        try { localStorage.setItem(sidebarStorageKey, collapsed ? 'collapsed' : 'expanded'); } catch (error) {}
    };

    if (desktopMedia.matches) {
        setDesktopSidebar(storedSidebarState() === 'collapsed', false);
    } else {
        document.documentElement.classList.remove('sidebar-collapsed');
        updateToggleAccessibility(false);
    }

    sidebarToggle?.addEventListener('click', () => {
        if (desktopMedia.matches) {
            setDesktopSidebar(!document.documentElement.classList.contains('sidebar-collapsed'));
            return;
        }

        setMobileSidebar(!sidebar?.classList.contains('open'));
    });
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setMobileSidebar(false));
    window.addEventListener('resize', () => {
        if (desktopMedia.matches) {
            setDesktopSidebar(storedSidebarState() === 'collapsed', false);
            return;
        }

        document.documentElement.classList.remove('sidebar-collapsed');
        setMobileSidebar(false);
    });

    const navigationGroups = Array.from(document.querySelectorAll('[data-nav-group]'));
    const setNavigationGroupOpen = (group, open) => {
        const groupToggle = group?.querySelector('[data-nav-toggle]');
        const groupSubmenu = group?.querySelector('[data-nav-submenu]');
        if (!group || !groupToggle || !groupSubmenu) return;

        group.classList.toggle('open', open);
        groupToggle.setAttribute('aria-expanded', String(open));
        groupSubmenu.hidden = !open;
    };

    document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const group = toggle.closest('[data-nav-group]');
            const submenu = group?.querySelector('[data-nav-submenu]');
            if (!group || !submenu) return;
            const open = !group.classList.contains('open');

            // Pindahkan sorotan menu saat induk dipilih tanpa mengganti halaman.
            document.querySelectorAll('.main-nav > .nav-link.active, [data-nav-toggle].active')
                .forEach((activeLink) => activeLink.classList.remove('active'));
            toggle.classList.add('active');

            if (open) {
                navigationGroups.forEach((otherGroup) => {
                    if (otherGroup !== group) setNavigationGroupOpen(otherGroup, false);
                });
            }

            setNavigationGroupOpen(group, open);
        });
    });

    const profileMenu = document.querySelector('[data-profile-menu]');
    const profileToggle = profileMenu?.querySelector('[data-profile-toggle]');
    const profileDropdown = profileMenu?.querySelector('[data-profile-dropdown]');
    const notificationMenu = document.querySelector('[data-notification-menu]');
    const notificationToggle = notificationMenu?.querySelector('[data-notification-toggle]');
    const notificationDropdown = notificationMenu?.querySelector('[data-notification-dropdown]');
    const setProfileMenuOpen = (open) => {
        if (!profileMenu || !profileToggle || !profileDropdown) return;
        profileMenu.classList.toggle('open', open);
        profileToggle.setAttribute('aria-expanded', String(open));
        profileDropdown.hidden = !open;
    };
    const setNotificationMenuOpen = (open) => {
        if (!notificationMenu || !notificationToggle || !notificationDropdown) return;
        notificationMenu.classList.toggle('open', open);
        notificationToggle.setAttribute('aria-expanded', String(open));
        notificationDropdown.hidden = !open;
    };

    profileToggle?.addEventListener('click', () => {
        setNotificationMenuOpen(false);
        setProfileMenuOpen(!profileMenu.classList.contains('open'));
    });
    notificationToggle?.addEventListener('click', () => {
        setProfileMenuOpen(false);
        setNotificationMenuOpen(!notificationMenu.classList.contains('open'));
    });
    document.addEventListener('click', (event) => {
        if (profileMenu?.classList.contains('open') && !profileMenu.contains(event.target)) {
            setProfileMenuOpen(false);
        }
        if (notificationMenu?.classList.contains('open') && !notificationMenu.contains(event.target)) {
            setNotificationMenuOpen(false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (profileMenu?.classList.contains('open')) {
            setProfileMenuOpen(false);
            profileToggle?.focus();
        }
        if (notificationMenu?.classList.contains('open')) {
            setNotificationMenuOpen(false);
            notificationToggle?.focus();
        }
    });

    document.querySelectorAll('.alert-close').forEach((button) => {
        button.addEventListener('click', () => button.closest('.alert')?.remove());
    });

    const successToast = document.querySelector('[data-success-toast]');
    const closeSuccessToast = () => {
        if (!successToast || successToast.classList.contains('closing')) return;
        successToast.classList.add('closing');
        window.setTimeout(() => successToast.remove(), 220);
    };
    successToast?.querySelector('[data-success-toast-close]')?.addEventListener('click', closeSuccessToast);
    if (successToast) window.setTimeout(closeSuccessToast, 4500);

    document.querySelectorAll('[data-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });

    document.querySelectorAll('[data-table-length]').forEach((select) => {
        select.addEventListener('change', () => select.form?.requestSubmit());
    });

    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const updateDay = (dateInput) => {
        const output = dateInput.closest('form')?.querySelector('[data-day-output]');
        if (!output) return;
        const date = dateInput.value ? new Date(`${dateInput.value}T12:00:00`) : null;
        output.value = date && !Number.isNaN(date.getTime()) ? dayNames[date.getDay()] : '';
    };

    document.querySelectorAll('[data-date-input]').forEach((input) => {
        updateDay(input);
        input.addEventListener('change', () => updateDay(input));
    });

    const perihalSelectors = new WeakMap();
    const setupPerihalSelector = (form) => {
        if (form && perihalSelectors.has(form)) return perihalSelectors.get(form);
        const select = form?.querySelector('[data-perihal-select]');
        const customGroup = form?.querySelector('[data-perihal-custom]');
        const customInput = form?.querySelector('[data-perihal-custom-input]');
        if (!select || !customGroup || !customInput) return null;

        const sync = () => {
            const isOther = select.value === 'Lainnya';
            customGroup.hidden = !isOther;
            customInput.disabled = !isOther;
            customInput.required = isOther;
            if (!isOther) customInput.value = '';
        };

        select.addEventListener('change', () => {
            sync();
            if (select.value === 'Lainnya') customInput.focus();
        });
        sync();

        const controller = {
            setValue(value) {
                if (value === 'Confidential Documents') {
                    select.value = 'Confidential Documents';
                    customInput.value = '';
                } else if (value && value !== '-') {
                    select.value = 'Lainnya';
                    customInput.value = value;
                } else {
                    select.value = '';
                    customInput.value = '';
                }
                sync();
            },
        };
        perihalSelectors.set(form, controller);
        return controller;
    };

    document.querySelectorAll('form').forEach((form) => setupPerihalSelector(form));

    const jenisSelectors = new WeakMap();
    const setupJenisSelector = (form) => {
        if (form && jenisSelectors.has(form)) return jenisSelectors.get(form);
        const select = form?.querySelector('[data-jenis-select]');
        const customGroup = form?.querySelector('[data-jenis-custom]');
        const customInput = form?.querySelector('[data-jenis-custom-input]');
        if (!select || !customGroup || !customInput) return null;

        const sync = () => {
            const isOther = select.value === 'Lainnya';
            customGroup.hidden = !isOther;
            customInput.disabled = !isOther;
            customInput.required = isOther;
            if (!isOther) customInput.value = '';
        };

        select.addEventListener('change', () => {
            sync();
            if (select.value === 'Lainnya') customInput.focus();
        });
        sync();

        const controller = {
            setValue(value) {
                const normalized = String(value || '');
                const known = Array.from(select.options).some((option) => option.value === normalized && normalized !== 'Lainnya');
                if (known) {
                    select.value = normalized;
                    customInput.value = '';
                } else if (normalized) {
                    select.value = 'Lainnya';
                    customInput.value = normalized;
                } else {
                    select.value = '';
                    customInput.value = '';
                }
                sync();
            },
        };
        jenisSelectors.set(form, controller);
        return controller;
    };

    document.querySelectorAll('form').forEach((form) => setupJenisSelector(form));

    const ekspedisiSelectors = new WeakMap();
    const setupEkspedisiSelector = (form) => {
        if (form && ekspedisiSelectors.has(form)) return ekspedisiSelectors.get(form);
        const select = form?.querySelector('[data-ekspedisi-select]');
        const customGroup = form?.querySelector('[data-ekspedisi-custom]');
        const customInput = form?.querySelector('[data-ekspedisi-custom-input]');
        if (!select || !customGroup || !customInput) return null;

        const sync = () => {
            const isOther = select.value === 'Lainnya';
            customGroup.hidden = !isOther;
            customInput.disabled = !isOther;
            customInput.required = isOther;
            if (!isOther) customInput.value = '';
        };

        select.addEventListener('change', () => {
            sync();
            if (select.value === 'Lainnya') customInput.focus();
        });
        sync();

        const controller = {
            setValue(value) {
                const normalized = value === '-' ? '' : String(value || '');
                const known = Array.from(select.options).some((option) => option.value === normalized && normalized !== 'Lainnya');
                if (known) {
                    select.value = normalized;
                    customInput.value = '';
                } else if (normalized) {
                    select.value = 'Lainnya';
                    customInput.value = normalized;
                } else {
                    select.value = '';
                    customInput.value = '';
                }
                sync();
            },
        };
        ekspedisiSelectors.set(form, controller);
        return controller;
    };

    document.querySelectorAll('form').forEach((form) => setupEkspedisiSelector(form));

    const modal = document.querySelector('#inputDokumenModal');
    const modalForm = document.querySelector('#modalDokumenForm');
    const modalErrors = modal?.querySelector('[data-modal-errors]');
    const modalStatus = modal?.querySelector('[data-modal-status]');
    const modalSubmit = modal?.querySelector('[data-modal-submit]');

    const openModal = () => {
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => modal.querySelector('input:not([type="hidden"])')?.focus(), 180);
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { modal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-open-input-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openModal();
        });
    });

    modal?.querySelectorAll('[data-modal-close]').forEach((trigger) => trigger.addEventListener('click', closeModal));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal?.classList.contains('open')) closeModal();
    });

    const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
    }[char]));

    const securityHandoverItemsHtml = (items) => items.map((item) => `
        <article class="security-handover-history-item">
            <div class="security-handover-route">
                <span><small>Security Lama</small><strong>${escapeHtml(item.security_dari)}</strong></span>
                <b aria-hidden="true">→</b>
                <span><small>Security Baru</small><strong>${escapeHtml(item.security_ke)}</strong></span>
            </div>
            <small>${escapeHtml(item.waktu)} · Dicatat oleh ${escapeHtml(item.dicatat_oleh)}</small>
        </article>
    `).join('');

    const showErrors = (message, errors = []) => {
        if (!modalErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        modalErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        modalErrors.classList.remove('success');
        modalErrors.hidden = false;
    };

    const updateCsrf = (csrf) => {
        if (!csrf?.name || !csrf?.hash) return;
        document.querySelectorAll('input[type="hidden"]').forEach((token) => {
            token.name = csrf.name;
            token.value = csrf.hash;
        });
    };

    modalForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        modalErrors.hidden = true;
        modalStatus.textContent = 'Menyimpan data...';
        modalSubmit.disabled = true;

        try {
            const response = await fetch(modalForm.action, {
                method: 'POST',
                body: new FormData(modalForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);

            if (!response.ok || !result.success) {
                showErrors(result.message || 'Data belum dapat disimpan.', result.errors || []);
                return;
            }

            modalErrors.innerHTML = `<strong>✓ ${escapeHtml(result.message)}</strong>`;
            modalErrors.classList.add('success');
            modalErrors.hidden = false;
            modalStatus.textContent = 'Berhasil disimpan';
            window.setTimeout(reloadOperationalPage, 650);
        } catch (error) {
            showErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            modalSubmit.disabled = false;
            if (modalStatus.textContent === 'Menyimpan data...') modalStatus.textContent = '';
        }
    });

    const detailModal = document.querySelector('#detailDokumenModal');
    const detailLoading = detailModal?.querySelector('[data-detail-loading]');
    const detailContent = detailModal?.querySelector('[data-detail-content]');
    const detailError = detailModal?.querySelector('[data-detail-error]');
    const detailErrorMessage = detailModal?.querySelector('[data-detail-error-message]');
    const detailEdit = detailModal?.querySelector('[data-detail-edit]');
    const securityHandoverHistory = detailModal?.querySelector('[data-security-handover-history]');
    const securityHandoverHistoryList = detailModal?.querySelector('[data-security-handover-history-list]');
    let currentDetailUrl = '';

    const setDetailState = (state) => {
        if (!detailLoading || !detailContent || !detailError) return;
        detailLoading.hidden = state !== 'loading';
        detailContent.hidden = state !== 'content';
        detailError.hidden = state !== 'error';
    };

    const loadDetail = async () => {
        if (!currentDetailUrl) return;
        setDetailState('loading');

        try {
            const response = await fetch(currentDetailUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const responseType = response.headers.get('content-type') || '';
            const result = responseType.includes('application/json') ? await response.json() : null;
            if (!response.ok || !result?.success) {
                throw new Error(`Detail tidak dapat dimuat (HTTP ${response.status}).`);
            }

            Object.entries(result.dokumen).forEach(([field, value]) => {
                detailModal.querySelectorAll(`[data-detail-field="${field}"]`).forEach((element) => {
                    element.textContent = value;
                });
            });
            const penyerahanTime = detailModal.querySelector('[data-penyerahan-time]');
            if (penyerahanTime) penyerahanTime.hidden = !result.dokumen.penyerahan_at;
            const handoverItems = Array.isArray(result.dokumen.serah_terima_history) ? result.dokumen.serah_terima_history : [];
            if (securityHandoverHistory && securityHandoverHistoryList) {
                securityHandoverHistory.hidden = handoverItems.length === 0;
                securityHandoverHistoryList.innerHTML = handoverItems.map((item) => `
                    <article class="security-handover-history-item">
                        <div class="security-handover-route">
                            <span><small>Security Lama</small><strong>${escapeHtml(item.security_dari)}</strong></span>
                            <b aria-hidden="true">→</b>
                            <span><small>Security Baru</small><strong>${escapeHtml(item.security_ke)}</strong></span>
                        </div>
                        <small>${escapeHtml(item.waktu)} · Dicatat oleh ${escapeHtml(item.dicatat_oleh)}</small>
                    </article>
                `).join('');
            }
            if (detailEdit) {
                detailEdit.href = result.dokumen.edit_url;
                detailEdit.dataset.detailUrl = currentDetailUrl;
            }
            setDetailState('content');
        } catch (error) {
            if (detailErrorMessage) detailErrorMessage.textContent = error.message || 'Detail tidak dapat dimuat.';
            setDetailState('error');
        }
    };

    const openDetailModal = (url) => {
        if (!detailModal) return;
        currentDetailUrl = url;
        detailModal.hidden = false;
        detailModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => detailModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        loadDetail();
    };

    const closeDetailModal = () => {
        if (!detailModal) return;
        detailModal.classList.remove('open');
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { detailModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-open-detail-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openDetailModal(trigger.dataset.detailUrl || trigger.href);
        });
    });

    detailModal?.querySelectorAll('[data-detail-close]').forEach((trigger) => trigger.addEventListener('click', closeDetailModal));
    detailModal?.querySelector('[data-detail-retry]')?.addEventListener('click', loadDetail);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && detailModal?.classList.contains('open')) closeDetailModal();
    });

    const editModal = document.querySelector('#editDokumenModal');
    const editForm = editModal?.querySelector('[data-edit-form]');
    const editLoading = editModal?.querySelector('[data-edit-loading]');
    const editErrors = editModal?.querySelector('[data-edit-errors]');
    const editStatus = editModal?.querySelector('[data-edit-status]');
    const editSubmitButtons = editModal?.querySelectorAll('[data-edit-submit]') || [];
    const editHandoverInfo = editModal?.querySelector('[data-edit-handover-info]');
    const editHandoverValue = editModal?.querySelector('[data-edit-handover-value]');
    const editHandoverPanel = editModal?.querySelector('[data-edit-handover-panel]');
    const editHandoverSelect = editModal?.querySelector('[data-edit-handover-select]');
    const editPerihalSelector = setupPerihalSelector(editForm);
    const editJenisSelector = setupJenisSelector(editForm);
    let currentEditUrl = '';

    const setEditHandoverPanel = (open) => {
        if (!editHandoverPanel || !editHandoverSelect) return;
        editHandoverPanel.hidden = !open;
        editHandoverSelect.disabled = !open;
        editHandoverSelect.required = open;
        if (!open) editHandoverSelect.value = '';
        if (open) {
            editHandoverPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            window.setTimeout(() => editHandoverSelect.focus(), 180);
        }
    };

    const closeEditModal = () => {
        if (!editModal) return;
        editModal.classList.remove('open');
        editModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { editModal.hidden = true; }, 180);
    };

    const showEditErrors = (message, errors = []) => {
        if (!editErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        editErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        editErrors.classList.remove('success');
        editErrors.hidden = false;
    };

    const loadEdit = async () => {
        editLoading.hidden = false;
        editForm.hidden = true;
        editErrors.hidden = true;

        try {
            const response = await fetch(currentEditUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error('Data tidak tersedia.');

            const data = result.dokumen;
            editForm.action = data.update_url;
            editForm.elements.pengirim.value = data.pengirim;
            editPerihalSelector?.setValue(data.perihal);
            editForm.elements.penerima.value = data.penerima === '-' ? '' : data.penerima;
            editForm.elements.tanggal.value = data.tanggal_value;
            editJenisSelector?.setValue(data.jenis);
            editForm.elements.jumlah.value = data.jumlah.replace(/\./g, '');
            editForm.elements.satuan_jumlah.value = data.satuan_jumlah_value || '';
            setupEkspedisiSelector(editForm)?.setValue(data.ekspedisi);
            if (editHandoverInfo && editHandoverValue) {
                editHandoverInfo.hidden = false;
                editHandoverValue.textContent = data.security_penanggung_jawab || 'Belum ditentukan';
            }
            setEditHandoverPanel(false);
            editForm.elements.tanggal.dispatchEvent(new Event('change'));
            editLoading.hidden = true;
            editForm.hidden = false;
        } catch (error) {
            editLoading.innerHTML = '<strong>Data edit tidak dapat dimuat.</strong>';
        }
    };

    const openEditModal = (url) => {
        if (!editModal || !url) return;
        if (detailModal?.classList.contains('open')) closeDetailModal();
        currentEditUrl = url;
        editModal.hidden = false;
        editModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => editModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        loadEdit();
    };

    document.querySelectorAll('[data-open-edit-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openEditModal(trigger.dataset.detailUrl);
        });
    });

    editModal?.querySelectorAll('[data-edit-close]').forEach((trigger) => trigger.addEventListener('click', closeEditModal));
    editModal?.querySelector('[data-edit-handover-open]')?.addEventListener('click', () => setEditHandoverPanel(true));
    editModal?.querySelector('[data-edit-handover-cancel]')?.addEventListener('click', () => setEditHandoverPanel(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && editModal?.classList.contains('open')) closeEditModal();
    });

    editForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const editAction = event.submitter?.dataset.editAction || 'save';
        editErrors.hidden = true;
        editStatus.textContent = editAction === 'handover' ? 'Mencatat serah terima...' : 'Menyimpan perubahan...';
        editSubmitButtons.forEach((button) => { button.disabled = true; });

        try {
            const formData = new FormData(editForm);
            formData.set('submit_action', editAction);
            const response = await fetch(editForm.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);

            if (!response.ok || !result.success) {
                showEditErrors(result.message || 'Perubahan belum dapat disimpan.', result.errors || []);
                return;
            }

            editErrors.innerHTML = `<strong>✓ ${escapeHtml(result.message)}</strong>`;
            editErrors.classList.add('success');
            editErrors.hidden = false;
            editStatus.textContent = 'Berhasil diperbarui';
            window.setTimeout(reloadOperationalPage, 650);
        } catch (error) {
            showEditErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            editSubmitButtons.forEach((button) => { button.disabled = false; });
            if (['Menyimpan perubahan...', 'Mencatat serah terima...'].includes(editStatus.textContent)) editStatus.textContent = '';
        }
    });

    const deleteModal = document.querySelector('#deleteDokumenModal');
    const deleteForm = deleteModal?.querySelector('[data-delete-form]');
    const deleteLabel = deleteModal?.querySelector('[data-delete-label]');
    const deleteTitle = deleteModal?.querySelector('[data-delete-title]');
    const deleteDescription = deleteModal?.querySelector('[data-delete-description]');
    const deleteError = deleteModal?.querySelector('[data-delete-error]');
    const deleteSubmit = deleteModal?.querySelector('[data-delete-submit]');

    const openDeleteModal = (url, label, locked = false) => {
        if (!deleteModal || !url) return;
        if (detailModal?.classList.contains('open')) closeDetailModal();
        deleteForm.action = url;
        deleteLabel.textContent = label || 'yang dipilih';
        deleteTitle.textContent = locked ? 'Dokumen tidak dapat dihapus' : 'Hapus dokumen?';
        deleteDescription.hidden = locked;
        deleteSubmit.hidden = locked;
        deleteSubmit.disabled = locked;
        deleteForm.classList.toggle('locked', locked);
        deleteError.textContent = locked ? 'Dokumen sudah diserahkan dan tidak dapat dihapus dari sistem.' : '';
        deleteError.hidden = !locked;
        deleteModal.hidden = false;
        deleteModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => deleteModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };

    const closeDeleteModal = () => {
        if (!deleteModal) return;
        deleteModal.classList.remove('open');
        deleteModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { deleteModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-open-delete-modal]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openDeleteModal(trigger.dataset.deleteUrl, trigger.dataset.deleteLabel, trigger.dataset.deleteLocked === '1');
        });
    });

    deleteModal?.querySelectorAll('[data-delete-close]').forEach((trigger) => trigger.addEventListener('click', closeDeleteModal));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && deleteModal?.classList.contains('open')) closeDeleteModal();
    });

    deleteForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        deleteError.hidden = true;
        deleteSubmit.disabled = true;
        deleteSubmit.textContent = 'Menghapus...';

        try {
            const response = await fetch(deleteForm.action, {
                method: 'POST',
                body: new FormData(deleteForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);

            if (!response.ok || !result.success) throw new Error(result.message || 'Dokumen gagal dihapus.');
            window.setTimeout(reloadOperationalPage, 250);
        } catch (error) {
            deleteError.textContent = error.message;
            deleteError.hidden = false;
        } finally {
            deleteSubmit.disabled = false;
            deleteSubmit.textContent = 'Ya, hapus dokumen';
        }
    });

    const distributionModal = document.querySelector('#distributionActionModal');
    const distributionForm = distributionModal?.querySelector('[data-distribution-form]');
    const distributionLoading = distributionModal?.querySelector('[data-distribution-loading]');
    const distributionErrors = distributionModal?.querySelector('[data-distribution-errors]');
    const distributionStatus = distributionModal?.querySelector('[data-distribution-status]');
    const distributionSubmit = distributionModal?.querySelector('[data-distribution-submit]');
    let currentDistributionUrl = '';

    const closeDistributionModal = () => {
        if (!distributionModal) return;
        distributionModal.classList.remove('open');
        distributionModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { distributionModal.hidden = true; }, 180);
    };

    const showDistributionErrors = (message, errors = []) => {
        if (!distributionErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        distributionErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        distributionErrors.classList.remove('success');
        distributionErrors.hidden = false;
    };

    const loadDistributionAction = async () => {
        distributionLoading.hidden = false;
        distributionForm.hidden = true;
        distributionErrors.hidden = true;

        try {
            const response = await fetch(currentDistributionUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Data tidak tersedia.');

            Object.entries(result.dokumen).forEach(([field, value]) => {
                distributionModal.querySelectorAll(`[data-distribution-field="${field}"]`).forEach((element) => {
                    if ('value' in element) element.value = value;
                    else element.textContent = value;
                });
            });
            distributionForm.action = result.dokumen.process_url;
            distributionForm.elements.pengambilan.value = '';
            distributionLoading.hidden = true;
            distributionForm.hidden = false;
            window.setTimeout(() => distributionForm.elements.pengambilan.focus(), 120);
        } catch (error) {
            distributionLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    const openDistributionModal = (url) => {
        if (!distributionModal || !url) return;
        currentDistributionUrl = url;
        distributionModal.hidden = false;
        distributionModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => distributionModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        loadDistributionAction();
    };

    document.querySelectorAll('[data-open-distribution-action]').forEach((trigger) => {
        trigger.addEventListener('click', () => openDistributionModal(trigger.dataset.actionUrl));
    });

    distributionModal?.querySelectorAll('[data-distribution-close]').forEach((trigger) => trigger.addEventListener('click', closeDistributionModal));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && distributionModal?.classList.contains('open')) closeDistributionModal();
    });

    distributionForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        distributionErrors.hidden = true;
        distributionStatus.textContent = 'Menyimpan penyerahan...';
        distributionSubmit.disabled = true;

        try {
            const response = await fetch(distributionForm.action, {
                method: 'POST',
                body: new FormData(distributionForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);

            if (!response.ok || !result.success) {
                showDistributionErrors(result.message || 'Penyerahan belum dapat disimpan.', result.errors || []);
                return;
            }

            distributionErrors.innerHTML = `<strong>✓ ${escapeHtml(result.message)}</strong>`;
            distributionErrors.classList.add('success');
            distributionErrors.hidden = false;
            distributionStatus.textContent = 'Berhasil diproses';
            window.setTimeout(reloadOperationalPage, 650);
        } catch (error) {
            showDistributionErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            distributionSubmit.disabled = false;
            if (distributionStatus.textContent === 'Menyimpan penyerahan...') distributionStatus.textContent = '';
        }
    });

    const outgoingDistributionModal = document.querySelector('#outgoingDistributionModal');
    const outgoingDistributionForm = outgoingDistributionModal?.querySelector('[data-outgoing-distribution-form]');
    const outgoingDistributionLoading = outgoingDistributionModal?.querySelector('[data-outgoing-distribution-loading]');
    const outgoingDistributionErrors = outgoingDistributionModal?.querySelector('[data-outgoing-distribution-errors]');
    const outgoingDistributionStatus = outgoingDistributionModal?.querySelector('[data-outgoing-distribution-status]');
    const outgoingDistributionSubmit = outgoingDistributionModal?.querySelector('[data-outgoing-distribution-submit]');
    const outgoingHandoverSubmit = outgoingDistributionModal?.querySelector('[data-outgoing-handover-submit]');
    const outgoingHandoverOpen = outgoingDistributionModal?.querySelector('[data-outgoing-handover-open]');
    const outgoingHandoverPanel = outgoingDistributionModal?.querySelector('[data-outgoing-handover-panel]');
    const outgoingHandoverSelect = outgoingDistributionModal?.querySelector('[data-outgoing-handover-select]');
    const outgoingHandoverInfo = outgoingDistributionModal?.querySelector('[data-outgoing-handover-info]');
    const outgoingHandoverCurrent = outgoingDistributionModal?.querySelector('[data-outgoing-handover-current]');
    const outgoingHandoverHistory = outgoingDistributionModal?.querySelector('[data-outgoing-handover-history]');
    const outgoingHandoverHistoryList = outgoingDistributionModal?.querySelector('[data-outgoing-handover-history-list]');
    const outgoingSecurityField = outgoingDistributionModal?.querySelector('[data-outgoing-security-field]');
    const outgoingSecurityLockNote = outgoingDistributionModal?.querySelector('[data-outgoing-security-lock-note]');
    const outgoingDistributionNext = outgoingDistributionModal?.querySelector('[data-outgoing-step-next]');
    const outgoingDistributionBack = outgoingDistributionModal?.querySelector('[data-outgoing-step-back]');
    const outgoingDistributionSteps = outgoingDistributionModal?.querySelectorAll('[data-outgoing-step]') || [];
    const outgoingDistributionIndicators = outgoingDistributionModal?.querySelectorAll('[data-outgoing-step-indicator]') || [];
    let outgoingDistributionCurrentStep = 1;
    let outgoingCanHandover = false;

    const setOutgoingHandoverPanel = (open) => {
        if (!outgoingHandoverPanel || !outgoingHandoverSelect) return;
        outgoingHandoverPanel.hidden = !open;
        outgoingHandoverSelect.disabled = !open;
        outgoingHandoverSelect.required = open;
        if (!open) outgoingHandoverSelect.value = '';
        if (open) {
            outgoingHandoverPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            window.setTimeout(() => outgoingHandoverSelect.focus(), 180);
        }
    };

    const setOutgoingDistributionStep = (step) => {
        outgoingDistributionCurrentStep = step;
        outgoingDistributionSteps.forEach((panel) => { panel.hidden = Number(panel.dataset.outgoingStep) !== step; });
        outgoingDistributionIndicators.forEach((indicator) => {
            indicator.classList.toggle('active', Number(indicator.dataset.outgoingStepIndicator) === step);
        });
        outgoingDistributionBack.hidden = step === 1;
        outgoingDistributionNext.hidden = step !== 1;
        outgoingDistributionSubmit.hidden = step !== 2;
        if (outgoingHandoverOpen) outgoingHandoverOpen.hidden = step !== 2 || !outgoingCanHandover;
        if (step !== 2) setOutgoingHandoverPanel(false);
        outgoingDistributionStatus.textContent = '';
    };

    const closeOutgoingDistribution = () => {
        if (!outgoingDistributionModal) return;
        outgoingDistributionModal.classList.remove('open');
        outgoingDistributionModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => {
            outgoingDistributionModal.hidden = true;
            setOutgoingDistributionStep(1);
        }, 180);
    };

    const showOutgoingDistributionErrors = (message, errors = []) => {
        if (!outgoingDistributionErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        outgoingDistributionErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        outgoingDistributionErrors.hidden = false;
    };

    const openOutgoingDistribution = async (url) => {
        if (!outgoingDistributionModal || !url) return;
        outgoingDistributionLoading.hidden = false;
        outgoingDistributionForm.hidden = true;
        outgoingDistributionErrors.hidden = true;
        outgoingCanHandover = false;
        setOutgoingHandoverPanel(false);
        setOutgoingDistributionStep(1);
        outgoingDistributionModal.hidden = false;
        outgoingDistributionModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => outgoingDistributionModal.classList.add('open'));
        document.body.style.overflow = 'hidden';

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Data Surat Keluar tidak tersedia.');
            Object.entries(result.dokumen).forEach(([field, value]) => {
                outgoingDistributionModal.querySelectorAll(`[data-outgoing-distribution-field="${field}"]`).forEach((element) => { element.value = value; });
            });
            outgoingDistributionForm.action = result.dokumen.process_url;
            outgoingDistributionForm.elements.tanggal_security.value = result.dokumen.tanggal_security_value;
            outgoingDistributionForm.elements.security.value = result.dokumen.security_value;
            outgoingDistributionForm.elements.progres.value = result.dokumen.progres_value;
            outgoingCanHandover = Boolean(result.dokumen.security_value);
            outgoingSecurityField?.classList.toggle('outgoing-security-locked', outgoingCanHandover);
            if (outgoingSecurityLockNote) outgoingSecurityLockNote.hidden = !outgoingCanHandover;
            outgoingDistributionForm.elements.security.tabIndex = outgoingCanHandover ? -1 : 0;
            if (outgoingHandoverInfo && outgoingHandoverCurrent) {
                outgoingHandoverInfo.hidden = !outgoingCanHandover;
                outgoingHandoverCurrent.textContent = result.dokumen.security_value || '';
            }
            const handoverItems = Array.isArray(result.dokumen.serah_terima_history) ? result.dokumen.serah_terima_history : [];
            if (outgoingHandoverHistory && outgoingHandoverHistoryList) {
                outgoingHandoverHistory.hidden = handoverItems.length === 0;
                outgoingHandoverHistoryList.innerHTML = securityHandoverItemsHtml(handoverItems);
            }
            outgoingDistributionLoading.hidden = true;
            outgoingDistributionForm.hidden = false;
        } catch (error) {
            outgoingDistributionLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    document.querySelectorAll('[data-open-outgoing-distribution]').forEach((button) => button.addEventListener('click', () => openOutgoingDistribution(button.dataset.actionUrl)));
    outgoingDistributionModal?.querySelectorAll('[data-outgoing-distribution-close]').forEach((button) => button.addEventListener('click', closeOutgoingDistribution));
    outgoingDistributionNext?.addEventListener('click', () => {
        outgoingDistributionErrors.hidden = true;
        setOutgoingDistributionStep(2);
        window.setTimeout(() => outgoingDistributionForm.elements.security.focus(), 120);
    });
    outgoingDistributionBack?.addEventListener('click', () => {
        outgoingDistributionErrors.hidden = true;
        setOutgoingDistributionStep(1);
    });
    outgoingHandoverOpen?.addEventListener('click', () => setOutgoingHandoverPanel(true));
    outgoingDistributionModal?.querySelector('[data-outgoing-handover-cancel]')?.addEventListener('click', () => setOutgoingHandoverPanel(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && outgoingDistributionModal?.classList.contains('open')) closeOutgoingDistribution();
    });

    outgoingDistributionForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const outgoingAction = event.submitter?.dataset.outgoingAction || 'save';
        if (outgoingDistributionCurrentStep !== 2) {
            setOutgoingDistributionStep(2);
            window.setTimeout(() => outgoingDistributionForm.elements.security.focus(), 120);
            return;
        }
        outgoingDistributionErrors.hidden = true;
        outgoingDistributionStatus.textContent = outgoingAction === 'handover' ? 'Mencatat serah terima...' : 'Menyimpan distribusi...';
        outgoingDistributionSubmit.disabled = true;
        if (outgoingHandoverSubmit) outgoingHandoverSubmit.disabled = true;
        try {
            const outgoingFormData = new FormData(outgoingDistributionForm);
            outgoingFormData.set('submit_action', outgoingAction);
            const response = await fetch(outgoingDistributionForm.action, {
                method: 'POST', body: outgoingFormData, credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) {
                showOutgoingDistributionErrors(result.message || 'Distribusi belum dapat disimpan.', result.errors || []);
                return;
            }
            outgoingDistributionStatus.textContent = 'Berhasil disimpan';
            window.setTimeout(reloadOperationalPage, 500);
        } catch (error) {
            showOutgoingDistributionErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            outgoingDistributionSubmit.disabled = false;
            if (outgoingHandoverSubmit) outgoingHandoverSubmit.disabled = false;
            if (['Menyimpan distribusi...', 'Mencatat serah terima...'].includes(outgoingDistributionStatus.textContent)) outgoingDistributionStatus.textContent = '';
        }
    });

    const agendaFormModal = document.querySelector('#agendarisFormModal');
    const agendaForm = agendaFormModal?.querySelector('[data-agendaris-form]');
    const agendaFormTitle = agendaFormModal?.querySelector('[data-agendaris-form-title]');
    const agendaFormNote = agendaFormModal?.querySelector('[data-agendaris-form-note]');
    const agendaErrors = agendaFormModal?.querySelector('[data-agendaris-errors]');
    const agendaStatus = agendaFormModal?.querySelector('[data-agendaris-status]');
    const agendaSubmit = agendaFormModal?.querySelector('[data-agendaris-submit]');
    const agendaStepNext = agendaFormModal?.querySelector('[data-agendaris-step-next]');
    const agendaStepBack = agendaFormModal?.querySelector('[data-agendaris-step-back]');
    const agendaStepPanels = agendaFormModal?.querySelectorAll('[data-agendaris-step]') || [];
    const agendaStepIndicators = agendaFormModal?.querySelectorAll('[data-agendaris-step-indicator]') || [];
    const agendaLink = agendaFormModal?.querySelector('[data-agendaris-link]');
    const agendaLinkInput = agendaForm?.elements.berkas_link;
    const agendaGenerateButton = agendaFormModal?.querySelector('[data-agendaris-generate]');
    const agendaDownloadSheetButton = agendaFormModal?.querySelector('[data-agendaris-download-sheet]');
    const agendaNumberInput = agendaForm?.elements.nomor_agendaris;
    const agendaCreateUrl = agendaForm?.action || '';
    // Hanya Pengirim yang boleh diperbarui oleh Agendaris. Field sumber
    // Security lainnya tetap dikunci.
    const agendaSourceFieldNames = ['tanggal_diterima', 'penerima', 'pengambilan', 'jenis'];
    const agendaDispositionStages = agendaFormModal?.querySelectorAll('[data-disposition-form-stage]') || [];
    const agendaEditHistoryButton = agendaFormModal?.querySelector('[data-agendaris-edit-history-open]');
    const agendaEditHistoryModal = document.querySelector('#agendarisDispositionHistoryModal');
    const agendaEditHistory = agendaEditHistoryModal?.querySelector('[data-agendaris-edit-history]');
    const agendaLastStep = agendaStepPanels.length || 1;
    let agendaCurrentStep = 1;
    let agendaEditHistoryReturnFocus = null;

    const currentDateLocal = () => {
        const now = new Date();
        const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        return local.toISOString().slice(0, 10);
    };

    const updateDispositionFormState = (stage) => {
        const step = stage.dataset.dispositionFormStage;
        const recipient = stage.querySelector(`[data-disposition-recipient="${step}"]`);
        const status = stage.querySelector(`[data-disposition-status="${step}"]`);
        const time = stage.querySelector(`[data-disposition-time="${step}"]`);
        const note = stage.querySelector(`[name="disposisi_${step}_catatan"]`);
        const state = stage.querySelector(`[data-disposition-state="${step}"]`);
        const filled = Boolean(recipient?.value.trim());

        if (filled && time && !time.value) time.value = currentDateLocal();
        [status, time, note].forEach((field) => { if (field) field.disabled = !filled; });
        if (state) {
            state.textContent = filled ? (status?.value || 'Menunggu') : 'Belum ditentukan';
            state.classList.toggle('filled', filled);
        }
    };

    const refreshDispositionForm = () => agendaDispositionStages.forEach(updateDispositionFormState);

    const clearLegacyDispositionOptions = () => {
        agendaDispositionStages.forEach((stage) => {
            stage.querySelectorAll('[data-disposition-recipient] option[data-legacy]').forEach((option) => option.remove());
        });
    };

    const setDispositionRecipientValue = (field, value) => {
        if (!field) return;
        const recipient = value || '';
        field.querySelectorAll('option[data-legacy]').forEach((option) => option.remove());
        if (recipient && !Array.from(field.options).some((option) => option.value === recipient)) {
            const legacyOption = new Option(`${recipient} (data lama)`, recipient, true, true);
            legacyOption.dataset.legacy = 'true';
            field.add(legacyOption);
        }
        field.value = recipient;
    };

    const renderAgendaEditHistory = (timeline = []) => {
        if (!agendaEditHistory || !agendaEditHistoryButton) return;
        const filled = timeline.filter((item) => item.terisi);
        agendaEditHistoryButton.hidden = filled.length === 0;
        agendaEditHistoryButton.setAttribute('aria-expanded', 'false');
        agendaEditHistory.innerHTML = filled.map((item) => {
            const status = item.status || 'Menunggu';
            const statusClass = ({ Menunggu:'pending', Diterima:'received', Diproses:'active', Diteruskan:'forwarded', Selesai:'completed' }[status] || 'empty');
            return `<article class="disposition-detail-item filled">
                <span class="disposition-detail-dot">${String(item.urutan).padStart(2, '0')}</span>
                <div class="disposition-detail-card">
                    <header><div><h3>${escapeHtml(item.penerima)}</h3><time>${escapeHtml(item.waktu)}</time></div><span class="disposition-status-badge ${statusClass}">${escapeHtml(status)}</span></header>
                    <p>${escapeHtml(item.catatan)}</p>
                </div>
            </article>`;
        }).join('');
    };

    const closeAgendaEditHistory = () => {
        if (!agendaEditHistoryModal || agendaEditHistoryModal.hidden) return;
        agendaEditHistoryModal.classList.remove('open');
        agendaEditHistoryModal.setAttribute('aria-hidden', 'true');
        agendaEditHistoryButton?.setAttribute('aria-expanded', 'false');
        window.setTimeout(() => {
            agendaEditHistoryModal.hidden = true;
            agendaEditHistoryReturnFocus?.focus();
            agendaEditHistoryReturnFocus = null;
        }, 180);
    };

    const openAgendaEditHistory = () => {
        if (!agendaEditHistoryModal || !agendaEditHistoryButton || agendaEditHistoryButton.hidden) return;
        agendaEditHistoryReturnFocus = document.activeElement;
        agendaEditHistoryModal.hidden = false;
        agendaEditHistoryModal.setAttribute('aria-hidden', 'false');
        agendaEditHistoryButton.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(() => agendaEditHistoryModal.classList.add('open'));
        window.setTimeout(() => agendaEditHistoryModal.querySelector('.modal-close')?.focus(), 120);
    };

    agendaDispositionStages.forEach((stage) => {
        const step = stage.dataset.dispositionFormStage;
        stage.querySelector(`[data-disposition-recipient="${step}"]`)?.addEventListener('change', () => updateDispositionFormState(stage));
        stage.querySelector(`[data-disposition-status="${step}"]`)?.addEventListener('change', () => updateDispositionFormState(stage));
    });

    const setAgendaStep = (step) => {
        agendaCurrentStep = step;
        agendaStepPanels.forEach((panel) => { panel.hidden = Number(panel.dataset.agendarisStep) !== step; });
        agendaStepIndicators.forEach((indicator) => {
            indicator.classList.toggle('active', Number(indicator.dataset.agendarisStepIndicator) === step);
        });
        if (agendaStepBack) agendaStepBack.hidden = step === 1;
        if (agendaStepNext) agendaStepNext.hidden = step >= agendaLastStep;
        if (agendaSubmit) agendaSubmit.hidden = step !== agendaLastStep;
        if (agendaStatus) agendaStatus.textContent = '';
    };

    const validateAgendaStep = (step) => {
        const panel = Array.from(agendaStepPanels).find((item) => Number(item.dataset.agendarisStep) === step);
        if (!panel) return true;
        const fields = panel.querySelectorAll('input, select, textarea');
        for (const field of fields) {
            if (!field.checkValidity()) {
                field.reportValidity();
                field.focus();
                return false;
            }
        }
        return true;
    };

    const setAgendaSourceLock = (locked) => {
        agendaSourceFieldNames.forEach((name) => {
            const field = agendaForm?.elements[name];
            if (!field) return;
            field.readOnly = locked;
            field.setAttribute('aria-readonly', locked ? 'true' : 'false');
            field.closest('.form-group')?.classList.toggle('field-source-locked', locked);
        });
        if (agendaFormNote) {
            agendaFormNote.textContent = locked
                ? 'Field bertanda kunci berasal dari Security dan tidak dapat diubah'
                : 'Lengkapi seluruh informasi Surat Masuk';
        }
    };

    const setAgendaLink = (visible, value = '') => {
        if (agendaLink) agendaLink.hidden = !visible;
        if (agendaLinkInput) agendaLinkInput.value = visible ? value : '';
    };

    const setAgendaNumberState = (hasNumber, canCancel = false) => {
        if (!agendaGenerateButton) return;
        agendaGenerateButton.disabled = hasNumber && !canCancel;
        agendaGenerateButton.textContent = hasNumber ? 'Nomor sudah dibuat' : 'Generate Nomor';
        agendaGenerateButton.dataset.canCancel = canCancel ? 'true' : 'false';
        agendaGenerateButton.title = canCancel ? 'Klik lagi untuk membatalkan Nomor Agendaris' : '';
    };

    const closeAgendaForm = () => {
        if (!agendaFormModal) return;
        closeAgendaEditHistory();
        agendaFormModal.classList.remove('open');
        agendaFormModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => {
            agendaFormModal.hidden = true;
            setAgendaStep(1);
        }, 180);
    };

    const showAgendaErrors = (message, errors = []) => {
        if (!agendaErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        agendaErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        agendaErrors.hidden = false;
    };

    const showAgendaForm = () => {
        agendaFormModal.hidden = false;
        agendaFormModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => agendaFormModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };

    const openAgendaCreate = () => {
        if (!agendaForm || !agendaFormModal) return;
        clearLegacyDispositionOptions();
        agendaForm.reset();
        agendaForm.action = agendaCreateUrl;
        agendaFormTitle.textContent = 'Tambah Surat Masuk';
        agendaSubmit.textContent = 'Simpan Surat Masuk';
        setAgendaStep(1);
        setAgendaSourceLock(false);
        setAgendaLink(true);
        setAgendaNumberState(false);
        renderAgendaEditHistory([]);
        refreshDispositionForm();
        agendaErrors.hidden = true;
        agendaStatus.textContent = '';
        showAgendaForm();
        window.setTimeout(() => agendaForm.elements.pengirim?.focus(), 120);
    };

    const loadAgenda = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Data Surat Masuk tidak tersedia.');
        return result.agenda;
    };

    const openAgendaEdit = async (url) => {
        if (!agendaForm || !agendaFormModal || !url) return;
        clearLegacyDispositionOptions();
        agendaForm.reset();
        setAgendaStep(1);
        setAgendaSourceLock(false);
        setAgendaLink(false);
        agendaErrors.hidden = true;
        agendaFormTitle.textContent = 'Edit Surat Masuk';
        agendaSubmit.textContent = 'Simpan perubahan';
        agendaStatus.textContent = 'Memuat data...';
        renderAgendaEditHistory([]);
        showAgendaForm();

        try {
            const data = await loadAgenda(url);
            agendaForm.action = data.update_url;
            agendaForm.elements.pengirim.value = data.pengirim;
            agendaForm.elements.penerima.value = data.penerima_value;
            agendaForm.elements.pengambilan.value = data.pengambilan_value;
            agendaForm.elements.jenis.value = data.jenis_value;
            agendaForm.elements.tanggal_diterima.value = data.tanggal_value;
            agendaForm.elements.tanggal_surat.value = data.tanggal_surat_value;
            agendaForm.elements.nomor_surat.value = data.nomor_surat_value;
            agendaForm.elements.nomor_agendaris.value = data.nomor_agendaris_value;
            agendaForm.elements.tanggal_agendaris.value = data.tanggal_agendaris_value;
            agendaForm.elements.perihal_surat.value = data.perihal_surat;
            for (let step = 1; step <= agendaDispositionStages.length; step += 1) {
                setDispositionRecipientValue(agendaForm.elements[`disposisi_${step}`], data[`disposisi_${step}_value`]);
                agendaForm.elements[`disposisi_${step}_status`].value = data[`disposisi_${step}_status_value`] || 'Menunggu';
                agendaForm.elements[`disposisi_${step}_waktu`].value = data[`disposisi_${step}_waktu_value`] || '';
                agendaForm.elements[`disposisi_${step}_catatan`].value = data[`disposisi_${step}_catatan_value`] || '';
            }
            agendaForm.elements.progres.value = data.progres || 'Menunggu Penyelesaian';
            renderAgendaEditHistory(data.disposisi_timeline || []);
            setAgendaSourceLock(Boolean(data.source_locked));
            setAgendaLink(true, data.berkas_link || '');
            setAgendaNumberState(Boolean(data.nomor_agendaris_value));
            refreshDispositionForm();
            agendaStatus.textContent = '';
        } catch (error) {
            showAgendaErrors(error.message);
            agendaStatus.textContent = '';
        }
    };

    document.querySelector('[data-agendaris-add]')?.addEventListener('click', openAgendaCreate);
    document.querySelectorAll('[data-agendaris-edit]').forEach((button) => button.addEventListener('click', () => openAgendaEdit(button.dataset.agendarisUrl)));
    agendaFormModal?.querySelectorAll('[data-agendaris-form-close]').forEach((button) => button.addEventListener('click', closeAgendaForm));
    agendaEditHistoryButton?.addEventListener('click', openAgendaEditHistory);
    agendaEditHistoryModal?.querySelectorAll('[data-agendaris-edit-history-close]').forEach((button) => button.addEventListener('click', closeAgendaEditHistory));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && agendaEditHistoryModal?.classList.contains('open')) {
            event.stopImmediatePropagation();
            closeAgendaEditHistory();
        }
    });
    agendaStepNext?.addEventListener('click', () => {
        agendaErrors.hidden = true;
        if (!validateAgendaStep(agendaCurrentStep)) return;
        const nextStep = Math.min(agendaCurrentStep + 1, agendaLastStep);
        setAgendaStep(nextStep);
        window.setTimeout(() => {
            if (nextStep === 2 && agendaGenerateButton && !agendaGenerateButton.disabled) agendaGenerateButton.focus();
            else if (nextStep === 2) agendaForm?.elements.tanggal_agendaris?.focus();
            else if (nextStep === 3) agendaForm?.elements.disposisi_1?.focus();
            else agendaForm?.elements.progres?.focus();
        }, 120);
    });
    agendaStepBack?.addEventListener('click', () => {
        agendaErrors.hidden = true;
        const previousStep = Math.max(agendaCurrentStep - 1, 1);
        setAgendaStep(previousStep);
        window.setTimeout(() => {
            if (previousStep === 1) agendaForm?.elements.pengirim?.focus();
            else if (previousStep === 2) agendaForm?.elements.nomor_surat?.focus();
            else agendaForm?.elements.disposisi_1?.focus();
        }, 120);
    });

    agendaGenerateButton?.addEventListener('click', async () => {
        const url = agendaGenerateButton.dataset.generateUrl;
        if (!url || !agendaNumberInput) return;

        if (agendaGenerateButton.dataset.canCancel === 'true' && agendaNumberInput.value) {
            agendaNumberInput.value = '';
            setAgendaNumberState(false);
            if (agendaStatus) agendaStatus.textContent = 'Nomor Agendaris dibatalkan dan belum disimpan.';
            return;
        }

        agendaGenerateButton.disabled = true;
        agendaGenerateButton.textContent = 'Membuat...';
        agendaErrors.hidden = true;

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) throw new Error(result.message || 'Nomor Agendaris belum dapat dibuat.');
            agendaNumberInput.value = result.nomor_agendaris;
            setAgendaNumberState(true, true);
        } catch (error) {
            showAgendaErrors(error.message);
            setAgendaNumberState(false);
        }
    });

    agendaDownloadSheetButton?.addEventListener('click', async () => {
        const endpoint = agendaDownloadSheetButton.dataset.downloadUrl;
        if (!endpoint || !agendaForm) return;

        const url = new URL(endpoint, window.location.href);
        url.protocol = window.location.protocol;
        url.host = window.location.host;

        agendaErrors.hidden = true;
        agendaStatus.textContent = 'Membuat Lembar Pengendalian...';
        agendaDownloadSheetButton.disabled = true;

        try {
            const response = await fetch(url.toString(), {
                method: 'POST',
                body: new FormData(agendaForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/pdf, application/json' },
            });
            const csrfName = response.headers.get('X-CSRF-Name');
            const csrfHash = response.headers.get('X-CSRF-Hash');
            updateCsrf({ name: csrfName, hash: csrfHash });

            if (!response.ok) {
                const contentType = response.headers.get('Content-Type') || '';
                const result = contentType.includes('application/json') ? await response.json() : null;
                updateCsrf(result?.csrf);
                throw new Error(result?.message || `Lembar Pengendalian belum dapat dibuat (HTTP ${response.status}).`);
            }

            const contentType = response.headers.get('Content-Type') || '';
            if (!contentType.includes('application/pdf')) {
                throw new Error('Respons Lembar Pengendalian bukan berupa PDF. Silakan masuk ulang lalu coba kembali.');
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const disposition = response.headers.get('Content-Disposition') || '';
            const filename = disposition.match(/filename="?([^";]+)"?/i)?.[1] || 'Lembar-Pengendalian-Surat-Masuk.pdf';
            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
            agendaStatus.textContent = 'Lembar Pengendalian berhasil diunduh';
        } catch (error) {
            showAgendaErrors(error.message || 'Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            agendaDownloadSheetButton.disabled = false;
            if (agendaStatus.textContent === 'Membuat Lembar Pengendalian...') agendaStatus.textContent = '';
        }
    });

    agendaForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (agendaCurrentStep !== agendaLastStep) {
            if (!validateAgendaStep(agendaCurrentStep)) return;
            setAgendaStep(Math.min(agendaCurrentStep + 1, agendaLastStep));
            return;
        }
        agendaErrors.hidden = true;
        agendaStatus.textContent = 'Menyimpan data...';
        agendaSubmit.disabled = true;

        try {
            const response = await fetch(agendaForm.action, {
                method: 'POST',
                body: new FormData(agendaForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) {
                showAgendaErrors(result.message || 'Data belum dapat disimpan.', result.errors || []);
                return;
            }
            agendaStatus.textContent = 'Berhasil disimpan';
            window.setTimeout(reloadOperationalPage, 500);
        } catch (error) {
            showAgendaErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            agendaSubmit.disabled = false;
            if (agendaStatus.textContent === 'Menyimpan data...') agendaStatus.textContent = '';
        }
    });

    const agendaDetailModal = document.querySelector('#agendarisDetailModal');
    const agendaDetailLoading = agendaDetailModal?.querySelector('[data-agendaris-detail-loading]');
    const agendaDetailContent = agendaDetailModal?.querySelector('[data-agendaris-detail-content]');
    const agendaDetailEdit = agendaDetailModal?.querySelector('[data-agendaris-detail-edit]');
    const agendaDetailLink = agendaDetailModal?.querySelector('[data-agendaris-detail-link]');
    const agendaDetailNoLink = agendaDetailModal?.querySelector('[data-agendaris-detail-no-link]');
    const agendaDispositionTimeline = agendaDetailModal?.querySelector('[data-agendaris-disposition-timeline]');
    let currentAgendaUrl = '';

    const dispositionStatusClass = (status) => ({
        'Belum ditentukan': 'empty',
        Menunggu: 'pending',
        Diterima: 'received',
        Diproses: 'active',
        Diteruskan: 'forwarded',
        Selesai: 'completed',
    }[status] || 'empty');

    const renderDispositionTimeline = (timeline = []) => {
        if (!agendaDispositionTimeline) return;
        agendaDispositionTimeline.innerHTML = timeline
            .filter((item) => Number(item.urutan) !== 5 || item.terisi)
            .map((item) => `
            <article class="disposition-detail-item${item.terisi ? ' filled' : ''}">
                <span class="disposition-detail-dot">${String(item.urutan).padStart(2, '0')}</span>
                <div class="disposition-detail-card">
                    <header>
                        <div><h3>${escapeHtml(item.penerima)}</h3><time>${escapeHtml(item.waktu)}</time></div>
                        <span class="disposition-status-badge ${dispositionStatusClass(item.status)}">${escapeHtml(item.status)}</span>
                    </header>
                    <p>${escapeHtml(item.catatan)}</p>
                </div>
            </article>
        `).join('');
    };

    const closeAgendaDetail = () => {
        if (!agendaDetailModal) return;
        agendaDetailModal.classList.remove('open');
        agendaDetailModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { agendaDetailModal.hidden = true; }, 180);
    };

    const openAgendaDetail = async (url) => {
        if (!agendaDetailModal || !url) return;
        currentAgendaUrl = url;
        agendaDetailLoading.hidden = false;
        agendaDetailContent.hidden = true;
        agendaDetailModal.hidden = false;
        agendaDetailModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => agendaDetailModal.classList.add('open'));
        document.body.style.overflow = 'hidden';

        try {
            const data = await loadAgenda(url);
            Object.entries(data).forEach(([field, value]) => {
                agendaDetailModal.querySelectorAll(`[data-agendaris-field="${field}"]`).forEach((element) => { element.textContent = value; });
            });
            const penyerahanTime = agendaDetailModal.querySelector('[data-agendaris-penyerahan-time]');
            if (penyerahanTime) penyerahanTime.hidden = !data.penyerahan_at;
            const hasLink = Boolean(data.berkas_link);
            if (agendaDetailLink) {
                agendaDetailLink.hidden = !hasLink;
                agendaDetailLink.href = hasLink ? data.berkas_link : '#';
            }
            if (agendaDetailNoLink) agendaDetailNoLink.hidden = hasLink;
            renderDispositionTimeline(data.disposisi_timeline || []);
            agendaDetailLoading.hidden = true;
            agendaDetailContent.hidden = false;
        } catch (error) {
            agendaDetailLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    document.querySelectorAll('[data-agendaris-view]').forEach((button) => button.addEventListener('click', () => openAgendaDetail(button.dataset.agendarisUrl)));
    agendaDetailModal?.querySelectorAll('[data-agendaris-detail-close]').forEach((button) => button.addEventListener('click', closeAgendaDetail));
    agendaDetailEdit?.addEventListener('click', () => {
        closeAgendaDetail();
        openAgendaEdit(currentAgendaUrl);
    });

    const agendaDeleteModal = document.querySelector('#agendarisDeleteModal');
    const agendaDeleteForm = agendaDeleteModal?.querySelector('[data-agendaris-delete-form]');
    const agendaDeleteLabel = agendaDeleteModal?.querySelector('[data-agendaris-delete-label]');
    const agendaDeleteError = agendaDeleteModal?.querySelector('[data-agendaris-delete-error]');
    const agendaDeleteSubmit = agendaDeleteModal?.querySelector('[data-agendaris-delete-submit]');

    const closeAgendaDelete = () => {
        if (!agendaDeleteModal) return;
        agendaDeleteModal.classList.remove('open');
        agendaDeleteModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { agendaDeleteModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-agendaris-delete]').forEach((button) => button.addEventListener('click', () => {
        agendaDeleteForm.action = button.dataset.deleteUrl;
        agendaDeleteLabel.textContent = button.dataset.deleteLabel;
        agendaDeleteError.hidden = true;
        agendaDeleteModal.hidden = false;
        agendaDeleteModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => agendaDeleteModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    }));
    agendaDeleteModal?.querySelectorAll('[data-agendaris-delete-close]').forEach((button) => button.addEventListener('click', closeAgendaDelete));

    agendaDeleteForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        agendaDeleteError.hidden = true;
        agendaDeleteSubmit.disabled = true;
        try {
            const response = await fetch(agendaDeleteForm.action, {
                method: 'POST', body: new FormData(agendaDeleteForm), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) throw new Error(result.message || 'Data gagal dihapus.');
            window.setTimeout(reloadOperationalPage, 250);
        } catch (error) {
            agendaDeleteError.textContent = error.message;
            agendaDeleteError.hidden = false;
        } finally {
            agendaDeleteSubmit.disabled = false;
        }
    });

    const dokumenKeluarFormModal = document.querySelector('#dokumenKeluarFormModal');
    const dokumenKeluarForm = dokumenKeluarFormModal?.querySelector('[data-dokumen-keluar-form]');
    const dokumenKeluarFormTitle = dokumenKeluarFormModal?.querySelector('[data-dokumen-keluar-form-title]');
    const dokumenKeluarErrors = dokumenKeluarFormModal?.querySelector('[data-dokumen-keluar-errors]');
    const dokumenKeluarStatus = dokumenKeluarFormModal?.querySelector('[data-dokumen-keluar-status]');
    const dokumenKeluarSubmit = dokumenKeluarFormModal?.querySelector('[data-dokumen-keluar-submit]');
    const dokumenKeluarCreateUrl = dokumenKeluarForm?.action || '';

    const closeDokumenKeluarForm = () => {
        if (!dokumenKeluarFormModal) return;
        dokumenKeluarFormModal.classList.remove('open');
        dokumenKeluarFormModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { dokumenKeluarFormModal.hidden = true; }, 180);
    };

    const showDokumenKeluarErrors = (message, errors = []) => {
        if (!dokumenKeluarErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        dokumenKeluarErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        dokumenKeluarErrors.hidden = false;
    };

    const showDokumenKeluarForm = () => {
        if (!dokumenKeluarFormModal) return;
        dokumenKeluarFormModal.hidden = false;
        dokumenKeluarFormModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => dokumenKeluarFormModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };

    const loadDokumenKeluar = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Data Surat Keluar tidak tersedia.');
        return result.dokumen;
    };

    const openDokumenKeluarCreate = () => {
        if (!dokumenKeluarForm) return;
        dokumenKeluarForm.reset();
        setupEkspedisiSelector(dokumenKeluarForm)?.setValue('');
        dokumenKeluarForm.action = dokumenKeluarCreateUrl;
        dokumenKeluarFormTitle.textContent = 'Tambah Surat Keluar';
        dokumenKeluarSubmit.textContent = 'Simpan Surat Keluar';
        dokumenKeluarErrors.hidden = true;
        dokumenKeluarStatus.textContent = '';
        showDokumenKeluarForm();
        window.setTimeout(() => dokumenKeluarForm.elements.nomor_surat?.focus(), 120);
    };

    const openDokumenKeluarEdit = async (url) => {
        if (!dokumenKeluarForm || !url) return;
        dokumenKeluarForm.reset();
        dokumenKeluarErrors.hidden = true;
        dokumenKeluarFormTitle.textContent = 'Edit Surat Keluar';
        dokumenKeluarSubmit.textContent = 'Simpan perubahan';
        dokumenKeluarStatus.textContent = 'Memuat data...';
        showDokumenKeluarForm();

        try {
            const data = await loadDokumenKeluar(url);
            dokumenKeluarForm.action = data.update_url;
            dokumenKeluarForm.elements.nomor_surat.value = data.nomor_surat;
            dokumenKeluarForm.elements.jenis_surat.value = data.jenis_surat;
            dokumenKeluarForm.elements.jumlah_dokumen.value = data.jumlah_dokumen_value;
            setupEkspedisiSelector(dokumenKeluarForm)?.setValue(data.nama_ekspedisi_value);
            dokumenKeluarForm.elements.pemohon.value = data.pemohon_value;
            dokumenKeluarForm.elements.pelaksana.value = data.pelaksana_value;
            dokumenKeluarForm.elements.up.value = data.up_value;
            dokumenKeluarForm.elements.tanggal_pengiriman.value = data.tanggal_pengiriman_value;
            dokumenKeluarForm.elements.alamat_penerima.value = data.alamat_penerima;
            dokumenKeluarForm.elements.dokumen_link.value = data.dokumen_link_value;
            dokumenKeluarStatus.textContent = '';
        } catch (error) {
            showDokumenKeluarErrors(error.message);
            dokumenKeluarStatus.textContent = '';
        }
    };

    document.querySelector('[data-dokumen-keluar-add]')?.addEventListener('click', openDokumenKeluarCreate);
    document.querySelectorAll('[data-dokumen-keluar-edit]').forEach((button) => button.addEventListener('click', () => openDokumenKeluarEdit(button.dataset.dokumenKeluarUrl)));
    dokumenKeluarFormModal?.querySelectorAll('[data-dokumen-keluar-form-close]').forEach((button) => button.addEventListener('click', closeDokumenKeluarForm));

    dokumenKeluarForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        dokumenKeluarErrors.hidden = true;
        dokumenKeluarStatus.textContent = 'Menyimpan data...';
        dokumenKeluarSubmit.disabled = true;
        try {
            const response = await fetch(dokumenKeluarForm.action, {
                method: 'POST', body: new FormData(dokumenKeluarForm), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) {
                showDokumenKeluarErrors(result.message || 'Data belum dapat disimpan.', result.errors || []);
                return;
            }
            dokumenKeluarStatus.textContent = 'Berhasil disimpan';
            window.setTimeout(reloadOperationalPage, 500);
        } catch (error) {
            showDokumenKeluarErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            dokumenKeluarSubmit.disabled = false;
            if (dokumenKeluarStatus.textContent === 'Menyimpan data...') dokumenKeluarStatus.textContent = '';
        }
    });

    const dokumenKeluarDetailModal = document.querySelector('#dokumenKeluarDetailModal');
    const dokumenKeluarDetailLoading = dokumenKeluarDetailModal?.querySelector('[data-dokumen-keluar-detail-loading]');
    const dokumenKeluarDetailContent = dokumenKeluarDetailModal?.querySelector('[data-dokumen-keluar-detail-content]');
    const dokumenKeluarDetailEdit = dokumenKeluarDetailModal?.querySelector('[data-dokumen-keluar-detail-edit]');
    const dokumenKeluarHandoverHistory = dokumenKeluarDetailModal?.querySelector('[data-dokumen-keluar-handover-history]');
    const dokumenKeluarHandoverHistoryList = dokumenKeluarDetailModal?.querySelector('[data-dokumen-keluar-handover-history-list]');
    let currentDokumenKeluarUrl = '';

    const closeDokumenKeluarDetail = () => {
        if (!dokumenKeluarDetailModal) return;
        dokumenKeluarDetailModal.classList.remove('open');
        dokumenKeluarDetailModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { dokumenKeluarDetailModal.hidden = true; }, 180);
    };

    const openDokumenKeluarDetail = async (url) => {
        if (!dokumenKeluarDetailModal || !url) return;
        currentDokumenKeluarUrl = url;
        dokumenKeluarDetailLoading.hidden = false;
        dokumenKeluarDetailContent.hidden = true;
        dokumenKeluarDetailModal.hidden = false;
        dokumenKeluarDetailModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => dokumenKeluarDetailModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        try {
            const data = await loadDokumenKeluar(url);
            Object.entries(data).forEach(([field, value]) => {
                dokumenKeluarDetailModal.querySelectorAll(`[data-dokumen-keluar-field="${field}"]`).forEach((element) => { element.textContent = value; });
            });
            const documentLink = dokumenKeluarDetailModal.querySelector('[data-dokumen-keluar-document-link]');
            const documentEmpty = dokumenKeluarDetailModal.querySelector('[data-dokumen-keluar-document-empty]');
            const hasDocumentLink = Boolean(data.dokumen_link);
            if (documentLink) {
                documentLink.hidden = !hasDocumentLink;
                documentLink.href = hasDocumentLink ? data.dokumen_link : '#';
            }
            if (documentEmpty) documentEmpty.hidden = hasDocumentLink;
            const handoverItems = Array.isArray(data.serah_terima_history) ? data.serah_terima_history : [];
            if (dokumenKeluarHandoverHistory && dokumenKeluarHandoverHistoryList) {
                dokumenKeluarHandoverHistory.hidden = handoverItems.length === 0;
                dokumenKeluarHandoverHistoryList.innerHTML = securityHandoverItemsHtml(handoverItems);
            }
            dokumenKeluarDetailLoading.hidden = true;
            dokumenKeluarDetailContent.hidden = false;
        } catch (error) {
            dokumenKeluarDetailLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    document.querySelectorAll('[data-dokumen-keluar-view]').forEach((button) => button.addEventListener('click', () => openDokumenKeluarDetail(button.dataset.dokumenKeluarUrl)));
    dokumenKeluarDetailModal?.querySelectorAll('[data-dokumen-keluar-detail-close]').forEach((button) => button.addEventListener('click', closeDokumenKeluarDetail));
    dokumenKeluarDetailEdit?.addEventListener('click', () => { closeDokumenKeluarDetail(); openDokumenKeluarEdit(currentDokumenKeluarUrl); });

    const dokumenKeluarDeleteModal = document.querySelector('#dokumenKeluarDeleteModal');
    const dokumenKeluarDeleteForm = dokumenKeluarDeleteModal?.querySelector('[data-dokumen-keluar-delete-form]');
    const dokumenKeluarDeleteLabel = dokumenKeluarDeleteModal?.querySelector('[data-dokumen-keluar-delete-label]');
    const dokumenKeluarDeleteError = dokumenKeluarDeleteModal?.querySelector('[data-dokumen-keluar-delete-error]');
    const dokumenKeluarDeleteSubmit = dokumenKeluarDeleteModal?.querySelector('[data-dokumen-keluar-delete-submit]');

    const closeDokumenKeluarDelete = () => {
        if (!dokumenKeluarDeleteModal) return;
        dokumenKeluarDeleteModal.classList.remove('open');
        dokumenKeluarDeleteModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { dokumenKeluarDeleteModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-dokumen-keluar-delete]').forEach((button) => button.addEventListener('click', () => {
        dokumenKeluarDeleteForm.action = button.dataset.deleteUrl;
        dokumenKeluarDeleteLabel.textContent = button.dataset.deleteLabel;
        dokumenKeluarDeleteError.hidden = true;
        dokumenKeluarDeleteModal.hidden = false;
        dokumenKeluarDeleteModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => dokumenKeluarDeleteModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    }));
    dokumenKeluarDeleteModal?.querySelectorAll('[data-dokumen-keluar-delete-close]').forEach((button) => button.addEventListener('click', closeDokumenKeluarDelete));

    dokumenKeluarDeleteForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        dokumenKeluarDeleteError.hidden = true;
        dokumenKeluarDeleteSubmit.disabled = true;
        try {
            const response = await fetch(dokumenKeluarDeleteForm.action, {
                method: 'POST', body: new FormData(dokumenKeluarDeleteForm), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) throw new Error(result.message || 'Data gagal dihapus.');
            window.setTimeout(reloadOperationalPage, 250);
        } catch (error) {
            dokumenKeluarDeleteError.textContent = error.message;
            dokumenKeluarDeleteError.hidden = false;
        } finally {
            dokumenKeluarDeleteSubmit.disabled = false;
        }
    });

    const progressFormModal = document.querySelector('#progressDocumentFormModal');
    const progressForm = progressFormModal?.querySelector('[data-progress-form]');
    const progressFormTitle = progressFormModal?.querySelector('[data-progress-form-title]');
    const progressErrors = progressFormModal?.querySelector('[data-progress-errors]');
    const progressStatus = progressFormModal?.querySelector('[data-progress-status]');
    const progressSubmit = progressFormModal?.querySelector('[data-progress-submit]');
    const progressCreateUrl = progressForm?.action || '';
    const progressDetailModal = document.querySelector('#progressDocumentDetailModal');
    const progressDetailLoading = progressDetailModal?.querySelector('[data-progress-detail-loading]');
    const progressDetailContent = progressDetailModal?.querySelector('[data-progress-detail-content]');
    const progressDetailEdit = progressDetailModal?.querySelector('[data-progress-detail-edit]');
    const progressDocumentLink = progressDetailModal?.querySelector('[data-progress-document-link]');
    const progressDocumentEmpty = progressDetailModal?.querySelector('[data-progress-document-empty]');
    const progressDeleteModal = document.querySelector('#progressDocumentDeleteModal');
    const progressDeleteForm = progressDeleteModal?.querySelector('[data-progress-delete-form]');
    const progressDeleteLabel = progressDeleteModal?.querySelector('[data-progress-delete-label]');
    const progressDeleteError = progressDeleteModal?.querySelector('[data-progress-delete-error]');
    const progressDeleteSubmit = progressDeleteModal?.querySelector('[data-progress-delete-submit]');
    let currentProgressUrl = '';

    const closeProgressForm = () => {
        if (!progressFormModal) return;
        progressFormModal.classList.remove('open');
        progressFormModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { progressFormModal.hidden = true; }, 180);
    };
    const closeProgressDetail = () => {
        if (!progressDetailModal) return;
        progressDetailModal.classList.remove('open');
        progressDetailModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { progressDetailModal.hidden = true; }, 180);
    };
    const closeProgressDelete = () => {
        if (!progressDeleteModal) return;
        progressDeleteModal.classList.remove('open');
        progressDeleteModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { progressDeleteModal.hidden = true; }, 180);
    };
    const showProgressForm = () => {
        if (!progressFormModal) return;
        progressFormModal.hidden = false;
        progressFormModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => progressFormModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };
    const showProgressErrors = (message, errors = []) => {
        if (!progressErrors) return;
        const list = errors.length ? `<ul>${errors.map((error) => `<li>${escapeHtml(error)}</li>`).join('')}</ul>` : '';
        progressErrors.innerHTML = `<strong>${escapeHtml(message)}</strong>${list}`;
        progressErrors.hidden = false;
    };
    const loadProgressDocument = async (url) => {
        const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Progres Dokumen Keluar tidak tersedia.');
        return result.dokumen;
    };
    const fillProgressForm = (data) => {
        ['nomor_surat','jenis_surat','jumlah_dokumen','pemohon','pelaksana','up','tanggal_pengiriman','nomor_resi','tanggal_diterima','penerima','alamat_penerima','dokumen_link','security','tanggal_security','progres','status_agendaris'].forEach((name) => {
            const valueKey = `${name}_value`;
            progressForm.elements[name].value = Object.prototype.hasOwnProperty.call(data, valueKey) ? data[valueKey] : (data[name] === '-' ? '' : data[name]);
        });
        setupEkspedisiSelector(progressForm)?.setValue(data.nama_ekspedisi_value);
        const completionOption = progressForm.elements.status_agendaris?.querySelector('option[value="Selesai"]');
        if (completionOption) completionOption.disabled = data.progres !== 'Diambil Ekspedisi';
    };
    const setProgressSecurityFieldsLocked = (locked) => {
        ['security','tanggal_security','progres'].forEach((name) => {
            const field = progressForm?.elements[name];
            if (!field) return;
            field.disabled = locked;
            field.closest('.form-group')?.classList.toggle('field-source-locked', locked);
        });
    };
    const openProgressCreate = () => {
        if (!progressForm) return;
        setProgressSecurityFieldsLocked(true);
        progressForm.reset();
        setupEkspedisiSelector(progressForm)?.setValue('');
        const completionOption = progressForm.elements.status_agendaris?.querySelector('option[value="Selesai"]');
        if (completionOption) completionOption.disabled = true;
        progressForm.action = progressCreateUrl;
        progressFormTitle.textContent = 'Tambah Progres Dokumen Keluar';
        progressSubmit.textContent = 'Simpan dokumen';
        progressErrors.hidden = true;
        progressStatus.textContent = '';
        setProgressSecurityFieldsLocked(true);
        showProgressForm();
        window.setTimeout(() => progressForm.elements.nomor_surat.focus(), 120);
    };
    const openProgressEdit = async (url) => {
        if (!progressForm || !url) return;
        setProgressSecurityFieldsLocked(true);
        progressForm.reset();
        progressErrors.hidden = true;
        progressFormTitle.textContent = 'Edit Progres Dokumen Keluar';
        progressSubmit.textContent = 'Simpan perubahan';
        progressStatus.textContent = 'Memuat data...';
        showProgressForm();
        try {
            const data = await loadProgressDocument(url);
            progressForm.action = data.update_url;
            fillProgressForm(data);
            setProgressSecurityFieldsLocked(true);
            progressStatus.textContent = '';
        } catch (error) {
            showProgressErrors(error.message);
            progressStatus.textContent = '';
        }
    };
    const openProgressDetail = async (url) => {
        if (!progressDetailModal || !url) return;
        currentProgressUrl = url;
        progressDetailLoading.hidden = false;
        progressDetailContent.hidden = true;
        progressDetailModal.hidden = false;
        progressDetailModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => progressDetailModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        try {
            const data = await loadProgressDocument(url);
            Object.entries(data).forEach(([field, value]) => progressDetailModal.querySelectorAll(`[data-progress-field="${field}"]`).forEach((element) => { element.textContent = value; }));
            const hasDocumentLink = Boolean(data.dokumen_link);
            if (progressDocumentLink) {
                progressDocumentLink.href = hasDocumentLink ? data.dokumen_link : '#';
                progressDocumentLink.hidden = !hasDocumentLink;
            }
            if (progressDocumentEmpty) progressDocumentEmpty.hidden = hasDocumentLink;
            progressDetailEdit.hidden = false;
            progressDetailLoading.hidden = true;
            progressDetailContent.hidden = false;
        } catch (error) {
            progressDetailLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    document.querySelector('[data-progress-add]')?.addEventListener('click', openProgressCreate);
    document.querySelectorAll('[data-progress-view]').forEach((button) => button.addEventListener('click', () => openProgressDetail(button.dataset.progressUrl)));
    document.querySelectorAll('[data-progress-edit]').forEach((button) => button.addEventListener('click', () => openProgressEdit(button.dataset.progressUrl)));
    progressFormModal?.querySelectorAll('[data-progress-form-close]').forEach((button) => button.addEventListener('click', closeProgressForm));
    progressDetailModal?.querySelectorAll('[data-progress-detail-close]').forEach((button) => button.addEventListener('click', closeProgressDetail));
    progressDetailEdit?.addEventListener('click', () => { closeProgressDetail(); openProgressEdit(currentProgressUrl); });

    progressForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        progressErrors.hidden = true;
        progressStatus.textContent = 'Menyimpan data...';
        progressSubmit.disabled = true;
        try {
            const response = await fetch(progressForm.action, { method: 'POST', body: new FormData(progressForm), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) { showProgressErrors(result.message || 'Data belum dapat disimpan.', result.errors || []); return; }
            progressStatus.textContent = 'Berhasil disimpan';
            window.setTimeout(reloadOperationalPage, 500);
        } catch (error) {
            showProgressErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
        } finally {
            progressSubmit.disabled = false;
            if (progressStatus.textContent === 'Menyimpan data...') progressStatus.textContent = '';
        }
    });

    document.querySelectorAll('[data-progress-delete]').forEach((button) => button.addEventListener('click', () => {
        progressDeleteForm.action = button.dataset.deleteUrl;
        progressDeleteLabel.textContent = button.dataset.deleteLabel;
        progressDeleteError.hidden = true;
        progressDeleteModal.hidden = false;
        progressDeleteModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => progressDeleteModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    }));
    progressDeleteModal?.querySelectorAll('[data-progress-delete-close]').forEach((button) => button.addEventListener('click', closeProgressDelete));
    progressDeleteForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        progressDeleteError.hidden = true;
        progressDeleteSubmit.disabled = true;
        try {
            const response = await fetch(progressDeleteForm.action, { method: 'POST', body: new FormData(progressDeleteForm), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) throw new Error(result.message || 'Dokumen gagal dihapus.');
            window.setTimeout(reloadOperationalPage, 250);
        } catch (error) {
            progressDeleteError.textContent = error.message;
            progressDeleteError.hidden = false;
        } finally {
            progressDeleteSubmit.disabled = false;
        }
    });

    const reopenProgressModal = document.querySelector('#reopenProgressModal');
    const reopenProgressForm = reopenProgressModal?.querySelector('[data-reopen-progress-form]');
    const reopenProgressTitle = reopenProgressModal?.querySelector('[data-reopen-progress-title]');
    const reopenProgressDescription = reopenProgressModal?.querySelector('[data-reopen-progress-description]');
    const reopenProgressError = reopenProgressModal?.querySelector('[data-reopen-progress-error]');
    const reopenProgressSubmit = reopenProgressModal?.querySelector('[data-reopen-progress-submit]');
    const reopenProgressDefaultTitle = reopenProgressTitle?.textContent || 'Kembalikan ke Progres Dokumen?';

    const closeReopenProgress = () => {
        if (!reopenProgressModal) return;
        reopenProgressModal.classList.remove('open');
        reopenProgressModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { reopenProgressModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-reopen-progress]').forEach((button) => button.addEventListener('click', () => {
        if (!reopenProgressModal || !reopenProgressForm) return;
        const lockedMessage = button.dataset.reopenLockedMessage || '';
        reopenProgressForm.action = button.dataset.reopenUrl;
        reopenProgressForm.hidden = false;
        if (reopenProgressSubmit) reopenProgressSubmit.hidden = lockedMessage !== '';
        if (reopenProgressTitle) reopenProgressTitle.textContent = lockedMessage !== '' ? 'Disposisi telah diproses' : reopenProgressDefaultTitle;
        if (reopenProgressDescription) {
            if (lockedMessage !== '') {
                reopenProgressDescription.textContent = lockedMessage;
            } else {
                reopenProgressDescription.replaceChildren();
                const label = document.createElement('strong');
                label.textContent = button.dataset.reopenLabel || 'Dokumen ini';
                reopenProgressDescription.append(label, ` ${reopenProgressDescription.dataset.defaultDescription || ''}`);
            }
        }
        reopenProgressError.hidden = true;
        reopenProgressModal.hidden = false;
        reopenProgressModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => reopenProgressModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    }));

    reopenProgressModal?.querySelectorAll('[data-reopen-progress-close]').forEach((button) => button.addEventListener('click', closeReopenProgress));
    reopenProgressForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        reopenProgressError.hidden = true;
        reopenProgressSubmit.disabled = true;
        try {
            const response = await fetch(reopenProgressForm.action, {
                method: 'POST', body: new FormData(reopenProgressForm), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            updateCsrf(result.csrf);
            if (!response.ok || !result.success) throw new Error(result.message || 'Dokumen belum dapat dikembalikan ke progres.');
            window.location.href = result.redirect_url;
        } catch (error) {
            reopenProgressError.textContent = error.message;
            reopenProgressError.hidden = false;
        } finally {
            reopenProgressSubmit.disabled = false;
        }
    });

    const incomingProgressModal = document.querySelector('#incomingProgressDetailModal');
    const incomingProgressLoading = incomingProgressModal?.querySelector('[data-incoming-progress-loading]');
    const incomingProgressContent = incomingProgressModal?.querySelector('[data-incoming-progress-content]');
    const closeIncomingProgress = () => {
        if (!incomingProgressModal) return;
        incomingProgressModal.classList.remove('open');
        incomingProgressModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { incomingProgressModal.hidden = true; }, 180);
    };
    const openIncomingProgress = async (url) => {
        if (!incomingProgressModal || !incomingProgressLoading || !incomingProgressContent || !url) return;
        incomingProgressLoading.innerHTML = '<span></span><strong>Memuat Progres Dokumen Masuk...</strong>';
        incomingProgressLoading.hidden = false;
        incomingProgressContent.hidden = true;
        incomingProgressModal.hidden = false;
        incomingProgressModal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => incomingProgressModal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        try {
            const response = await fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Progres Dokumen Masuk tidak tersedia.');
            Object.entries(result.dokumen).forEach(([field, value]) => {
                incomingProgressModal.querySelectorAll(`[data-incoming-progress-field="${field}"]`).forEach((element) => { element.textContent = value; });
            });
            incomingProgressLoading.hidden = true;
            incomingProgressContent.hidden = false;
        } catch (error) {
            incomingProgressLoading.innerHTML = `<strong>${escapeHtml(error.message)}</strong>`;
        }
    };

    document.querySelectorAll('[data-incoming-progress-view]').forEach((button) => button.addEventListener('click', () => openIncomingProgress(button.dataset.incomingProgressUrl)));
    incomingProgressModal?.querySelectorAll('[data-incoming-progress-close]').forEach((button) => button.addEventListener('click', closeIncomingProgress));

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (agendaFormModal?.classList.contains('open')) closeAgendaForm();
        if (agendaDetailModal?.classList.contains('open')) closeAgendaDetail();
        if (agendaDeleteModal?.classList.contains('open')) closeAgendaDelete();
        if (dokumenKeluarFormModal?.classList.contains('open')) closeDokumenKeluarForm();
        if (dokumenKeluarDetailModal?.classList.contains('open')) closeDokumenKeluarDetail();
        if (dokumenKeluarDeleteModal?.classList.contains('open')) closeDokumenKeluarDelete();
        if (progressFormModal?.classList.contains('open')) closeProgressForm();
        if (progressDetailModal?.classList.contains('open')) closeProgressDetail();
        if (progressDeleteModal?.classList.contains('open')) closeProgressDelete();
        if (reopenProgressModal?.classList.contains('open')) closeReopenProgress();
        if (incomingProgressModal?.classList.contains('open')) closeIncomingProgress();
    });
})();

(() => {
    const dialog = document.querySelector('#lrMappingDetailDialog');
    if (dialog instanceof HTMLDialogElement) {
        document.querySelectorAll('[data-lr-mapping-detail-open]').forEach((button) => button.addEventListener('click', () => {
            if (!dialog.open) dialog.showModal();
        }));
        dialog.querySelectorAll('[data-lr-mapping-detail-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
    }
    document.querySelectorAll('form[data-mapping-delete]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm('Hapus mapping ini? Upload berikutnya tidak akan memakai aturan tersebut.')) event.preventDefault();
    }));
    document.querySelectorAll('[data-mapping-search]').forEach((input) => input.addEventListener('input', () => {
        const rows = document.querySelector('[data-mapping-search-rows]');
        if (!rows) return;
        const keyword = input.value.trim().toLocaleLowerCase('id-ID');
        rows.querySelectorAll('tr').forEach((row) => {
            row.hidden = keyword !== '' && !row.textContent.toLocaleLowerCase('id-ID').includes(keyword);
        });
    }));
})();

// Period-bound Oracle source adjustments: visual raw-COA formula editor with audit-safe deactivation.
(() => {
    const dialog = document.querySelector('#sourceAdjustmentDialog');
    const form = dialog?.querySelector('[data-source-adjustment-form]');
    if (!(dialog instanceof HTMLDialogElement) || !(form instanceof HTMLFormElement)) return;
    const approvalRequired = form.dataset.approvalRequired === '1';
    const fields = {
        id: form.querySelector('[data-source-adjustment-id]'),
        scope: form.querySelector('[data-source-adjustment-scope]'),
        column: form.querySelector('[data-source-adjustment-column]'),
        target: form.querySelector('[data-source-adjustment-target]'),
        from: form.querySelector('[data-source-adjustment-from]'),
        to: form.querySelector('[data-source-adjustment-to]'),
        reason: form.querySelector('[data-source-adjustment-reason]'),
        active: form.querySelector('[data-source-adjustment-active]'),
        lines: form.querySelector('[data-source-adjustment-lines]'),
        component: form.querySelector('[data-source-adjustment-component]'),
        list: form.querySelector('[data-source-adjustment-term-list]'),
        empty: form.querySelector('[data-source-adjustment-empty]'),
        count: form.querySelector('[data-source-adjustment-count]'),
        submit: form.querySelector('[data-source-adjustment-submit]'),
        title: dialog.querySelector('[data-source-adjustment-title]'),
        menu: form.querySelector('[data-source-description-menu]'),
        menuToggle: form.querySelector('[data-source-description-toggle]'),
        addDescription: form.querySelector('[data-source-adjustment-add-description]'),
    };
    const normalize = (value) => String(value || '').trim().replace(/\s+/g, ' ').toLocaleLowerCase('id-ID');
    const sourceOptions = [...form.querySelectorAll('[data-source-description-option]')];
    const sourceMap = new Map(sourceOptions.map((option) => [normalize(option.dataset.value), option.dataset.value]));
    let terms = [];
    const decode = (encoded) => {
        try {
            const bytes = Uint8Array.from(window.atob(encoded || ''), (character) => character.charCodeAt(0));
            return new TextDecoder().decode(bytes);
        } catch (_error) { return ''; }
    };
    const parse = (value) => String(value || '').split(/\r?\n/).map((line) => {
        const match = line.trim().match(/^([+\-*\/×÷])\s*(.+)$/);
        if (!match) return null;
        return { operator: match[1] === '×' ? '*' : (match[1] === '÷' ? '/' : match[1]), label: match[2].trim() };
    }).filter(Boolean);
    const sync = () => {
        if (!(fields.list instanceof HTMLElement) || !(fields.lines instanceof HTMLTextAreaElement)) return;
        if (terms[0]) terms[0].operator = '+';
        fields.list.replaceChildren();
        terms.forEach((term, index) => {
            if (index > 0) {
                const connector = document.createElement('div'); connector.className = `formula-term-connector ${term.operator === '-' ? 'is-minus' : (['*', '/'].includes(term.operator) ? 'is-math' : 'is-plus')}`;
                const connectorLabel = document.createElement('span'); connectorLabel.textContent = 'Pilih operasi';
                const choices = document.createElement('div'); choices.className = 'formula-term-choices'; choices.setAttribute('role', 'group'); choices.setAttribute('aria-label', `Operasi sebelum ${term.label}`);
                [['+', '+', 'Tambah'], ['-', '−', 'Kurang'], ['*', '×', 'Kali'], ['/', '÷', 'Bagi']].forEach(([value, symbol, text]) => {
                    const choice = document.createElement('button'); choice.type = 'button'; choice.className = 'formula-term-choice';
                    choice.classList.toggle('is-selected', term.operator === value);
                    choice.setAttribute('aria-pressed', term.operator === value ? 'true' : 'false');
                    choice.innerHTML = `<b>${symbol}</b><span>${text}</span>`;
                    choice.addEventListener('click', () => { term.operator = value; sync(); });
                    choices.append(choice);
                });
                connector.append(connectorLabel, choices); fields.list.append(connector);
            }
            const row = document.createElement('div');
            row.className = 'formula-term-row source-formula-term';
            const content = document.createElement('div'); content.className = 'formula-term-content';
            const label = document.createElement('span'); label.className = 'formula-term-label'; label.textContent = term.label;
            content.append(label);
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'formula-term-remove'; remove.textContent = '×'; remove.setAttribute('aria-label', `Hapus ${term.label}`);
            remove.addEventListener('click', () => { terms.splice(index, 1); sync(); });
            row.append(content, remove); fields.list.append(row);
        });
        fields.lines.value = terms.map((term) => `${term.operator} ${term.label}`).join('\n');
        if (fields.empty instanceof HTMLElement) fields.empty.hidden = terms.length > 0;
        if (fields.count instanceof HTMLElement) fields.count.textContent = `${terms.length} uraian`;
        if (fields.submit instanceof HTMLButtonElement) fields.submit.disabled = terms.length === 0;
    };
    const open = (button = null) => {
        const editing = button instanceof HTMLElement && button.hasAttribute('data-source-adjustment-edit');
        form.reset();
        const currentMonth = new Date().toISOString().slice(0, 7);
        terms = editing ? parse(decode(button.dataset.lines)) : [];
        if (fields.id instanceof HTMLInputElement) fields.id.value = editing ? button.dataset.id || '' : '';
        if (fields.scope instanceof HTMLSelectElement) fields.scope.value = editing ? button.dataset.scope || 'all' : 'surabaya';
        if (fields.column instanceof HTMLSelectElement) fields.column.value = editing ? button.dataset.column || 'all' : 'all';
        if (fields.target instanceof HTMLSelectElement && editing) fields.target.value = button.dataset.target || fields.target.options[0]?.value;
        if (fields.from instanceof HTMLInputElement) fields.from.value = editing ? button.dataset.from || currentMonth : currentMonth;
        if (fields.to instanceof HTMLInputElement) fields.to.value = editing ? button.dataset.to || currentMonth : currentMonth;
        if (fields.reason instanceof HTMLTextAreaElement) fields.reason.value = editing ? button.dataset.reason || '' : '';
        if (fields.active instanceof HTMLInputElement) fields.active.checked = editing ? button.dataset.active === '1' : true;
        if (fields.title instanceof HTMLElement) fields.title.textContent = approvalRequired
            ? (editing ? 'Ajukan Perubahan Sumber' : 'Ajukan Penyesuaian Sumber')
            : (editing ? 'Edit Penyesuaian Sumber' : 'Buat Penyesuaian Sumber');
        if (fields.submit instanceof HTMLButtonElement) fields.submit.textContent = approvalRequired
            ? 'Kirim untuk Persetujuan'
            : (editing ? 'Simpan Perubahan' : 'Simpan Penyesuaian');
        sync();
        if (!dialog.open) dialog.showModal();
    };
    document.querySelectorAll('[data-source-adjustment-create]').forEach((button) => button.addEventListener('click', () => open()));
    document.querySelectorAll('[data-source-adjustment-edit]').forEach((button) => button.addEventListener('click', () => open(button)));
    dialog.querySelectorAll('[data-source-adjustment-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    const closeSourceMenu = () => {
        if (fields.menu instanceof HTMLElement) fields.menu.hidden = true;
        if (fields.component instanceof HTMLInputElement) fields.component.setAttribute('aria-expanded', 'false');
    };
    const filterSourceMenu = () => {
        if (!(fields.component instanceof HTMLInputElement) || !(fields.menu instanceof HTMLElement)) return;
        const keyword = normalize(fields.component.value);
        let visible = 0;
        sourceOptions.forEach((option) => {
            option.hidden = keyword !== '' && !normalize(option.dataset.value).includes(keyword);
            if (!option.hidden) visible++;
        });
        fields.menu.hidden = false;
        fields.component.setAttribute('aria-expanded', 'true');
        fields.menu.dataset.empty = visible === 0 ? '1' : '0';
    };
    sourceOptions.forEach((option) => option.addEventListener('click', () => {
        if (fields.component instanceof HTMLInputElement) {
            fields.component.value = option.dataset.value || '';
            fields.component.setCustomValidity('');
            fields.component.focus();
        }
        closeSourceMenu();
    }));
    fields.menuToggle?.addEventListener('click', () => {
        if (fields.menu instanceof HTMLElement && !fields.menu.hidden) closeSourceMenu();
        else { fields.component?.focus(); filterSourceMenu(); }
    });
    fields.component?.addEventListener('focus', filterSourceMenu);
    fields.component?.addEventListener('input', () => { fields.component.setCustomValidity(''); filterSourceMenu(); });
    fields.component?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSourceMenu();
        if (event.key === 'Enter') {
            const firstVisible = sourceOptions.find((option) => !option.hidden);
            if (firstVisible) { event.preventDefault(); firstVisible.click(); }
        }
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.source-description-picker')) closeSourceMenu();
    });
    fields.addDescription?.addEventListener('click', () => {
        if (!(fields.component instanceof HTMLInputElement)) return;
        const key = normalize(fields.component.value);
        const canonical = sourceMap.get(key);
        if (!canonical) {
            fields.component.setCustomValidity('Pilih uraian Oracle yang tersedia pada daftar. Unggah laporan Oracle terlebih dahulu jika uraian belum tersedia.');
            fields.component.reportValidity();
            return;
        }
        fields.component.setCustomValidity('');
        const existing = terms.find((term) => normalize(term.label) === key);
        if (!existing) terms.push({ label: canonical, operator: '+' });
        fields.component.value = '';
        closeSourceMenu();
        sync();
    });
    form.addEventListener('submit', (event) => {
        const message = approvalRequired
            ? 'Kirim pengajuan ini kepada Administrator? Perhitungan belum berubah sampai pengajuan disetujui.'
            : 'Simpan penyesuaian sumber ini? Nilai laporan pada unit, LOB, dan periode terpilih akan dihitung ulang dari Ending Balance Oracle.';
        if (terms.length === 0 || !window.confirm(message)) event.preventDefault();
    });
    document.querySelectorAll('form[data-source-adjustment-deactivate]').forEach((deactivateForm) => deactivateForm.addEventListener('submit', (event) => {
        const message = deactivateForm.dataset.approvalRequired === '1'
            ? 'Ajukan penonaktifan kepada Administrator? Aturan tetap aktif sampai pengajuan disetujui.'
            : 'Nonaktifkan penyesuaian ini? Sistem akan kembali memakai aturan sumber yang lebih umum atau standar.';
        if (!window.confirm(message)) event.preventDefault();
    }));
    document.querySelectorAll('form[data-source-adjustment-delete]').forEach((deleteForm) => deleteForm.addEventListener('submit', (event) => {
        const message = deleteForm.dataset.approvalRequired === '1'
            ? 'Ajukan penghapusan kepada Administrator? Aturan nonaktif tetap tersimpan sampai pengajuan disetujui.'
            : 'Hapus penyesuaian nonaktif ini? Aturan tidak dapat dipulihkan dari daftar, tetapi riwayat audit tetap disimpan.';
        if (!window.confirm(message)) event.preventDefault();
    }));
    document.querySelectorAll('form[data-source-request-approve]').forEach((approvalForm) => approvalForm.addEventListener('submit', (event) => {
        if (!window.confirm('Setujui pengajuan ini? Perubahan akan langsung diterapkan pada perhitungan laporan.')) event.preventDefault();
    }));
    document.querySelectorAll('form[data-source-request-reject]').forEach((rejectionForm) => rejectionForm.addEventListener('submit', (event) => {
        if (!window.confirm('Tolak pengajuan ini? Perubahan tidak akan diterapkan.')) event.preventDefault();
    }));
})();

// Laba & Rugi: buka dan tutup rincian kelompok tanpa memuat ulang halaman.
(() => {
    document.querySelectorAll('.lr-report-table [data-lr-expandable]').forEach((row) => {
        const toggle = row.querySelector('[data-lr-toggle]');
        if (!toggle) return;
        row.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(expanded));
            row.querySelectorAll('[data-rka-group-output], [data-lr-group-output]').forEach((output) => {
                if (output.hasAttribute('data-rka-group-output')) output.dataset.rkaExpanded = String(expanded);
                if (output.hasAttribute('data-lr-group-output')) output.dataset.lrExpanded = String(expanded);
                output.setAttribute('aria-hidden', String(expanded));
            });
            row.closest('table').querySelectorAll('[data-lr-detail]').forEach((detail) => {
                if (detail.dataset.lrDetail === toggle.dataset.lrToggle) detail.hidden = !expanded;
            });
        });
    });
})();

// Oracle LR: approved expense descriptions, fixed source Kanwil, native upload popup.
(() => {
    const dialog = document.querySelector('#lrUploadDialog');
    if (!dialog) return;
    document.querySelectorAll('[data-lr-upload-open]').forEach(button => button.addEventListener('click', () => { if (!dialog.open) dialog.showModal(); }));
    dialog.querySelectorAll('[data-lr-upload-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    const form = dialog.querySelector('[data-lr-upload-form]');
    const file = form.querySelector('[name="lr_excel"]');
    file.addEventListener('change', () => {
        const selected = file.files[0];
        file.setCustomValidity(selected && (!/\.xlsx$/i.test(selected.name) || selected.size > 5 * 1024 * 1024) ? 'Gunakan Excel .xlsx, maksimal 5 MB.' : '');
    });
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true; button.textContent = 'Menghitung…';
    });
    if (dialog.dataset.autoOpen === 'true') dialog.showModal();
})();

// Oracle LR deletion: exact unit/month/year selected in the confirmation dialog.
(() => {
    const dialog=document.querySelector('#lrDeleteDialog');
    if (!dialog) return;
    document.querySelectorAll('[data-lr-delete-open]').forEach(button=>button.addEventListener('click',()=>{ if (!dialog.open) dialog.showModal(); }));
    dialog.querySelectorAll('[data-lr-delete-close]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
    dialog.addEventListener('click',event=>{ if (event.target===dialog) dialog.close(); });
    dialog.querySelector('[data-lr-delete-form]').addEventListener('submit',()=>{
        const button=dialog.querySelector('button[type="submit"]'); button.disabled=true; button.textContent='Menghapus…';
    });
})();

// RKA Kanwil Surabaya: pemilihan halaman pengaturan melalui dialog.
(() => {
    const dialog = document.querySelector('#rkaSettingsDialog');
    if (!dialog) return;
    const uploadDialog = document.querySelector('#rkaUploadDialog');
    const manualDialog = document.querySelector('#rkaManualDialog');
    dialog.querySelector('[data-rka-manual-open]')?.addEventListener('click', () => {
        if (!manualDialog) return;
        dialog.close();
        if (!manualDialog.open) manualDialog.showModal();
    });
    manualDialog?.querySelectorAll('[data-rka-manual-close]').forEach((button) => {
        button.addEventListener('click', () => manualDialog.close());
    });
    manualDialog?.querySelector('[data-rka-manual-back]')?.addEventListener('click', () => {
        manualDialog.close();
        if (!dialog.open) dialog.showModal();
    });
    manualDialog?.addEventListener('click', (event) => {
        if (event.target === manualDialog) manualDialog.close();
    });
    document.querySelectorAll('[data-rka-settings-open]').forEach((button) => {
        button.addEventListener('click', () => { if (!dialog.open) dialog.showModal(); });
    });
    dialog.querySelectorAll('[data-rka-settings-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.querySelector('[data-rka-upload-open]')?.addEventListener('click', () => {
        if (!uploadDialog) return;
        dialog.close();
        if (!uploadDialog.open) uploadDialog.showModal();
    });
    uploadDialog?.querySelectorAll('[data-rka-upload-close]').forEach((button) => {
        button.addEventListener('click', () => uploadDialog.close());
    });
    uploadDialog?.querySelector('[data-rka-upload-back]')?.addEventListener('click', () => {
        uploadDialog.close();
        if (!dialog.open) dialog.showModal();
    });
    uploadDialog?.addEventListener('click', (event) => {
        if (event.target === uploadDialog) uploadDialog.close();
    });
    const fileInput = uploadDialog?.querySelector('[data-rka-upload-file]');
    const fileStatus = uploadDialog?.querySelector('[data-rka-upload-status]');
    fileInput?.addEventListener('change', () => {
        if (!fileStatus) return;
        const file = fileInput.files?.[0];
        const valid = !file || (/\.xlsx$/i.test(file.name) && file.size <= 5 * 1024 * 1024);
        fileInput.setCustomValidity(valid ? '' : 'Pilih berkas Template RKA .xlsx, maksimal 5 MB.');
        fileStatus.textContent = !valid ? 'Pilih berkas Template RKA .xlsx, maksimal 5 MB.'
            : file ? `Berkas dipilih: ${file.name}` : 'Belum ada berkas dipilih.';
        if (!valid) fileInput.reportValidity();
    });
    if (uploadDialog?.dataset.autoOpen === 'true') uploadDialog.showModal();
    else if (manualDialog?.dataset.autoOpen === 'true') manualDialog.showModal();
})();

// RKA Excel: same two-step selection and explicit setting-again gate as manual.
(() => {
    const form = document.querySelector('[data-rka-import-form]');
    if (!form) return;
    const selectStage = form.querySelector('[data-rka-upload-selection]');
    const fileStage = form.querySelector('[data-rka-upload-file-stage]');
    const unit = form.querySelector('#rkaUploadUnit');
    const year = form.querySelector('#rkaUploadYear');
    const next = form.querySelector('[data-rka-upload-next]');
    const reset = form.querySelector('[data-rka-upload-reset]');
    const existingAlert = form.querySelector('[data-rka-upload-existing-alert]');
    const message = form.querySelector('[data-rka-upload-selection-status]');
    const file = form.querySelector('[data-rka-upload-file]');
    const confirmation = form.querySelector('[name="confirm_replace"]');
    const submit = form.querySelector('[data-rka-upload-submit]');
    const back = form.querySelector('[data-rka-upload-step-back]');
    const menuBack = form.querySelector('[data-rka-upload-back]');
    const revision = form.querySelector('[data-rka-upload-revision]');
    const resetFlag = form.querySelector('[data-rka-upload-reset-flag]');
    const resetId = form.querySelector('[data-rka-upload-reset-id]');
    const scope = form.querySelector('#rkaUploadScope');
    const allSnapshots = form.querySelector('[data-rka-upload-all-snapshots]');
    const units = JSON.parse(form.dataset.rkaUnits);
    const deleteExisting = form.querySelector('[data-rka-upload-delete-existing]');
    let loading = false, pending = null, active = null;
    const matchesChoice = (data) => data && String(data.year) === year.value
        && (scope.value === 'all' ? data.scope === 'all' : data.scope !== 'all' && data.unit === unit.value);
    const updateScope = () => {
        const all = scope.value === 'all';
        unit.hidden = form.querySelector('[data-rka-upload-unit-label]').hidden = all;
        unit.required = !all;
        form.querySelector('[data-rka-upload-all-help]').hidden = !all;
    };
    const setStep = (step) => {
        form.dataset.rkaUploadStep = step;
        selectStage.hidden = step !== 'selection';
        fileStage.hidden = step !== 'file';
        back.hidden = submit.hidden = step !== 'file';
        menuBack.hidden = step !== 'selection';
        file.disabled = confirmation.disabled = submit.disabled = step !== 'file' || loading;
    };
    const enterFile = (data) => {
        active = data;
        pending = null;
        file.value = '';
        file.setCustomValidity('');
        confirmation.checked = false;
        revision.value = data.scope === 'all' ? '0' : String(data.revision);
        resetFlag.value = data.has_record ? '1' : '0';
        resetId.value = data.scope !== 'all' && data.has_record ? String(data.budget_id) : '';
        allSnapshots.value = data.scope === 'all' ? JSON.stringify(data.records) : '';
        existingAlert.hidden = true;
        form.querySelector('[data-rka-upload-status]').textContent = 'Belum ada berkas dipilih.';
        form.querySelector('[data-rka-upload-active-context]').textContent = `Tahap 2 dari 2 — upload RKA ${data.scope === 'all' ? 'seluruh unit kerja' : data.unit} tahun ${data.year}.`;
        form.querySelector('[data-rka-upload-confirm-text]').textContent = data.scope === 'all'
            ? 'Saya mengonfirmasi upload seluruh unit dan tahun yang dipilih. RKA yang sudah ada akan diganti sesuai sheet masing-masing setelah seluruh berkas lolos pemeriksaan.'
            : 'Saya mengonfirmasi unit dan tahun yang dipilih. Upload akan mengganti RKA unit/tahun tersebut jika sudah ada.';
        setStep('file');
    };
    const choiceChanged = () => { active = pending = null; existingAlert.hidden = true; resetFlag.value = '0'; resetId.value = ''; allSnapshots.value = ''; setStep('selection'); updateScope(); };
    scope.addEventListener('change', choiceChanged);
    unit.addEventListener('change', choiceChanged);
    year.addEventListener('input', choiceChanged);
    back.addEventListener('click', () => setStep('selection'));
    reset.addEventListener('click', () => {
        if (!loading && matchesChoice(pending)) enterFile(pending);
    });
    form.querySelector('[data-rka-upload-delete-existing]').addEventListener('click', () => {
        if (loading || scope.value === 'all' || !matchesChoice(pending)) return;
        document.dispatchEvent(new CustomEvent('rka:delete', {detail:{unit:pending.unit,year:pending.year,revision:pending.revision,id:pending.budget_id}}));
    });
    next.addEventListener('click', async () => {
        if (loading || !scope.reportValidity() || (scope.value !== 'all' && !unit.reportValidity()) || !year.reportValidity()) return;
        if (file.files?.length && !window.confirm('Melanjutkan akan mengosongkan pilihan berkas sebelumnya. Lanjutkan?')) return;
        active = pending = null;
        existingAlert.hidden = true;
        loading = true;
        scope.disabled = unit.disabled = year.disabled = next.disabled = true;
        setStep('selection');
        message.textContent = 'Memeriksa RKA sesuai unit kerja dan tahun…';
        try {
            const url = new URL(form.dataset.rkaDataUrl, window.location.href);
            url.searchParams.set('unit_kerja', unit.value); url.searchParams.set('tahun', year.value);
            if (scope.value === 'all') url.searchParams.set('scope', 'all');
            const response = await fetch(url, {credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
            const contentType = response.headers?.get('content-type');
            if (contentType && !contentType.includes('application/json')) throw new Error('Sesi login berakhir. Silakan masuk kembali.');
            const data = await response.json();
            if (!response.ok) throw new Error(typeof data.error === 'string' ? data.error : 'Data RKA tidak dapat dimuat.');
            if (scope.value === 'all') {
                if (data.scope !== 'all' || String(data.year) !== year.value || !data.records || Array.isArray(data.records)
                    || Object.keys(data.records).length !== units.length || units.some((name) => {
                        const record = data.records[name];
                        return !record || !Number.isSafeInteger(record.revision) || record.revision < 0 || record.revision > 999999999
                            || !(record.id === null ? record.revision === 0 : Number.isSafeInteger(record.id) && record.id > 0 && record.revision > 0);
                    })) throw new Error('Data RKA seluruh unit tidak sesuai pilihan.');
                data.has_record = units.some((name) => data.records[name].id !== null);
            } else if (data.unit !== unit.value || String(data.year) !== year.value || typeof data.has_record !== 'boolean'
                || !Number.isSafeInteger(data.revision) || data.revision < 0
                || (data.has_record && (!Number.isSafeInteger(data.budget_id) || data.budget_id <= 0))) throw new Error('Data RKA tidak sesuai pilihan.');
            loading = false;
            if (data.has_record) {
                pending = data;
                existingAlert.hidden = false;
                deleteExisting.hidden = scope.value === 'all';
                form.querySelector('[data-rka-upload-existing-message]').textContent = data.scope === 'all'
                    ? `RKA tahun ${data.year} sudah diseting untuk: ${units.filter((name) => data.records[name].id !== null).join(', ')}. Klik Seting Ulang untuk mengganti RKA melalui sheet masing-masing. Data lama hanya diganti setelah seluruh upload berhasil disimpan.`
                    : `RKA ${data.unit} tahun ${data.year} sudah diseting. Klik Seting Ulang untuk memilih berkas pengganti. Data lama hanya diganti setelah upload berhasil disimpan.`;
                message.textContent = 'Konfirmasi Seting Ulang untuk melanjutkan.';
            } else enterFile(data);
        } catch (error) { loading = false; message.textContent = `Gagal memeriksa RKA: ${error.message}. Silakan coba kembali.`; }
        finally { loading = false; scope.disabled = unit.disabled = year.disabled = next.disabled = false; }
    });
    form.addEventListener('submit', (event) => {
        if (loading || form.dataset.rkaUploadStep !== 'file' || !matchesChoice(active)) { event.preventDefault(); return; }
        submit.disabled = true; submit.textContent = 'Memeriksa & menyimpan…';
    });
    updateScope();
    setStep('selection');
})();

// RKA deletion: always confirm an exact unit, year and revision before a POST.
(() => {
    const dialog = document.querySelector('#rkaDeleteDialog');
    if (!dialog) return;
    const form = dialog.querySelector('[data-rka-delete-form]');
    const open = (target) => {
        if (!target || typeof target.unit !== 'string' || !/^\d{4}$/.test(String(target.year)) || !/^\d{1,9}$/.test(String(target.revision)) || !/^[1-9]\d{0,17}$/.test(String(target.id))) return;
        form.querySelector('[data-rka-delete-unit]').value = target.unit;
        form.querySelector('[data-rka-delete-year]').value = String(target.year);
        form.querySelector('[data-rka-delete-revision]').value = String(target.revision);
        form.querySelector('[data-rka-delete-id]').value = String(target.id);
        form.querySelector('[name="confirm_delete"]').checked = false;
        dialog.querySelector('[data-rka-delete-description]').textContent = `Hapus RKA ${target.unit} tahun ${target.year}?`;
        if (!dialog.open) dialog.showModal();
    };
    document.querySelectorAll('[data-rka-delete-open]').forEach((button) => button.addEventListener('click', () => open(button.dataset)));
    document.addEventListener('rka:delete', (event) => open(event.detail));
    dialog.querySelectorAll('[data-rka-delete-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Menghapus…';
    });
})();

// Manual RKA: calculate integer cents with BigInt, never floating-point money.
(() => {
    const form = document.querySelector('[data-rka-manual-form]');
    const schemaElement = document.querySelector('#rkaManualSchema');
    if (!form || !schemaElement) return;
    const schema = JSON.parse(schemaElement.textContent);
    const fields = [...form.querySelectorAll('[data-rka-input]')];
    const outputs = [...form.querySelectorAll('[data-rka-output]')];
    const status = form.querySelector('[data-rka-manual-status]');
    let selectionReady = () => true;
    const parse = (text) => {
        let value = text.trim();
        if (value === '') return 0n;
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/.test(value)) value = value.replace(/\./g, '').replace(',', '.');
        else if (!/^-?\d+\.\d{1,2}$/.test(value)) throw new Error('Gunakan format 1.234.567,89, maksimal 2 angka desimal.');
        const negative = value.startsWith('-');
        const [whole, fraction = ''] = value.replace(/^-/, '').split('.');
        if ((whole.replace(/^0+/, '') || '0').length > 22) throw new Error('Nominal maksimal 22 digit.');
        const amount = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
        return negative ? -amount : amount;
    };
    const format = (amount) => {
        if (amount === 0n) return '';
        const negative = amount < 0n;
        const digits = (negative ? -amount : amount).toString().padStart(3, '0');
        const fraction = digits.slice(-2);
        return (negative ? '-' : '') + digits.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + (fraction === '00' ? '' : ',' + fraction);
    };
    const update = () => {
        const values = {};
        let valid = true;
        fields.forEach((field) => {
            try { values[field.dataset.rkaInput] = parse(field.value); field.setCustomValidity(''); }
            catch (error) { field.setCustomValidity(error.message); valid = false; }
        });
        let message = 'Jumlah dihitung otomatis. Periksa nominal sebelum menyimpan.';
        if (valid) {
            try {
                for (const [row, definition] of Object.entries(schema.rows)) {
                    let total = 0n;
                    for (const column of ['C', 'D', 'E', 'F', 'G']) {
                        const key = `${column}${row}`;
                        if (definition.terms !== null) values[key] = definition.terms.reduce((sum, term) => sum + values[`${column}${term.row}`] * BigInt(term.coefficient), 0n);
                        const amount = values[key];
                        if (typeof amount !== 'bigint') throw new Error('Isian RKA tidak lengkap. Muat ulang halaman.');
                        if ((amount < 0n ? -amount : amount).toString().padStart(3, '0').slice(0, -2).length > 22) throw new Error('Hasil perhitungan melampaui 22 digit. Kurangi nominal isian.');
                        total += amount;
                    }
                    if ((total < 0n ? -total : total).toString().padStart(3, '0').slice(0, -2).length > 22) throw new Error('Jumlah melampaui 22 digit. Kurangi nominal isian.');
                    values[`H${row}`] = total;
                }
            } catch (error) { valid = false; message = error.message; }
        } else message = 'Ada nominal tidak valid. Perbaiki isian sebelum jumlah dihitung atau disimpan.';
        outputs.forEach((output) => {
            const formatted = valid ? format(values[output.dataset.rkaOutput]) : '';
            if (valid && formatted.startsWith('-')) {
                // Only exact formatted BigInt money is interpolated, never user text.
                output.innerHTML = `<span class="lr-rka-negative-marker">*</span>${formatted.slice(1)}`;
            } else output.textContent = valid ? (formatted || '-') : '—';
            output.dataset.rkaEmpty = formatted === '' ? 'true' : 'false';
        });
        if (status) { status.textContent = message; status.classList.toggle('lr-rka-status-error', !valid); }
        return valid;
    };
    const revealInvalid = (field) => {
        const row = field.closest('[data-lr-detail]');
        if (!row) return;
        form.querySelectorAll('[data-lr-detail]').forEach((detail) => { if (detail.dataset.lrDetail === row.dataset.lrDetail) detail.hidden = false; });
        form.querySelectorAll('[data-lr-toggle]').forEach((toggle) => {
            if (toggle.dataset.lrToggle !== row.dataset.lrDetail) return;
            toggle.setAttribute('aria-expanded', 'true');
            toggle.closest('tr').querySelectorAll('[data-rka-group-output]').forEach((output) => {
                output.dataset.rkaExpanded = 'true';
                output.setAttribute('aria-hidden', 'true');
            });
        });
    };
    fields.forEach((field) => {
        field.addEventListener('input', update);
        field.addEventListener('focus', () => field.select());
        field.addEventListener('blur', () => { try { field.value = format(parse(field.value)); } catch (_) { /* Keep invalid input visible. */ } });
    });
    form.addEventListener('invalid', (event) => revealInvalid(event.target), true);
    form.addEventListener('submit', (event) => {
        if (!selectionReady()) { event.preventDefault(); return; }
        if (!update()) { event.preventDefault(); form.reportValidity(); return; }
        const button = form.querySelector('button[type="submit"]');
        if (button) { button.disabled = true; button.textContent = 'Menyimpan…'; }
    });
    update();
    const unitChoice = form.querySelector('[data-rka-manual-unit]');
    const yearChoice = form.querySelector('[data-rka-manual-year]');
    const loadButton = form.querySelector('[data-rka-manual-load]');
    if (!unitChoice || !yearChoice || !loadButton) return;
    const unitField = form.querySelector('[name="unit_kerja"]');
    const yearField = form.querySelector('[name="tahun"]');
    const revisionField = form.querySelector('[name="revision"]');
    const confirmation = form.querySelector('[name="confirm_replace"]');
    const selectionStatus = form.querySelector('[data-rka-selection-status]');
    const saveButton = form.querySelector('button[type="submit"]');
    const popup = form.closest('dialog');
    const selectionStage = form.querySelector('[data-rka-selection-stage]');
    const inputStage = form.querySelector('[data-rka-input-stage]');
    const existingAlert = form.querySelector('[data-rka-existing-alert]');
    const resetButton = form.querySelector('[data-rka-manual-reset]');
    const stepBack = form.querySelector('[data-rka-step-back]');
    let loading = false;
    let dirty = form.dataset.hasUnsaved === 'true';
    let pendingExisting = null;
    selectionReady = () => form.dataset.rkaStep === 'input' && !loading && unitChoice.value === unitField.value && yearChoice.value === yearField.value;
    const syncSelection = () => {
        const ready = selectionReady();
        if (saveButton) saveButton.disabled = !ready;
        confirmation.disabled = !ready;
        fields.forEach((field) => { field.disabled = !ready; });
        if (!loading && selectionStatus) selectionStatus.textContent = ready
            ? `Isian aktif: ${unitField.value} · ${yearField.value}.`
            : 'Klik Next untuk memeriksa RKA dan melanjutkan ke input nominal.';
    };
    const setStep = (step) => {
        form.dataset.rkaStep = step;
        popup.dataset.rkaStage = step;
        selectionStage.hidden = step !== 'selection';
        inputStage.hidden = step !== 'input';
        saveButton.hidden = step !== 'input';
        status.hidden = step !== 'input';
        form.querySelector('[data-rka-save-confirm]').hidden = step !== 'input';
        form.querySelector('[data-rka-manual-back]').hidden = step !== 'selection';
        stepBack.hidden = step !== 'input';
        syncSelection();
    };
    const enterInputs = (data, edit = false) => {
        // Edit keeps exact stored amounts; setting again starts a blank draft.
        const replacements = fields.map((field) => edit ? format(parse(data.inputs[field.dataset.rkaInput])) : '');
        fields.forEach((field, index) => { field.value = replacements[index]; });
        unitField.value = data.unit;
        yearField.value = String(data.year);
        revisionField.value = String(data.revision);
        form.querySelector('[data-rka-manual-mode]').value = edit ? 'edit' : 'setting';
        form.querySelector('[data-rka-manual-edit-id]').value = edit ? String(data.budget_id) : '';
        popup.querySelector('#rkaManualTitle').textContent = edit ? 'Edit RKA' : 'Seting Manual RKA';
        saveButton.textContent = edit ? 'Simpan Perubahan' : 'Simpan RKA';
        confirmation.checked = false;
        popup.querySelector('[data-rka-manual-context]').textContent = `AKUTANSI / ${data.unit} · ${data.year}`;
        form.querySelector('#rka-manual-report-title').textContent = `RKA ${data.unit} Tahun ${data.year}`;
        form.querySelector('.lr-report-header p').textContent = `PT JAMKRINDO KANWIL SURABAYA · ${data.unit}`;
        form.querySelector('.lr-report-year').textContent = String(data.year);
        form.querySelector('.lr-report-caption').textContent = `RKA ${data.unit} Tahun ${data.year}`;
        form.querySelector('.lr-report-scroll').setAttribute('aria-label', `Tabel RKA ${data.unit}, dapat digeser ke samping`);
        form.querySelector('[data-rka-manual-confirm-text]').textContent = `Saya mengonfirmasi RKA ${data.unit} tahun ${data.year}. Menyimpan akan mengganti data RKA unit/tahun ini jika sudah ada.`;
        form.querySelector('[data-rka-manual-error]')?.remove();
        existingAlert.hidden = true;
        pendingExisting = null;
        dirty = false;
        update();
        setStep('input');
    };
    const choiceChanged = () => { pendingExisting = null; existingAlert.hidden = true; syncSelection(); };
    unitChoice.addEventListener('change', choiceChanged);
    yearChoice.addEventListener('input', choiceChanged);
    stepBack.addEventListener('click', () => { pendingExisting = null; existingAlert.hidden = true; setStep('selection'); });
    resetButton.addEventListener('click', () => {
        if (loading || !pendingExisting || pendingExisting.unit !== unitChoice.value || String(pendingExisting.year) !== yearChoice.value) return;
        enterInputs(pendingExisting);
    });
    form.querySelector('[data-rka-edit-existing]')?.addEventListener('click', () => {
        if (loading || !pendingExisting || pendingExisting.unit !== unitChoice.value || String(pendingExisting.year) !== yearChoice.value) return;
        enterInputs(pendingExisting, true);
    });
    form.querySelector('[data-rka-delete-existing]')?.addEventListener('click', () => {
        if (loading || !pendingExisting || pendingExisting.unit !== unitChoice.value || String(pendingExisting.year) !== yearChoice.value) return;
        document.dispatchEvent(new CustomEvent('rka:delete', {detail:{unit:pendingExisting.unit,year:pendingExisting.year,revision:pendingExisting.revision,id:pendingExisting.budget_id}}));
    });
    fields.forEach((field) => field.addEventListener('input', () => { dirty = true; }));
    const loadInputs = async (editTarget = null) => {
        if (loading) return;
        if (dirty && !window.confirm('Memuat unit/tahun akan mengganti isian yang belum disimpan. Lanjutkan?')) return;
        if (editTarget) {
            unitChoice.value = editTarget.unit;
            yearChoice.value = String(editTarget.year);
            setStep('selection');
            if (!popup.open) popup.showModal();
        }
        if (!unitChoice.reportValidity() || !yearChoice.reportValidity()) return;
        const requestedUnit = unitChoice.value;
        const requestedYear = yearChoice.value;
        pendingExisting = null;
        existingAlert.hidden = true;
        loading = true;
        unitChoice.disabled = yearChoice.disabled = loadButton.disabled = true;
        loadButton.textContent = 'Memuat…';
        syncSelection();
        if (selectionStatus) selectionStatus.textContent = 'Memuat isian RKA sesuai unit kerja dan tahun…';
        try {
            const url = new URL(form.dataset.rkaDataUrl, window.location.href);
            url.searchParams.set('unit_kerja', requestedUnit);
            url.searchParams.set('tahun', requestedYear);
            const response = await fetch(url, {credentials:'same-origin', cache:'no-store', headers:{Accept:'application/json'}});
            const contentType = response.headers?.get('content-type');
            if (contentType && !contentType.includes('application/json')) throw new Error('Sesi login berakhir atau data belum dapat dimuat. Silakan masuk kembali.');
            const data = await response.json();
            if (!response.ok) throw new Error(typeof data.error === 'string' ? data.error : 'Isian RKA tidak dapat dimuat.');
            if (data.unit !== requestedUnit || String(data.year) !== requestedYear || !Number.isSafeInteger(data.revision) || data.revision < 0
                || typeof data.has_record !== 'boolean' || !data.inputs || Object.keys(data.inputs).length !== fields.length
                || (data.has_record && (!Number.isSafeInteger(data.budget_id) || data.budget_id <= 0))) throw new Error('Data RKA yang diterima tidak sesuai pilihan.');
            if (editTarget && (!data.has_record || String(data.budget_id) !== String(editTarget.id))) throw new Error('RKA yang ingin diedit sudah dihapus atau diganti. Muat ulang halaman.');
            fields.forEach((field) => {
                const amount = data.inputs[field.dataset.rkaInput];
                if (typeof amount !== 'string' || !/^-?\d{1,22}\.\d{2}$/.test(amount)) throw new Error('Nominal RKA yang diterima tidak valid.');
                parse(amount);
            });
            loading = false;
            if (editTarget) enterInputs(data, true);
            else if (data.has_record) {
                pendingExisting = data;
                form.querySelector('[data-rka-existing-message]').textContent = `RKA ${data.unit} tahun ${data.year} sudah diseting. Pilih Edit RKA untuk mengubah nominal tersimpan, atau Seting Ulang untuk memulai isian kosong. Data lama hanya diganti setelah Anda menyimpan.`;
                existingAlert.hidden = false;
                syncSelection();
            } else enterInputs(data);
        } catch (error) {
            loading = false;
            syncSelection();
            if (selectionStatus) selectionStatus.textContent = `Gagal memuat: ${error.message}. Isian sebelumnya tetap dipertahankan.`;
        } finally {
            loading = false;
            unitChoice.disabled = yearChoice.disabled = loadButton.disabled = false;
            loadButton.textContent = 'Next →';
        }
    };
    loadButton.addEventListener('click', () => loadInputs());
    document.querySelectorAll('[data-rka-edit-open]').forEach((button) => button.addEventListener('click', () => loadInputs(button.dataset)));
    syncSelection();
})();

// Tutup panel urutan saat pengguna berinteraksi di luar panel.
(() => {
    const orderMenus = [...document.querySelectorAll('.list-order-menu')];
    if (orderMenus.length === 0) return;

    document.addEventListener('pointerdown', (event) => {
        orderMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        orderMenus.forEach((menu) => menu.removeAttribute('open'));
    });
})();

// Popup rekap, detail harian, koreksi anomali, dan konfirmasi penghapusan SDM Jatim.
(() => {
    const modalConfigurations = [
        {
            modal: document.querySelector('#attendanceAnomalyModal'),
            openSelector: '[data-open-attendance-anomaly]',
            closeSelector: '[data-close-attendance-anomaly]',
        },
        {
            modal: document.querySelector('#attendanceRecapDeleteModal'),
            openSelector: '[data-open-attendance-delete]',
            closeSelector: '[data-close-attendance-delete]',
        },
        {
            modal: document.querySelector('#attendanceSummaryModal'),
            openSelector: '[data-open-attendance-summary]',
            closeSelector: '[data-close-attendance-summary]',
        },
        {
            modal: document.querySelector('#attendanceDetailModal'),
            openSelector: '[data-open-attendance-detail]',
            closeSelector: '[data-close-attendance-detail]',
        },
    ];

    const openModal = (modal) => {
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        window.requestAnimationFrame(() => modal.classList.add('open'));
        document.body.style.overflow = 'hidden';
        window.setTimeout(() => modal.querySelector('input:not([type="hidden"]), select, button')?.focus(), 180);
    };

    const closeModal = (modal) => {
        if (!modal || !modal.classList.contains('open')) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { modal.hidden = true; }, 180);
    };

    modalConfigurations.forEach(({ modal, openSelector, closeSelector }) => {
        if (!modal) return;

        document.querySelectorAll(openSelector).forEach((trigger) => {
            trigger.addEventListener('click', () => openModal(modal));
        });
        modal.querySelectorAll(closeSelector).forEach((trigger) => {
            trigger.addEventListener('click', () => closeModal(modal));
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        modalConfigurations.forEach(({ modal }) => closeModal(modal));
    });

})();

// Kalender kerja SDM: buka pengaturan tanggal dari kartu kalender.
(() => {
    const modal = document.querySelector('#attendanceCalendarModal');
    if (!modal) return;

    const dateInput = modal.querySelector('[data-calendar-date]');
    const dateLabel = modal.querySelector('[data-calendar-date-label]');
    const modeInput = modal.querySelector('[data-calendar-mode]');
    const labelInput = modal.querySelector('[data-calendar-label]');
    const submitButton = modal.querySelector('[data-calendar-submit]');
    const monthContent = modal.querySelector('[data-calendar-month-content]');
    const monthTitle = modal.querySelector('[data-calendar-month-title]');
    const monthPicker = modal.querySelector('[data-calendar-picker-form]');
    const monthInput = modal.querySelector('#calendar_month');
    const yearInput = modal.querySelector('#calendar_year');
    const previousButton = modal.querySelector('[data-calendar-previous]');
    const nextButton = modal.querySelector('[data-calendar-next]');
    const loadError = modal.querySelector('[data-calendar-load-error]');

    const updateLabelState = () => {
        if (!modeInput || !labelInput) return;
        const automatic = modeInput.value === 'auto';
        labelInput.disabled = automatic;
        labelInput.placeholder = automatic
            ? 'Mengikuti keterangan kalender otomatis'
            : (modeInput.value === 'holiday' ? 'Contoh: Libur kantor' : 'Contoh: Hari kerja pengganti');
    };
    const closeModal = () => {
        if (!modal.classList.contains('open')) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { modal.hidden = true; }, 180);
    };
    const openModal = () => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        window.requestAnimationFrame(() => modal.classList.add('open'));
        document.body.style.overflow = 'hidden';
    };
    const resetEditor = () => {
        if (dateInput) dateInput.value = '';
        if (dateLabel) dateLabel.textContent = 'Belum dipilih';
        if (modeInput) {
            modeInput.value = 'auto';
            modeInput.disabled = true;
        }
        if (labelInput) {
            labelInput.value = '';
            labelInput.disabled = true;
            labelInput.placeholder = 'Pilih tanggal terlebih dahulu';
        }
        if (submitButton) submitButton.disabled = true;
    };
    const selectDay = (trigger) => {
        modal.querySelectorAll('[data-calendar-edit]').forEach((day) => day.classList.remove('is-selected'));
        trigger.classList.add('is-selected');
        if (dateInput) dateInput.value = trigger.dataset.date || '';
        if (dateLabel) dateLabel.textContent = trigger.dataset.dateLabel || '-';
        if (modeInput) {
            modeInput.disabled = false;
            modeInput.value = trigger.dataset.mode || 'auto';
        }
        if (labelInput) labelInput.value = trigger.dataset.mode === 'auto' ? '' : (trigger.dataset.label || '');
        if (submitButton) submitButton.disabled = false;
        updateLabelState();
        window.setTimeout(() => modeInput?.focus(), 100);
    };
    const loadMonth = async (year, month) => {
        if (!monthContent || !modal.dataset.calendarDataUrl) return;
        if (!Number.isFinite(year) || !Number.isFinite(month)) {
            if (loadError) {
                loadError.textContent = 'Bulan dan tahun kalender harus diisi.';
                loadError.hidden = false;
            }
            return;
        }
        const normalizedDate = new Date(year, month - 1, 1);
        const normalizedYear = normalizedDate.getFullYear();
        const normalizedMonth = normalizedDate.getMonth() + 1;
        if (normalizedYear < 2020 || normalizedYear > 2100) {
            if (loadError) {
                loadError.textContent = 'Tahun kalender harus berada antara 2020 dan 2100.';
                loadError.hidden = false;
            }
            return;
        }
        const url = new URL(modal.dataset.calendarDataUrl, window.location.origin);
        url.searchParams.set('format', 'json');
        url.searchParams.set('tahun', String(normalizedYear));
        url.searchParams.set('bulan', String(normalizedMonth));
        monthContent.classList.add('is-loading');
        if (loadError) loadError.hidden = true;
        previousButton?.setAttribute('disabled', 'disabled');
        nextButton?.setAttribute('disabled', 'disabled');
        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Kalender belum dapat dimuat.');
            monthContent.innerHTML = result.html;
            modal.dataset.calendarYear = String(result.year);
            modal.dataset.calendarMonth = String(result.month);
            if (monthTitle) monthTitle.textContent = result.month_label;
            if (yearInput) yearInput.value = String(result.year);
            if (monthInput) monthInput.value = String(result.month);
            resetEditor();
        } catch (error) {
            if (loadError) {
                loadError.textContent = error.message;
                loadError.hidden = false;
            }
        } finally {
            monthContent.classList.remove('is-loading');
            previousButton?.removeAttribute('disabled');
            nextButton?.removeAttribute('disabled');
        }
    };

    document.querySelectorAll('[data-calendar-open]').forEach((trigger) => trigger.addEventListener('click', openModal));
    modal.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-calendar-edit]') : null;
        if (trigger && modal.contains(trigger)) selectDay(trigger);
    });
    monthPicker?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!monthPicker.reportValidity()) return;
        loadMonth(Number(yearInput?.value), Number(monthInput?.value));
    });
    previousButton?.addEventListener('click', () => loadMonth(Number(modal.dataset.calendarYear), Number(modal.dataset.calendarMonth) - 1));
    nextButton?.addEventListener('click', () => loadMonth(Number(modal.dataset.calendarYear), Number(modal.dataset.calendarMonth) + 1));
    modal.querySelectorAll('[data-calendar-close]').forEach((trigger) => trigger.addEventListener('click', closeModal));
    modeInput?.addEventListener('change', updateLabelState);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeModal();
    });
    if (modal.dataset.calendarAutoOpen === 'true') openModal();
})();
