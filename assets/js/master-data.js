/**
 * Bengkel Pro V1 — Master Data CRUD Operations
 */
const MasterData = {
    currentTable: '',
    currentPage: 1,
    searchQuery: '',

    // Load data from API
    async load(table, page = 1, search = '') {
        this.currentTable = table;
        this.currentPage = page;
        this.searchQuery = search;
        
        const container = document.getElementById('data-container');
        if (!container) return;
        showLoading(container);
        
        const res = await apiRequest('get' + table.charAt(0).toUpperCase() + table.slice(1), { page, search, limit: 10 }, 'GET');
        
        if (res.status === 'success') {
            container.innerHTML = this.renderTable(res.data, res.pagination);
        } else {
            container.innerHTML = '<div class="empty-state text-dark-400"><p>Gagal memuat data</p></div>';
        }
    },

    // Render table
    renderTable(data, pagination) {
        if (!data || data.length === 0) {
            return `<div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-dark-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p class="text-dark-400 font-medium">Belum ada data</p>
                <p class="text-dark-500 text-sm mt-1">Klik tombol "Tambah" untuk menambah data baru</p>
            </div>`;
        }
        // Table HTML is rendered by each page's specific JS
        return '';
    },

    // Open add form
    add() {
        document.getElementById('modal-form').innerHTML = this.getFormHtml();
        document.getElementById('modal-title').textContent = 'Tambah ' + this.currentTable;
        document.getElementById('form-modal').classList.remove('hidden');
    },

    // Edit data
    async edit(id) {
        const res = await apiRequest('get' + this.currentTable.charAt(0).toUpperCase() + this.currentTable.slice(1), { id }, 'GET');
        if (res.status === 'success' && res.data) {
            document.getElementById('modal-form').innerHTML = this.getFormHtml(res.data);
            document.getElementById('modal-title').textContent = 'Edit ' + this.currentTable;
            document.getElementById('form-modal').classList.remove('hidden');
        }
    },

    // Save (create/update)
    async save() {
        const form = document.getElementById('data-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const id = data.id || '';
        
        const action = id ? 'update' + this.currentTable.charAt(0).toUpperCase() + this.currentTable.slice(1)
                          : 'add' + this.currentTable.charAt(0).toUpperCase() + this.currentTable.slice(1);
        
        const res = await apiRequest(action, data);
        if (res.status === 'success') {
            showToast(res.message || 'Data berhasil disimpan');
            document.getElementById('form-modal').classList.add('hidden');
            this.load(this.currentTable, this.currentPage, this.searchQuery);
        } else {
            showToast(res.message || 'Gagal menyimpan data', 'error');
        }
    },

    // Delete
    async deleteItem(id) {
        const confirmed = await confirmDelete();
        if (!confirmed) return;
        
        const res = await apiRequest('delete' + this.currentTable.charAt(0).toUpperCase() + this.currentTable.slice(1), { id });
        if (res.status === 'success') {
            showToast('Data berhasil dihapus');
            this.load(this.currentTable, this.currentPage, this.searchQuery);
        } else {
            showToast(res.message || 'Gagal menghapus data', 'error');
        }
    },

    // Search with debounce
    searchTimeout: null,
    onSearch(value) {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            this.load(this.currentTable, 1, value);
        }, 300);
    },

    // Close modal
    closeModal() {
        document.getElementById('form-modal').classList.add('hidden');
    },
    
    // To be overridden by each page
    getFormHtml(data = {}) {
        return '';
    }
};
