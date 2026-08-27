(() => {
    const authExpiresAt = Number(document.body.dataset.authExpiresAt || 0);
    const loginUrl = document.body.dataset.loginUrl || '';
    if (authExpiresAt > 0 && loginUrl !== '') {
        const redirectToLogin = () => window.location.replace(loginUrl);
        const remainingMilliseconds = (authExpiresAt * 1000) - Date.now();

        if (remainingMilliseconds <= 0) {
            redirectToLogin();
            return;
        }

        window.setTimeout(redirectToLogin, remainingMilliseconds);
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

    document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const group = toggle.closest('[data-nav-group]');
            const submenu = group?.querySelector('[data-nav-submenu]');
            if (!group || !submenu) return;
            const open = !group.classList.contains('open');
            group.classList.toggle('open', open);
            toggle.setAttribute('aria-expanded', String(open));
            submenu.hidden = !open;
        });
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
    const agendaLastStep = agendaStepPanels.length || 1;
    let agendaCurrentStep = 1;

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

    agendaDispositionStages.forEach((stage) => {
        const step = stage.dataset.dispositionFormStage;
        stage.querySelector(`[data-disposition-recipient="${step}"]`)?.addEventListener('input', () => updateDispositionFormState(stage));
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

    const setAgendaNumberState = (hasNumber) => {
        if (!agendaGenerateButton) return;
        agendaGenerateButton.disabled = hasNumber;
        agendaGenerateButton.textContent = hasNumber ? 'Nomor sudah dibuat' : 'Generate Nomor';
    };

    const closeAgendaForm = () => {
        if (!agendaFormModal) return;
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
        agendaForm.reset();
        agendaForm.action = agendaCreateUrl;
        agendaFormTitle.textContent = 'Tambah Surat Masuk';
        agendaSubmit.textContent = 'Simpan Surat Masuk';
        setAgendaStep(1);
        setAgendaSourceLock(false);
        setAgendaLink(true);
        setAgendaNumberState(false);
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
        agendaForm.reset();
        setAgendaStep(1);
        setAgendaSourceLock(false);
        setAgendaLink(false);
        agendaErrors.hidden = true;
        agendaFormTitle.textContent = 'Edit Surat Masuk';
        agendaSubmit.textContent = 'Simpan perubahan';
        agendaStatus.textContent = 'Memuat data...';
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
            agendaForm.elements.disposisi_1.value = data.disposisi_1_value || '';
            agendaForm.elements.disposisi_2.value = data.disposisi_2_value || '';
            agendaForm.elements.disposisi_3.value = data.disposisi_3_value || '';
            for (let step = 1; step <= 3; step += 1) {
                agendaForm.elements[`disposisi_${step}_status`].value = data[`disposisi_${step}_status_value`] || 'Menunggu';
                agendaForm.elements[`disposisi_${step}_waktu`].value = data[`disposisi_${step}_waktu_value`] || '';
                agendaForm.elements[`disposisi_${step}_catatan`].value = data[`disposisi_${step}_catatan_value`] || '';
            }
            agendaForm.elements.progres.value = data.progres || 'Menunggu Penyelesaian';
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
            setAgendaNumberState(true);
        } catch (error) {
            showAgendaErrors(error.message);
            setAgendaNumberState(false);
        }
    });

    agendaDownloadSheetButton?.addEventListener('click', async () => {
        const url = agendaDownloadSheetButton.dataset.downloadUrl;
        if (!url || !agendaForm) return;

        agendaErrors.hidden = true;
        agendaStatus.textContent = 'Membuat Lembar Pengendalian...';
        agendaDownloadSheetButton.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: new FormData(agendaForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/pdf, application/json' },
            });
            const csrfName = response.headers.get('X-CSRF-Name');
            const csrfHash = response.headers.get('X-CSRF-Hash');
            updateCsrf({ name: csrfName, hash: csrfHash });

            if (!response.ok) {
                const result = await response.json();
                updateCsrf(result.csrf);
                showAgendaErrors(result.message || 'Lembar Pengendalian belum dapat dibuat.', result.errors || []);
                return;
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
            showAgendaErrors('Koneksi ke aplikasi bermasalah. Silakan coba kembali.');
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
        agendaDispositionTimeline.innerHTML = timeline.map((item) => `
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
            documentLink.hidden = !hasDocumentLink;
            documentLink.href = hasDocumentLink ? data.dokumen_link : '#';
            documentEmpty.hidden = hasDocumentLink;
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
        ['nomor_surat','jenis_surat','pemohon','pelaksana','up','tanggal_pengiriman','nomor_resi','tanggal_diterima','penerima','alamat_penerima','dokumen_link','security','tanggal_security','progres','status_agendaris'].forEach((name) => {
            const valueKey = `${name}_value`;
            progressForm.elements[name].value = Object.prototype.hasOwnProperty.call(data, valueKey) ? data[valueKey] : (data[name] === '-' ? '' : data[name]);
        });
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
    const reopenProgressLabel = reopenProgressModal?.querySelector('[data-reopen-progress-label]');
    const reopenProgressError = reopenProgressModal?.querySelector('[data-reopen-progress-error]');
    const reopenProgressSubmit = reopenProgressModal?.querySelector('[data-reopen-progress-submit]');

    const closeReopenProgress = () => {
        if (!reopenProgressModal) return;
        reopenProgressModal.classList.remove('open');
        reopenProgressModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        window.setTimeout(() => { reopenProgressModal.hidden = true; }, 180);
    };

    document.querySelectorAll('[data-reopen-progress]').forEach((button) => button.addEventListener('click', () => {
        if (!reopenProgressModal || !reopenProgressForm) return;
        reopenProgressForm.action = button.dataset.reopenUrl;
        reopenProgressLabel.textContent = button.dataset.reopenLabel || 'Dokumen ini';
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
