// Main JavaScript for Islamic Calligraphy Gallery
// UPDATED: Animations enhanced, 3000x3000 badges removed

document.addEventListener('DOMContentLoaded', function () {
    console.log('Islamic Calligraphy Gallery - Loading Enhanced Features');

    // ============================================
    // 1. INITIALIZE ANIMATIONS
    // ============================================

    function initializeAnimations() {
        // Pinterest-inspired Intersection Observer for scroll animations
        const revealOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, revealOptions);

        document.querySelectorAll('.reveal-up').forEach(el => revealObserver.observe(el));

        // Animate any existing art cards
        document.querySelectorAll('.art-card').forEach((card, index) => {
            card.style.setProperty('--i', index);
            card.style.animationDelay = `${index * 0.1}s`;
            card.style.animation = 'fadeInUp 0.6s ease forwards';
        });

        // Animate about section
        const aboutSection = document.querySelector('.about-image-container');
        if (aboutSection) {
            aboutSection.style.animation = 'fadeInUp 0.8s ease 0.4s forwards';
        }

        // Add hover animations to buttons
        document.querySelectorAll('.btn-gold, .btn-enquire').forEach(btn => {
            btn.addEventListener('mouseenter', function () {
                this.style.animation = 'pulse 1.5s infinite';
            });
            btn.addEventListener('mouseleave', function () {
                this.style.animation = '';
            });
        });
    }

    initializeAnimations();

    // ============================================
    // 2. INITIALIZE BOOTSTRAP TOOLTIPS
    // ============================================

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ============================================
    // 3. SMOOTH NAVIGATION
    // ============================================

    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });

                // Update active nav link
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                });
                this.classList.add('active');
            }
        });
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function () {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 100) {
            navbar.style.padding = '0.5rem 0';
            navbar.style.background = 'rgba(26, 26, 46, 0.98)';
            navbar.style.backdropFilter = 'blur(10px)';
        } else {
            navbar.style.padding = '1rem 0';
            navbar.style.background = 'rgba(26, 26, 46, 0.95)';
            navbar.style.backdropFilter = 'none';
        }

        // Update active nav based on scroll position
        updateActiveNav();

        // Show/hide back to top button
        const backToTopBtn = document.getElementById('backToTop');
        if (backToTopBtn) {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        }
    });

    // ============================================
    // 4. BACK TO TOP BUTTON
    // ============================================

    const backToTopBtn = document.createElement('button');
    backToTopBtn.id = 'backToTop';
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTopBtn.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
        color: #1A1A2E;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 999;
        animation: bounce 2s infinite;
    `;

    document.body.appendChild(backToTopBtn);

    backToTopBtn.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // ============================================
    // 5. ARTWORK DATA (DYNAMIC LOAD)
    // ============================================

    let artworks = {}; // Will hold fetched data

    async function fetchArtworks() {
        try {
            const loading = document.getElementById('gallery-loading');
            if(loading) loading.style.display = 'block';
            
            const res = await fetch('http://localhost/calligraphy_website/api/artworks.php?action=list');
            const data = await res.json();
            
            if (data.success) {
                renderArtworks(data.data);
            } else {
                console.error('Failed to load artworks', data.message);
            }
        } catch(e) {
            console.error('Error fetching artworks', e);
        } finally {
            const loading = document.getElementById('gallery-loading');
            if(loading) loading.style.display = 'none';
        }
    }

    function renderArtworks(arts) {
        const gridView = document.getElementById('gridView');
        if (!gridView) return;
        
        const template = document.getElementById('artwork-card-template');
        if (!template) return;

        // Clear existing artworks (except template and loading)
        const items = gridView.querySelectorAll('.col-lg-4:not(template)');
        items.forEach(i => i.remove());

        arts.forEach((art, index) => {
            artworks[art.id] = art; // Store for modals
            
            const clone = template.content.cloneNode(true);
            const card = clone.querySelector('.art-card');
            
            card.setAttribute('data-art-id', art.id);
            
            // Image setup
            const img = clone.querySelector('.art-image');
            img.src = art.image_path;
            img.alt = art.title;
            // Blur up effect implementation
            img.onload = () => img.classList.add('loaded');
            
            // Setup buttons
            clone.querySelector('.btn-view-full').setAttribute('data-art', art.id);
            clone.querySelector('.btn-view-full').setAttribute('data-bs-toggle', 'modal');
            clone.querySelector('.btn-view-full').setAttribute('data-bs-target', '#fullModal');
            
            clone.querySelector('.btn-download').setAttribute('data-art', art.id);
            clone.querySelector('.btn-share').setAttribute('data-art', art.id);
            clone.querySelector('.btn-enquire').setAttribute('data-art', art.id);
            
            // Setup info
            clone.querySelector('h3').textContent = art.title;
            clone.querySelector('.art-info p').textContent = art.description.substring(0, 60) + '...';
            
            // Badge
            const badge = clone.querySelector('.badge');
            if (art.badge_text) {
                badge.textContent = art.badge_text;
            } else {
                badge.style.display = 'none';
            }
            
            clone.querySelector('.price').textContent = '$' + Number(art.price).toLocaleString();
            
            // Setup tilt effect
            setupTiltEffect(card);

            gridView.appendChild(clone);
            
            // Observe new element for reveal
            const newlyAdded = gridView.lastElementChild;
            const revealObserver = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
            revealObserver.observe(newlyAdded);
        });

        // Re-bind events to dynamically added buttons
        bindDynamicEvents();
    }

    function setupTiltEffect(card) {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateX = ((y - centerY) / centerY) * -10;
            const rotateY = ((x - centerX) / centerX) * 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
        });
    }

    function bindDynamicEvents() {
        // Re-bind Enquire
        document.querySelectorAll('.btn-enquire:not(.bound)').forEach(button => {
            button.classList.add('bound');
            button.addEventListener('click', function () {
                const artId = this.getAttribute('data-art');
                const artData = artworks[artId];
                if (artData) {
                    document.getElementById('contact').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    const subjectSelect = document.getElementById('subject');
                    const messageTextarea = document.getElementById('message');
                    if (subjectSelect) subjectSelect.value = 'Artwork Purchase';
                    if (messageTextarea) {
                        messageTextarea.value = `I'm interested in purchasing "${artData.title}" for $${artData.price}.\n\nArtwork Details:\n- Style: ${artData.style}\n- Size: ${artData.size}\n- Medium: ${artData.medium}\n- Frame: ${artData.frame_info}\n\nArtwork ID: ${artId}`;
                    }
                    showNotification(`Enquiry prepared for "${artData.title}"`, 'success');
                }
            });
        });

        // Re-bind Download
        document.querySelectorAll('.btn-download:not(.bound)').forEach(btn => {
            btn.classList.add('bound');
            btn.addEventListener('click', function () {
                const artId = this.getAttribute('data-art');
                const artData = artworks[artId];
                if (artData) downloadImage(artData.image_path, artData.title);
            });
        });

        // Re-bind Share
        document.querySelectorAll('.btn-share:not(.bound)').forEach(btn => {
            btn.classList.add('bound');
            btn.addEventListener('click', function () {
                const artId = this.getAttribute('data-art');
                const artData = artworks[artId];
                if (artData) {
                    if (navigator.share) {
                        navigator.share({
                            title: `${artData.title} - Sania's Calligraphy`,
                            text: `Check out this beautiful Islamic calligraphy: ${artData.title}`,
                            url: window.location.href + '?art=' + artId,
                        }).then(() => showNotification('Shared successfully!', 'success')).catch(e => console.log(e));
                    } else {
                        copyToClipboard(`${window.location.origin}${window.location.pathname}?art=${artId}`);
                        showNotification('Link copied to clipboard!', 'success');
                    }
                }
            });
        });
    }

    // Call fetch on load
    fetchArtworks();

    // ============================================
    // 6. GALLERY CONTROLS
    // ============================================

    const gridViewBtn = document.getElementById('gridViewBtn');
    const fullViewBtn = document.getElementById('fullViewBtn');
    const zoomAllBtn = document.getElementById('zoomAllBtn');
    const gridView = document.getElementById('gridView');

    if (gridViewBtn && fullViewBtn) {
        gridViewBtn.addEventListener('click', function () {
            this.classList.add('active');
            fullViewBtn.classList.remove('active');
            gridView.classList.remove('masonry-view');
        });

        fullViewBtn.addEventListener('click', function () {
            this.classList.add('active');
            gridViewBtn.classList.remove('active');
            gridView.classList.add('masonry-view');
            applyMasonryLayout();
        });
    }

    // ============================================
    // 7. ZOOM ALL FUNCTIONALITY
    // ============================================

    if (zoomAllBtn) {
        zoomAllBtn.addEventListener('click', function () {
            document.querySelectorAll('.art-image').forEach(img => {
                img.style.transform = 'scale(1.1)';
                img.style.transition = 'transform 0.3s ease';
            });

            setTimeout(() => {
                document.querySelectorAll('.art-image').forEach(img => {
                    img.style.transform = 'scale(1)';
                });
            }, 300);

            showNotification('All artworks zoomed in temporarily', 'success');
        });
    }

    // ============================================
    // 8. MODAL FUNCTIONALITY
    // ============================================

    // Full view modal
    const fullModal = document.getElementById('fullModal');
    if (fullModal) {
        fullModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const artId = button.getAttribute('data-art');
            const artData = artworks[artId];

            if (artData) {
                document.getElementById('fullArtTitle').textContent = artData.title;
                document.getElementById('fullArtImage').src = artData.image_path;
                document.getElementById('fullArtImage').alt = artData.title;
                document.getElementById('fullArtDescription').textContent = artData.description;

                // Set download button
                const downloadBtn = document.getElementById('downloadFullBtn');
                if (downloadBtn) {
                    downloadBtn.onclick = function () {
                        downloadImage(artData.image_path, artData.title);
                    };
                }
            }
        });
    }

    // Artwork modal
    const artModal = document.getElementById('artModal');
    if (artModal) {
        artModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const artId = button.getAttribute('data-art');
            const artData = artworks[artId];

            if (artData) {
                document.getElementById('modalArtTitle').textContent = artData.title;
                document.getElementById('modalArtDescription').textContent = artData.description;
                document.getElementById('modalArtStyle').textContent = artData.style;
                document.getElementById('modalArtSize').textContent = artData.size;
                document.getElementById('modalArtMedium').textContent = artData.medium;
                document.getElementById('modalArtFrame').textContent = artData.frame_info;
                document.getElementById('modalArtPrice').textContent = artData.price;
                document.getElementById('modalArtImage').src = artData.image_path;
                document.getElementById('modalArtImage').alt = artData.title;
                document.getElementById('artModalTitle').textContent = artData.title;
            }
        });
    }

    // ============================================
    // 9. ACTION BUTTONS (Handled dynamically now)
    // ============================================

    // Download image function (kept global for reuse)
    function downloadImage(imageSrc, fileName) {
        const link = document.createElement('a');
        link.href = imageSrc;
        link.download = `Sania_Calligraphy_${fileName.replace(/\s+/g, '_')}.jpg`;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification(`Downloading "${fileName}"...`, 'success');
    }

    // Copy to clipboard utility
    function copyToClipboard(text) {
        const dummy = document.createElement('textarea');
        document.body.appendChild(dummy);
        dummy.value = text;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);
    }

    // ============================================
    // 10. CONTACT FORM ENHANCEMENT
    // ============================================

    // ============================================
    // CONTACT FORM ENHANCEMENT - UPDATED
    // ============================================

    const contactForm = document.getElementById('contactForm');
    const formMessage = document.getElementById('formMessage');

    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
            submitBtn.disabled = true;

            if (formMessage) {
                formMessage.innerHTML = '<div class="alert alert-info">Sending your message...</div>';
                formMessage.style.display = 'block';
            }

            // Collect form data
            const formData = new FormData(this);

            // Send AJAX request to PHP
            fetch('http://localhost/calligraphy_website/contact.php', {
                method: 'POST',
                body: formData,
                mode: 'cors',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Try to parse JSON
                    return response.json().catch(err => {
                        console.error('JSON parse error:', err);
                        // If not JSON, get text
                        return response.text().then(text => {
                            throw new Error('Server returned non-JSON response: ' + text);
                        });
                    });
                })
                .then(data => {
                    console.log('Response data:', data);

                    if (data.success) {
                        // Success
                        submitBtn.innerHTML = '<i class="fas fa-check me-2"></i> Sent Successfully!';
                        submitBtn.classList.add('btn-success');

                        if (formMessage) {
                            formMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        }

                        // Optional: Show notification
                        if (typeof showNotification === 'function') {
                            showNotification(data.message, 'success');
                        }

                        // Reset form after 3 seconds
                        setTimeout(() => {
                            contactForm.reset();
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('btn-success');
                            if (formMessage) {
                                formMessage.style.display = 'none';
                                formMessage.innerHTML = '';
                            }
                        }, 3000);
                    } else {
                        // Error from server
                        submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Error!';
                        submitBtn.classList.add('btn-danger');

                        if (formMessage) {
                            formMessage.innerHTML = `<div class="alert alert-danger">${data.message || 'Server error occurred'}</div>`;
                        }

                        // Reset button after 3 seconds
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('btn-danger');
                        }, 3000);
                    }
                })
                .catch(error => {
                    // Network or parsing error
                    console.error('Fetch error:', error);

                    submitBtn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Network Error';
                    submitBtn.classList.add('btn-danger');

                    if (formMessage) {
                        formMessage.innerHTML = `<div class="alert alert-danger">Network error: ${error.message}. Please check console.</div>`;
                    }

                    // Show error in console for debugging
                    console.error('Full error details:', error);

                    // Reset button after 3 seconds
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-danger');
                    }, 3000);
                });
        });
    }

    // Update active navigation
    function updateActiveNav() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPos = window.scrollY + 100;

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            const sectionId = section.getAttribute('id');

            if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    // Masonry layout
    function applyMasonryLayout() {
        const cards = document.querySelectorAll('.art-card');
        cards.forEach(card => {
            card.style.width = '100%';
            card.style.marginBottom = '20px';
        });

        // Simple masonry effect using CSS columns
        if (gridView) {
            gridView.style.columnCount = '3';
            gridView.style.columnGap = '20px';

            if (window.innerWidth < 992) {
                gridView.style.columnCount = '2';
            }
            if (window.innerWidth < 768) {
                gridView.style.columnCount = '1';
            }
        }
    }

    // ============================================
    // 12. SCROLL TO CONTACT FUNCTION
    // ============================================

    window.scrollToContact = function () {
        document.getElementById('contact').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });

        const modal = bootstrap.Modal.getInstance(document.getElementById('artModal'));
        if (modal) modal.hide();

        const fullModal = bootstrap.Modal.getInstance(document.getElementById('fullModal'));
        if (fullModal) fullModal.hide();
    };

    // ============================================
    // 13. RESIZE HANDLER
    // ============================================

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (document.querySelector('.masonry-view')) {
                const gridView = document.getElementById('gridView');
                if (gridView && gridView.classList.contains('masonry-view')) {
                    if (window.innerWidth < 768) {
                        gridView.style.columnCount = '1';
                    } else if (window.innerWidth < 992) {
                        gridView.style.columnCount = '2';
                    } else {
                        gridView.style.columnCount = '3';
                    }
                }
            }
        }, 250);
    });

    console.log('✅ All features loaded successfully!');
    
    // Load artworks from API
    fetchArtworks();
});

// ============================================
// PARTICLES EFFECT FUNCTION
// ============================================

function createParticles() {
    const container = document.querySelector('.hero-section');
    if (!container) return;

    // Add particle styles
    const style = document.createElement('style');
    style.textContent = `
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(212, 175, 55, 0.6);
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
        }
        @keyframes float-particle {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); }
            25% { transform: translateY(-20px) translateX(10px) rotate(90deg); }
            50% { transform: translateY(-10px) translateX(20px) rotate(180deg); }
            75% { transform: translateY(-30px) translateX(5px) rotate(270deg); }
        }
    `;
    document.head.appendChild(style);

    // Create particles
    for (let i = 0; i < 15; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.cssText = `
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: float-particle ${Math.random() * 20 + 10}s infinite ease-in-out;
            animation-delay: ${Math.random() * 5}s;
            opacity: ${Math.random() * 0.5 + 0.3};
        `;
        container.appendChild(particle);
    }
}