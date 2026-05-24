// Admin Panel Logic

document.addEventListener('DOMContentLoaded', () => {
    // Check Authentication on load
    checkAuth();

    // Setup navigation
    setupNavigation();

    // Setup forms
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    document.getElementById('saveArtworkBtn').addEventListener('click', saveArtwork);
    document.getElementById('logoutBtn').addEventListener('click', handleLogout);
    
    // Setup image upload drag/drop
    setupImageUpload();
});

// --- State ---
let isEditMode = false;
let currentArtworkId = null;
let currentView = 'loginView';

// --- Auth Functions ---

async function checkAuth() {
    try {
        const res = await fetch('auth.php?action=check');
        const data = await res.json();
        
        if (data.logged_in) {
            document.getElementById('adminName').textContent = data.user.username;
            switchView('dashboardView');
            loadDashboardData();
        } else {
            switchView('loginView');
        }
    } catch (e) {
        console.error('Auth check failed', e);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button');
    const originalText = btn.textContent;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('username', document.getElementById('username').value);
    formData.append('password', document.getElementById('password').value);

    try {
        const res = await fetch('auth.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast('Success', data.message, 'success');
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            checkAuth();
        } else {
            showToast('Error', data.message, 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (e) {
        showToast('Error', 'Login failed. Please try again.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function handleLogout() {
    const formData = new FormData();
    formData.append('action', 'logout');
    await fetch('auth.php', { method: 'POST', body: formData });
    switchView('loginView');
}

// --- Navigation ---

function switchView(viewId) {
    document.querySelectorAll('.view-section').forEach(el => el.style.display = 'none');
    document.getElementById(viewId).style.display = 'block';
    currentView = viewId;
}

function setupNavigation() {
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Update active class
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            e.currentTarget.classList.add('active');

            // Switch content
            const targetId = e.currentTarget.getAttribute('data-target');
            document.querySelectorAll('.content-section').forEach(el => el.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';

            // Update title
            document.getElementById('pageTitle').textContent = e.currentTarget.textContent.trim();

            // Load data
            if (targetId === 'dashboardContent') {
                loadDashboardData();
            } else if (targetId === 'artworksContent') {
                loadArtworks();
            }
        });
    });
}

// --- Data Loading ---

async function loadDashboardData() {
    try {
        const res = await fetch('api.php?action=dashboard');
        if(res.status === 401) { checkAuth(); return; }
        const data = await res.json();

        if (data.success) {
            document.getElementById('statTotalArtworks').textContent = data.data.total_artworks;
            document.getElementById('statTotalValue').textContent = '$' + Number(data.data.total_value).toLocaleString();
            document.getElementById('statTotalEmails').textContent = data.data.recent_emails.length; // Simplified for now

            // Populate recent artworks
            const list = document.getElementById('recentArtworksList');
            list.innerHTML = '';
            data.data.recent_artworks.forEach(art => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><img src="../${art.image_path}" class="table-img" alt="${art.title}"></td>
                    <td>${art.title}</td>
                    <td class="text-gold fw-bold">$${Number(art.price).toLocaleString()}</td>
                    <td class="text-muted">${new Date(art.created_at).toLocaleDateString()}</td>
                `;
                list.appendChild(tr);
            });

            // Populate recent activity
            const activityList = document.getElementById('recentActivityList');
            activityList.innerHTML = '';
            data.data.recent_emails.forEach(email => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="activity-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="activity-details">
                        <p>Email sent: ${email.action_type} Notification</p>
                        <span class="activity-time">${new Date(email.sent_at).toLocaleString()}</span>
                    </div>
                `;
                activityList.appendChild(li);
            });
        }
    } catch (e) {
        console.error('Failed to load dashboard', e);
    }
}

async function loadArtworks() {
    try {
        const res = await fetch('api.php?action=list');
        if(res.status === 401) { checkAuth(); return; }
        const data = await res.json();

        if (data.success) {
            const list = document.getElementById('artworksList');
            list.innerHTML = '';
            data.data.forEach(art => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${art.sort_order}</td>
                    <td><img src="../${art.image_path}" class="table-img" alt="${art.title}"></td>
                    <td><strong>${art.title}</strong></td>
                    <td>${art.style || '-'}</td>
                    <td class="text-gold fw-bold">$${Number(art.price).toLocaleString()}</td>
                    <td>
                        <span class="badge ${art.is_featured == 1 ? 'bg-success' : 'bg-secondary'}">
                            ${art.is_featured == 1 ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td>
                        <button class="action-btn btn-edit" onclick="editArtwork(${art.id})"><i class="fas fa-edit"></i></button>
                        <button class="action-btn btn-delete" onclick="deleteArtwork(${art.id}, '${art.title}')"><i class="fas fa-trash"></i></button>
                    </td>
                `;
                list.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Failed to load artworks', e);
    }
}

// --- Artwork CRUD ---

function openArtworkModal() {
    isEditMode = false;
    currentArtworkId = null;
    document.getElementById('artworkForm').reset();
    document.getElementById('artworkModalTitle').textContent = 'Add Artwork';
    removeImagePreview();
    new bootstrap.Modal(document.getElementById('artworkModal')).show();
}

async function editArtwork(id) {
    try {
        const res = await fetch(`api.php?action=get&id=${id}`);
        const data = await res.json();

        if (data.success) {
            isEditMode = true;
            currentArtworkId = id;
            const art = data.data;

            document.getElementById('artworkModalTitle').textContent = 'Edit Artwork';
            document.getElementById('title').value = art.title;
            document.getElementById('price').value = art.price;
            document.getElementById('style').value = art.style;
            document.getElementById('size').value = art.size;
            document.getElementById('medium').value = art.medium;
            document.getElementById('frame_info').value = art.frame_info;
            document.getElementById('badge_text').value = art.badge_text;
            document.getElementById('sort_order').value = art.sort_order;
            document.getElementById('description').value = art.description;
            document.getElementById('is_featured').checked = art.is_featured == 1;

            if (art.image_path) {
                showImagePreview(`../${art.image_path}`);
            } else {
                removeImagePreview();
            }

            new bootstrap.Modal(document.getElementById('artworkModal')).show();
        }
    } catch (e) {
        showToast('Error', 'Failed to fetch artwork details', 'error');
    }
}

async function saveArtwork() {
    const form = document.getElementById('artworkForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const fileInput = document.getElementById('imageInput');
    
    // Validation
    if (!isEditMode && (!fileInput.files || fileInput.files.length === 0)) {
        showToast('Warning', 'Please select an image for the new artwork', 'warning');
        return;
    }

    const btn = document.getElementById('saveArtworkBtn');
    const originalText = btn.textContent;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    const url = isEditMode ? `api.php?action=update&id=${currentArtworkId}` : `api.php?action=create`;

    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast('Success', data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('artworkModal')).hide();
            loadArtworks();
        } else {
            showToast('Error', data.message, 'error');
        }
    } catch (e) {
        showToast('Error', 'Failed to save artwork', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

async function deleteArtwork(id, title) {
    if (confirm(`Are you sure you want to delete "${title}"? This cannot be undone.`)) {
        try {
            const res = await fetch(`api.php?action=delete&id=${id}`, { method: 'POST' });
            const data = await res.json();
            
            if (data.success) {
                showToast('Success', data.message, 'success');
                loadArtworks();
            } else {
                showToast('Error', data.message, 'error');
            }
        } catch(e) {
            showToast('Error', 'Failed to delete artwork', 'error');
        }
    }
}

// --- Image Upload UI ---

function setupImageUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const removeBtn = document.getElementById('removeImageBtn');

    uploadArea.addEventListener('click', (e) => {
        if(e.target !== removeBtn && e.target !== removeBtn.querySelector('i')) {
            imageInput.click();
        }
    });

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            imageInput.files = e.dataTransfer.files;
            handleImageSelection();
        }
    });

    imageInput.addEventListener('change', handleImageSelection);
    removeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        removeImagePreview();
        imageInput.value = '';
    });
}

function handleImageSelection() {
    const input = document.getElementById('imageInput');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            showImagePreview(e.target.result);
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function showImagePreview(src) {
    document.getElementById('imagePreview').src = src;
    document.getElementById('imagePreview').classList.remove('d-none');
    document.getElementById('removeImageBtn').classList.remove('d-none');
    document.getElementById('uploadPlaceholder').classList.add('d-none');
}

function removeImagePreview() {
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreview').classList.add('d-none');
    document.getElementById('removeImageBtn').classList.add('d-none');
    document.getElementById('uploadPlaceholder').classList.remove('d-none');
}

// --- UI Utilities ---

function showToast(title, message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const id = 'toast-' + Date.now();
    
    let icon = 'info-circle';
    let titleColor = 'text-white';
    if(type === 'success') { icon = 'check-circle'; titleColor = 'text-success'; }
    if(type === 'error') { icon = 'exclamation-circle'; titleColor = 'text-danger'; }
    if(type === 'warning') { icon = 'exclamation-triangle'; titleColor = 'text-warning'; }

    const html = `
        <div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-${icon} ${titleColor} me-2"></i>
                <strong class="me-auto text-white">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    
    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}
