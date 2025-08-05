document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileNav = document.getElementById('mobile-nav');
    const overlay = document.getElementById('overlay');
    const animationContainer = document.getElementById('bitto-animation');

    if (mobileMenu && mobileNav && overlay) {
        mobileMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileNav.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            mobileNav.classList.remove('active');
            this.classList.remove('active');
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (mobileMenu) mobileMenu.classList.remove('active');
            if (mobileNav) mobileNav.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        // Trigger animation when scrolling to bottom
        const scrollHeight = document.documentElement.scrollHeight;
        const clientHeight = document.documentElement.clientHeight;
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;

        if (scrollHeight - scrollTop <= clientHeight + 50 && animationContainer) {
            animationContainer.classList.add('active');
        } else if (animationContainer) {
            animationContainer.classList.remove('active');
        }
    });

    const ctx = document.getElementById('priceChart');
    if (ctx && typeof chartData !== 'undefined') {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { 
                            color: '#ADB5BD',
                            callback: function(value) {
                                return '$' + value.toFixed(6);
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#ADB5BD' }
                    }
                }
            }
        });
    }

    // Auto-detect mobile/desktop and initialize animation
    function initAnimation() {
        const isMobile = window.innerWidth < 768;
        animationContainer.classList.add(isMobile ? 'mobile' : 'desktop');

        if (isMobile) {
            // Mobile 2D Animation
            const canvas = document.createElement('canvas');
            canvas.width = window.innerWidth;
            canvas.height = 150;
            animationContainer.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            const img = new Image();
            img.src = 'images/photo_2025-07-31_12-03-41.jpg';

            let angle = 0;
            function animateMobile() {
                if (animationContainer.classList.contains('active')) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.save();
                    ctx.translate(canvas.width / 2, canvas.height / 2);
                    ctx.rotate(angle * Math.PI / 180);
                    if (img.complete) {
                        ctx.drawImage(img, -img.width / 2, -img.height / 2);
                    }
                    ctx.restore();
                    angle = (angle + 1) % 360;
                }
                requestAnimationFrame(animateMobile);
            }
            img.onload = animateMobile;
        } else {
            // Desktop 3D Animation
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / 200, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ alpha: true });
            renderer.setSize(window.innerWidth, 200);
            animationContainer.appendChild(renderer.domElement);

            const geometry = new THREE.BoxGeometry(1, 1, 1);
            const textureLoader = new THREE.TextureLoader();
            const texture = textureLoader.load('images/photo_2025-07-31_12-03-41.jpg');
            const material = new THREE.MeshBasicMaterial({ map: texture });
            const cube = new THREE.Mesh(geometry, material);
            scene.add(cube);

            camera.position.z = 2;

            function animateDesktop() {
                requestAnimationFrame(animateDesktop);
                if (animationContainer.classList.contains('active')) {
                    cube.rotation.x += 0.01;
                    cube.rotation.y += 0.01;
                    renderer.render(scene, camera);
                }
            }
            animateDesktop();

            window.addEventListener('resize', () => {
                renderer.setSize(window.innerWidth, 200);
                camera.aspect = window.innerWidth / 200;
                camera.updateProjectionMatrix();
            });
        }
    }

    initAnimation();
});

// Cross-browser clipboard copy
function copyText(text, message) {
    const notification = document.getElementById('copy-notification');
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            notification.textContent = message;
            notification.classList.add('show');
            setTimeout(() => notification.classList.remove('show'), 3000);
        }).catch(err => {
            console.error('Clipboard API failed:', err);
            fallbackCopyText(text, message);
        });
    } else {
        fallbackCopyText(text, message);
    }
}

function fallbackCopyText(text, message) {
    const notification = document.getElementById('copy-notification');
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        notification.textContent = message;
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
    } catch (err) {
        console.error('Fallback copy failed:', err);
        notification.textContent = 'Copy failed!';
        notification.classList.add('show');
        setTimeout(() => notification.classList.remove('show'), 3000);
    }
    document.body.removeChild(textArea);
}

function copyContract() {
    copyText('6cHJn25Pay6nd4JJsKKZ8Fmm2NL43t8BY5Mp1hhiUb56', 'Contract address copied to clipboard!');
}

function copyMarketingWallet() {
    copyText('DTJEL9ModY8E5mGS4StgpUjp8mkWaj3yDCuruYva8mSN', 'Marketing wallet address copied to clipboard!');
}