/**
 * Raya Mahardika — Portfolio Interactions & Behaviors
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Update copyright year dynamically
  const yearElement = document.getElementById('current-year');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }

  // 2. Active Navigation Highlight based on Scroll Position
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navItems = document.querySelectorAll('.nav-item');

  const observerOptions = {
    root: null,
    rootMargin: '-20% 0px -70% 0px',
    threshold: 0
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navItems.forEach((item) => {
          if (item.getAttribute('href') === `#${id}`) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach((section) => observer.observe(section));

  // 3. Email Button Click & Copy Feedback
  const emailBtn = document.getElementById('email-btn');
  const toast = document.getElementById('toast');
  const emailAddress = 'rayamahardikaibrahim@gmail.com';

  function showToast(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => {
      toast.classList.remove('show');
    }, 3000);
  }

  if (emailBtn) {
    // When right-clicked or clicked with Ctrl/Meta, copy email to clipboard
    emailBtn.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      navigator.clipboard.writeText(emailAddress).then(() => {
        showToast(`Email ${emailAddress} disalin!`);
      });
    });
  }

  // 4. Subtle Card Mouse Hover Effect (Glow / Lighting)
  const cards = document.querySelectorAll('.project-card');
  cards.forEach((card) => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      card.style.setProperty('--mouse-x', `${x}px`);
      card.style.setProperty('--mouse-y', `${y}px`);
    });
  });

  console.log('🚀 Portofolio Raya Mahardika siap dijalankan.');
});
